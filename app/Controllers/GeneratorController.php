<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\FlashHelper;
use App\Libraries\RouterOSAPI;
use App\Models\Config;

class GeneratorController extends Controller
{
    public function index($session)
    {
        $configModel = new Config;
        $creds = $configModel->getSession($session);

        if (! $creds) {
            $this->redirect('/');

            return;
        }

        $API = RouterOSAPI::fromSession($creds);
        $API->attempts = 1;
        $API->delay = 0;
        if ($API->connect($creds['ip'], $creds['user'], $creds['password'])) {
            // Fetch Profiles for Dropdown
            $profiles = $API->comm('/ip/hotspot/user/profile/print');
            // Fetch Hotspot Servers
            $servers = $API->comm('/ip/hotspot/print');
            $API->disconnect();

            // Packages (sumber harga & profile untuk voucher)
            $qpModel = new \App\Models\QuickPrintModel;
            $packages = $qpModel->getAllByRouterId((int) $creds['id']);

            $data = [
                'session' => $session,
                'title' => 'Generate Vouchers - '.$session,
                'profiles' => $profiles,
                'servers' => $servers,
                'packages' => $packages,
            ];

            $this->view('hotspot/generate', $data);
        } else {
            FlashHelper::set('error', 'Connection Failed', 'Could not connect to router at '.$creds['ip']);
            header('Location: '.($_SERVER['HTTP_REFERER'] ?? '/'.$session.'/dashboard'));
            exit;
        }
    }

    public function process()
    {
        \App\Services\RouterListCache::flushSession(isset($session) ? $session : ($_POST['session'] ?? ''));
        $session = $_POST['session'] ?? '';
        $qty = intval($_POST['qty'] ?? 1);
        // SECURITY (CWE-770): batasi ukuran batch — tanpa cap, satu request
        // bisa membanjiri router/worker dengan ribuan user.
        $qty = min(max($qty, 1), 500);
        $server = $_POST['server'] ?? 'all';
        $userMode = $_POST['userModel'] ?? 'up';
        $userLength = intval($_POST['userLength'] ?? 4);
        // SECURITY (CWE-338): kode pendek + PRNG lemah = tebak-tebakan gratis
        // lewat check API publik. Panjang minimal dipaksa di sisi server.
        $userLength = min(max($userLength, 10), 32);
        $prefix = $_POST['prefix'] ?? '';
        $char = $_POST['char'] ?? 'mix';
        $profile = $_POST['profile'] ?? '';
        $comment = $_POST['comment'] ?? '';

        // Resolusi Package: profile + harga (SSOT pricing)
        $packageId = intval($_POST['package'] ?? 0);
        $pkgPrice = 0;
        if ($packageId > 0) {
            $qp = (new \App\Models\QuickPrintModel)->getById($packageId);
            if ($qp) {
                if (! empty($qp['profile'])) {
                    $profile = $qp['profile'];
                }
                $pkgPrice = intval($qp['price'] ?? 0);
            }
        }

        // Time Limit Logic (d, h, m)
        $timelimit_d = $_POST['timelimit_d'] ?? '';
        $timelimit_h = $_POST['timelimit_h'] ?? '';
        $timelimit_m = $_POST['timelimit_m'] ?? '';

        $timeLimit = '';
        if ($timelimit_d != '') {
            $timeLimit .= $timelimit_d.'d';
        }
        if ($timelimit_h != '') {
            $timeLimit .= $timelimit_h.'h';
        }
        if ($timelimit_m != '') {
            $timeLimit .= $timelimit_m.'m';
        }

        // Data Limit Logic (Value, Unit)
        $datalimit_val = $_POST['datalimit_val'] ?? '';
        $datalimit_unit = $_POST['datalimit_unit'] ?? 'MB';

        $dataLimit = '';
        if (! empty($datalimit_val) && is_numeric($datalimit_val)) {
            $bytes = (float) $datalimit_val;
            if ($datalimit_unit === 'GB') {
                $bytes = $bytes * 1073741824;
            } else {
                // MB
                $bytes = $bytes * 1048576;
            }
            $dataLimit = (string) round($bytes);
        }

        if (! $session || $qty < 1 || ! $profile) {
            $this->back($session);

            return;
        }

        $configModel = new Config;
        $creds = $configModel->getSession($session);
        if (! $creds) {
            $this->redirect('/');

            return;
        }

        $API = RouterOSAPI::fromSession($creds);
        $API->attempts = 1;
        $API->delay = 0;
        if ($API->connect($creds['ip'], $creds['user'], $creds['password'])) {

            // Validity default dari Data Plan terpilih (bila form Time Limit kosong)
            $planValidity = '';
            $routerPlans = $API->comm('/ip/hotspot/user/profile/print');
            if (is_array($routerPlans)) {
                foreach ($routerPlans as $rp) {
                    if (($rp['name'] ?? '') === $profile) {
                        $planMeta = \App\Helpers\HotspotHelper::parseProfileMetadata($rp['on-login'] ?? '');
                        $planValidity = $planMeta['validity_raw'] ?? '';
                        break;
                    }
                }
            }

                        // Format Comment: prefix-rand-date- comment
            // Example: up-123-12.01.26- premium
            $commentPrefix = ($userMode === 'vc') ? 'vc-' : 'up-';
            $batchId = random_int(100, 999);
            $date = date('m.d.y');
            $time = date('H:i');
            $commentBody = $comment ?: $profile;
            // Format: prefix-batchId-m.d.yy H:i- body  (jam utk Sales Report datetime)
            $priceTag = $pkgPrice > 0 ? 'p:'.$pkgPrice.' ' : '';
            $finalComment = "{$commentPrefix}{$batchId}-{$date} {$time}- {$priceTag}{$commentBody}";

            $created = 0;
            $failed = 0;
            for ($i = 0; $i < $qty; $i++) {
                $username = $prefix.$this->generateRandomString($userLength, $char);
                $password = $username;

                if ($userMode === 'up') {
                    $password = $this->generateRandomString($userLength, $char);
                }

                $user = [
                    'server' => $server,
                    'profile' => $profile,
                    'name' => $username,
                    'password' => $password,
                    'comment' => $finalComment,
                ];

                if (! empty($timeLimit)) {
                    $user['limit-uptime'] = $timeLimit;
                } elseif (! empty($planValidity)) {
                    $user['limit-uptime'] = $planValidity;
                }
                if (! empty($dataLimit)) {
                    $user['limit-bytes-total'] = $dataLimit;
                }
                $result = $API->comm('/ip/hotspot/user/add', $user);

                // SECURITY/UX (CWE-754): kegagalan per-item tidak boleh senyap —
                // operator sebelumnya mengira qty voucher dibuat semuanya.
                if ($result === false || (is_array($result) && isset($result['!trap']))) {
                    $failed++;
                } else {
                    $created++;
                    // Persistent denormalized snapshot ke sales_records.
                    // Source of truth untuk Sales Report — tidak hilang walau
                    // voucher dihapus dari MikroTik (soft-delete on remove).
                    try {
                        (new \App\Models\SalesRecordModel)->insert([
                            'router_id' => (int) ($creds['id'] ?? 0),
                            'voucher_name' => $username,
                            'voucher_password' => $password,
                            'profile_name' => $profile,
                            'profile_price' => $pkgPrice,
                            'server' => $server,
                            'comment' => $finalComment,
                            'sale_type' => 'bulk_generate',
                            'price' => $pkgPrice,
                            'billable' => $pkgPrice > 0,
                            'datetime' => date('Y-m-d H:i:s'),
                        ]);
                    } catch (\Throwable $e) {
                        // Jangan gagalkan create hanya karena sales insert error
                    }
                }
            }
            $API->disconnect();
        }

        if ($failed > 0) {
            FlashHelper::set('warning', 'toasts.vouchers_partial', 'toasts.vouchers_partial_desc',
                ['created' => $created, 'failed' => $failed], true);
        } else {
            FlashHelper::set('success', 'toasts.vouchers_generated', 'toasts.vouchers_generated_desc', ['qty' => $created], true);
        }
        $this->redirect('/'.$session.'/hotspot/users');
    }

    private function generateRandomString($length, $charType)
    {
        $characters = '';
        switch ($charType) {
            case 'lower':
                $characters = 'abcdefghijklmnopqrstuvwxyz';
                break;
            case 'upper':
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'number':
                $characters = '0123456789';
                break;
            case 'uppernumber':
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                break;
            case 'lowernumber':
                $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
                break;
            case 'mix':
            default:
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                break;
        }

        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            // SECURITY (CWE-338): CSPRNG wajib — rand() dapat diprediksi.
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $randomString;
    }

    private function back($session)
    {
        $this->redirect('/'.$session.'/hotspot/generate');
    }
}
