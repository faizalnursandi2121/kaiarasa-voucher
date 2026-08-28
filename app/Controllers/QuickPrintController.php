<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Helpers\FlashHelper;
use App\Helpers\HotspotHelper;
use App\Libraries\RouterOSAPI;
use App\Models\Config;
use App\Models\Logo;
use App\Models\QuickPrintModel;
use App\Models\Setting;
use App\Models\VoucherTemplateModel;

class QuickPrintController extends Controller
{
    public function __construct()
    {
        Middleware::auth();
    }

    // Dashboard: List Cards
    public function index($session)
    {
        $qpModel = new QuickPrintModel;

        $configModel = new Config;
        $creds = $configModel->getSession($session);
        $routerId = $creds['id'] ?? null;

        // If no ID (Legacy), fallback to empty list or handle gracefully.
        // For now, we assume ID exists as per migration plan.
        $packages = $routerId ? $qpModel->getAllByRouterId($routerId) : [];

        // Fetch voucher templates for the selector
        $tplModel = new VoucherTemplateModel;
        $templates = $tplModel->getAll();

        // Fetch default template from settings
        $settingModel = new Setting;
        $defaultTemplate = $settingModel->get('default_voucher_template', 'default');

        // Fetch router profiles — untuk visual check: card dengan profile
        // yang sudah dihapus dari Data Plans akan diberi badge "Data Plan Missing"
        // dan tombol Print di-disable. Server-side tetap reject di printPacket.
        $routerProfiles = [];
        if ($creds) {
            $API = RouterOSAPI::fromSession($creds);
            $API->attempts = 1;
            $API->delay = 0;
            $password = $creds['password'];
            if (isset($creds['source']) && $creds['source'] === 'legacy') {
                $password = RouterOSAPI::decrypt($password);
            }
            if ($API->connect($creds['ip'], $creds['user'], $password)) {
                $profiles = $API->comm('/ip/hotspot/user/profile/print');
                if (is_array($profiles)) {
                    foreach ($profiles as $p) {
                        $routerProfiles[] = $p['name'] ?? '';
                    }
                }
                $API->disconnect();
            }
        }

        $data = [
            'session' => $session,
            'packages' => $packages,
            'templates' => $templates,
            'defaultTemplate' => $defaultTemplate,
            'routerProfiles' => $routerProfiles,
        ];

        // Note: View will be 'quick_print/index'
        return $this->view('quick_print/index', $data);
    }

    // List/Manage Packages (CRUD)
    public function manage($session)
    {
        $qpModel = new QuickPrintModel;

        $configModel = new Config;
        $creds = $configModel->getSession($session);
        $routerId = $creds['id'] ?? null;

        $packages = $routerId ? $qpModel->getAllByRouterId($routerId) : [];
        $profiles = [];
        if ($creds) {
            $API = RouterOSAPI::fromSession($creds);
        $API->attempts = 1;
        $API->delay = 0;
            $password = $creds['password'];
            if (isset($creds['source']) && $creds['source'] === 'legacy') {
                $password = RouterOSAPI::decrypt($password);
            }
            if ($API->connect($creds['ip'], $creds['user'], $password)) {
                $profiles = $API->comm('/ip/hotspot/user/profile/print');
                $API->disconnect();
            }
        }

        $data = [
            'session' => $session,
            'packages' => $packages,
            'profiles' => $profiles,
        ];

        return $this->view('quick_print/list', $data);
    }

    // CRUD: Store
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $session = $_POST['session'] ?? '';

        $configModel = new Config;
        $creds = $configModel->getSession($session);
        $routerId = $creds['id'] ?? 0;

        // Build time_limit (e.g. "1d2h30m") and data_limit (bytes) from split fields
        $timelimit_d = $_POST['timelimit_d'] ?? '';
        $timelimit_h = $_POST['timelimit_h'] ?? '';
        $timelimit_m = $_POST['timelimit_m'] ?? '';
        $timeLimit = '';
        if ($timelimit_d !== '') { $timeLimit .= $timelimit_d.'d'; }
        if ($timelimit_h !== '') { $timeLimit .= $timelimit_h.'h'; }
        if ($timelimit_m !== '') { $timeLimit .= $timelimit_m.'m'; }

        $datalimit_val = $_POST['datalimit_val'] ?? '';
        $datalimit_unit = $_POST['datalimit_unit'] ?? 'MB';
        $dataLimit = 0; // bytes
        if ($datalimit_val !== '' && is_numeric($datalimit_val)) {
            $dataLimit = (int) round((float) $datalimit_val * (($datalimit_unit === 'GB') ? 1073741824 : 1048576));
        }

        $data = [
            'router_id' => $routerId,
            'session_name' => $session,
            'name' => $_POST['name'] ?? 'Package',
            'server' => $_POST['server'] ?? 'all',
            'profile' => $_POST['profile'] ?? 'default',
            'prefix' => $_POST['prefix'] ?? '',
            'char_length' => $_POST['char_length'] ?? 4,
            'price' => $_POST['price'] ?? 0,
            'selling_price' => $_POST['selling_price'] ?? ($_POST['price'] ?? 0),
            'time_limit' => $timeLimit,
            'data_limit' => $dataLimit,
            'comment' => $_POST['comment'] ?? '',
            'color' => $_POST['color'] ?? 'bg-blue-500',
        ];

        $qpModel = new QuickPrintModel;
        $qpModel->add($data);

        FlashHelper::set('success', 'toasts.package_saved', 'toasts.package_saved_desc', [], true);
        header('Location: /'.$session.'/quick-print/manage');
        exit;
    }

    // CRUD: Update
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $session = $_POST['session'] ?? '';
        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            FlashHelper::set('error', 'common.error', 'toasts.error_missing_id', [], true);
            header('Location: /'.$session.'/quick-print/manage');
            exit;
        }

        // Build time_limit (e.g. "1d2h30m") and data_limit (bytes) from split fields
        $timelimit_d = $_POST['timelimit_d'] ?? '';
        $timelimit_h = $_POST['timelimit_h'] ?? '';
        $timelimit_m = $_POST['timelimit_m'] ?? '';
        $timeLimit = '';
        if ($timelimit_d !== '') { $timeLimit .= $timelimit_d.'d'; }
        if ($timelimit_h !== '') { $timeLimit .= $timelimit_h.'h'; }
        if ($timelimit_m !== '') { $timeLimit .= $timelimit_m.'m'; }

        $datalimit_val = $_POST['datalimit_val'] ?? '';
        $datalimit_unit = $_POST['datalimit_unit'] ?? 'MB';
        $dataLimit = 0; // bytes
        if ($datalimit_val !== '' && is_numeric($datalimit_val)) {
            $dataLimit = (int) round((float) $datalimit_val * (($datalimit_unit === 'GB') ? 1073741824 : 1048576));
        }

        $qpModel = new QuickPrintModel;

        // Field limit/selling sudah tidak ada di form (arsitektur baru):
        // pertahankan nilai lama agar tidak terhapus saat edit.
        $existing = $qpModel->getById($id);
        if ($timeLimit === '') {
            $timeLimit = $existing['time_limit'] ?? '';
        }
        if ($dataLimit === 0) {
            $dataLimit = intval($existing['data_limit'] ?? 0);
        }

        $data = [
            'name' => $_POST['name'] ?? 'Package',
            'profile' => $_POST['profile'] ?? 'default',
            'prefix' => $_POST['prefix'] ?? '',
            'char_length' => $_POST['char_length'] ?? 4,
            'price' => $_POST['price'] ?? 0,
            'selling_price' => $_POST['selling_price'] ?? ($existing['selling_price'] ?? ($_POST['price'] ?? 0)),
            'time_limit' => $timeLimit,
            'data_limit' => $dataLimit,
            'comment' => $_POST['comment'] ?? '',
            'color' => $_POST['color'] ?? 'bg-blue-500',
        ];

        $qpModel->update($id, $data);

        FlashHelper::set('success', 'toasts.package_updated', 'toasts.package_updated_desc', [], true);
        header('Location: /'.$session.'/quick-print/manage');
        exit;
    }

    // CRUD: Delete
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        $session = $_POST['session'] ?? '';
        $id = $_POST['id'] ?? '';

        $qpModel = new QuickPrintModel;
        $qpModel->delete($id);

        FlashHelper::set('success', 'toasts.package_deleted', 'toasts.package_deleted_desc', [], true);
        header('Location: /'.$session.'/quick-print/manage');
        exit;
    }

    // ACTION: Generate User & Print
    public function printPacket($session, $id)
    {
        \App\Services\RouterListCache::flushSession(isset($session) ? $session : ($_POST['session'] ?? ''));
        // 1. Get Package Details
        $qpModel = new QuickPrintModel;
        $package = $qpModel->getById($id);

        if (! $package) {
            exit('Package not found');
        }

        // 2. Generate Credentials
        $prefix = $package['prefix'];
        $length = $package['char_length'];
        $charSet = '1234567890abcdefghijklmnopqrstuvwxyz'; // Simple lowercase + num
        $rand = substr(str_shuffle($charSet), 0, $length);
        $username = $prefix.$rand;
        $password = $username; // Default: user=pass (User Mode) - Can be improved later

        // 3. Connect to Mikrotik & Add User
        $configModel = new Config;
        $creds = $configModel->getSession($session);
        if (! $creds) {
            exit('Session error');
        }

        $API = RouterOSAPI::fromSession($creds);
        $API->attempts = 1;
        $API->delay = 0;
        $password_router = $creds['password'];
        if (isset($creds['source']) && $creds['source'] === 'legacy') {
            $password_router = RouterOSAPI::decrypt($password_router);
        }

        if ($API->connect($creds['ip'], $creds['user'], $password_router)) {
            // Build a report-friendly comment: p:{price} [QP] {date}
            // This ensures the Selling Report and Resume Report can detect price and date.
            $price = intval($package['price'] ?? 0);
            $dateStr = date('Y-m-d');
            $timeStr = date('H:i');
            $qpComment = 'p:'.$price.' [QP] '.$dateStr.' '.$timeStr;
            // Append original package comment if set
            if (! empty($package['comment'])) {
                $qpComment .= ' '.$package['comment'];
            }

            $userData = [
                'name' => $username,
                'password' => $password,
                'profile' => $package['profile'],
                'comment' => $qpComment, // Report-friendly comment
            ];

            // Effective uptime: override package -> Validity Data Plan.
            // Validate: package.profile HARUS ada di router. Jika sudah dihapus dari
            // Data Plans, abort print — operator tidak boleh create voucher orphan.
            $planValidityRaw = '';
            $profileExists = false;
            $plans = $API->comm('/ip/hotspot/user/profile/print');
            if (is_array($plans)) {
                foreach ($plans as $rp) {
                    if (($rp['name'] ?? '') === $package['profile']) {
                        $profileExists = true;
                        $planMeta = \App\Helpers\HotspotHelper::parseProfileMetadata($rp['on-login'] ?? '');
                        $planValidityRaw = $planMeta['validity_raw'] ?? '';
                        break;
                    }
                }
            }
            if (! $profileExists) {
                $API->disconnect();
                FlashHelper::set('error', 'quick_print.profile_missing', 'quick_print.profile_missing_desc', ['profile' => $package['profile']], true);
                header('Location: /'.$session.'/quick-print');
                exit;
            }
            $effectiveUptime = ! empty($package['time_limit']) ? $package['time_limit'] : $planValidityRaw;

            // Limits
            if (! empty($effectiveUptime)) {
                $userData['limit-uptime'] = $effectiveUptime;
            }
            if (! empty($package['data_limit'])) {
                // data_limit is stored as bytes
                $userData['limit-bytes-total'] = intval($package['data_limit']);
            }

            $API->comm('/ip/hotspot/user/add', $userData);
            $API->disconnect();
        } else {
            FlashHelper::set('error', 'Connection Failed', 'Could not connect to router at '.$creds['ip']);
            header('Location: '.($_SERVER['HTTP_REFERER'] ?? '/'.$session.'/quick-print/manage'));
            exit;
        }

        // 4. Render Template
        $tplModel = new VoucherTemplateModel;
        $templates = $tplModel->getAll();

        $currentTemplate = $_GET['template'] ?? 'default';
        $templateContent = '';
        $viewName = 'print/default';

        if ($currentTemplate !== 'default') {
            $tpl = $tplModel->getById($currentTemplate);
            if ($tpl) {
                $templateContent = $tpl['content'];
                $viewName = 'print/custom';
            } else {
                $currentTemplate = 'default';
            }
        }

        // data_limit is stored as bytes
        $bytes = intval($package['data_limit']);

        $userDataValues = [
            'username' => $username,
            'password' => $password,
            'price' => $package['price'],
            'validity' => $effectiveUptime,
            'timelimit' => HotspotHelper::formatValidity($effectiveUptime),
            'datalimit' => HotspotHelper::formatBytes($bytes),
            'profile' => $package['profile'],
            'comment' => 'Quick Print',
            'hotspotname' => $creds['hotspot_name'],
            'dns_name' => $creds['dns_name'],
            'login_url' => (preg_match('~^(?:f|ht)tps?://~i', $creds['dns_name']) ? $creds['dns_name'] : 'http://'.$creds['dns_name']).'/login',
        ];

        // --- Logo Handling ---
        $logoModel = new Logo;
        $logos = $logoModel->getAll();
        $logoMap = [];
        foreach ($logos as $l) {
            $logoMap[$l['id']] = $l['path'];
        }

        $data = [
            'users' => [$userDataValues],
            'templates' => $templates,
            'currentTemplate' => $currentTemplate,
            'templateContent' => $templateContent,
            'session' => $session,
            'logoMap' => $logoMap,
        ];

        return $this->view($viewName, $data);
    }
}
