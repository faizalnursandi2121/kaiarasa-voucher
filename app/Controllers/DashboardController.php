<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\RouterOSAPI;
use App\Models\Config;
use App\Services\ActivitySnapshotService;
use App\Services\SalesReportService;

class DashboardController extends Controller
{
    /**
     * Operational control panel untuk satu lokasi/router.
     * Target: receptionist/operator — bukan monitoring teknis router
     * (itu tanggung jawab Home/NOC).
     */
    public function index($session)
    {
        $configModel = new Config;
        $creds = $configModel->getSession($session);

        if (! $creds) {
            echo 'Session not found.';

            return;
        }

        $force = isset($_GET['refresh']);
        $demo = $session === 'demo';
        $salesSvc = new SalesReportService($session);
        $snapSvc = new ActivitySnapshotService($session);

        // ---------- Active Users (live count + sampler) ----------
        $unreachable = false;
        $credIssue = trim((string) ($creds['password'] ?? '')) === '';
        $activeUsers = null;

        if ($demo) {
            $activeUsers = 25;
            $this->seedDemoSnapshots();
        } else {
            $snapSvc->sampleIfStale();
            $latest = $snapSvc->getLatestSnapshot();
            if ($latest !== null) {
                $activeUsers = (int) $latest['active_users'];
            } else {
                $live = $snapSvc->countActiveNow();
                if ($live === null) {
                    $unreachable = true;
                } else {
                    $snapSvc->recordNow($live);
                    $activeUsers = $live;
                }
            }
        }

        // ---------- Records penjualan/issuance ----------
        if ($demo) {
            $raw = SalesReportService::demoRaw();
            $records = array_map(
                fn ($u) => SalesReportService::normalizeUser($u, $raw['price_map']),
                $raw['users']
            );
        } else {
            $records = $salesSvc->getVoucherRecords($force);
            if (isset($records['__unreachable'])) {
                $records = [];
                $unreachable = true;
            }
        }

        // ---------- KPI ----------
        $today = date('Y-m-d');
        $soldToday = 0;
        $revenueToday = 0;
        $createdStats = $this->getCreatedStats($records);

        foreach ($records as $rec) {
            if (($rec['date'] ?? null) !== $today || empty($rec['billable'])) {
                continue;
            }
            if ($rec['price'] > 0) {
                $soldToday++;
                $revenueToday += $rec['price'];
            }
        }

        // ---------- Charts ----------
        $uaMode = in_array($_GET['ua'] ?? '', ['today', '7d', '30d'], true) ? $_GET['ua'] : 'today';
        $uaSeries = $unreachable
            ? []
            : $snapSvc->getSeries($uaMode === 'today' ? 'today' : 'days', $uaMode === '30d' ? 30 : 7);

        $data = [
            'session' => $session,
            'today_sales' => ['sold' => $soldToday, 'revenue' => $revenueToday],
            'kpis' => [
                'active_users' => $activeUsers,
                'sold_today' => $soldToday,
                'revenue_today' => $revenueToday,
                'created_today' => $createdStats['today'],
            ],
            'quick_actions' => [
                ['label' => 'Quick Print', 'icon' => 'printer', 'href' => '/'.htmlspecialchars($session).'/quick-print'],
                ['label' => 'Generate Vouchers', 'icon' => 'ticket-plus', 'href' => '/'.htmlspecialchars($session).'/hotspot/generate'],
            ],
            'activity' => $unreachable ? [] : $this->getActivityFeed($records, $session),
            'charts' => [
                'user_activity' => ['mode' => $uaMode, 'series' => $uaSeries],
                'voucher_activity' => [
                    'today' => $createdStats['today'],
                    'yesterday' => $createdStats['yesterday'],
                    'daily' => array_slice($createdStats['daily'], -30),
                ],
                'top_packages' => array_slice(
                    array_map(fn ($name, $count) => ['name' => $name, 'count' => $count],
                        array_keys($createdStats['top_packages']), $createdStats['top_packages']),
                    0, 5),
            ],
            'unreachable' => $unreachable,
            'cred_issue' => $credIssue,
            'ua_mode' => $uaMode,
        ];

        return $this->view('dashboard', $data);
    }

    /** Agregasi voucher dibuat hari ini/kemarin + distribusi harian 30 hari. */
    private function getCreatedStats(array $records): array
    {
        $today = date('Y-m-d');
        $yest = date('Y-m-d', strtotime('-1 day'));
        $todayN = 0; $yestN = 0; $byPkg = []; $byDate = [];

        foreach ($records as $r) {
            // Hanya issuance types; manual non-billable tidak dihitung
            if (! in_array($r['sale_type'], ['bulk_generate', 'quick_print'], true)) {
                continue;
            }
            $d = $r['date'] ?? null;
            if ($d === null) {
                continue;
            }
            $byDate[$d] = ($byDate[$d] ?? 0) + 1;
            if ($d === $today) {
                $todayN++;
                $byPkg[$r['profile']] = ($byPkg[$r['profile']] ?? 0) + 1;
            }
            if ($d === $yest) {
                $yestN++;
            }
        }
        arsort($byPkg);

        $daily = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} day"));
            $daily[] = ['date' => $d, 'count' => ($byDate[$d] ?? 0)];
        }

        return ['today' => $todayN, 'yesterday' => $yestN, 'top_packages' => $byPkg, 'daily' => $daily];
    }

    /**
     * Operational activity feed (best-effort):
     * creation events hari ini + connection events dari RouterOS hotspot log.
     */
    private function getActivityFeed(array $records, string $session): array
    {
        $items = [];
        foreach ($records as $r) {
            if (($r['date'] ?? null) !== date('Y-m-d')) {
                continue;
            }
            if ($r['sale_type'] === 'quick_print') {
                $items[] = ['icon' => 'printer', 'text' => 'Voucher printed', 'detail' => $r['profile']];
            } elseif ($r['sale_type'] === 'bulk_generate') {
                $items[] = ['icon' => 'ticket-plus', 'text' => 'Voucher generated', 'detail' => $r['profile']];
            } elseif (! empty($r['billable'])) {
                $items[] = ['icon' => 'user-plus', 'text' => 'User account created', 'detail' => $r['profile']];
            }
        }

        // Connection events dari hotspot log (best-effort)
        try {
            $config = (new Config)->getSession($session);
            if ($config) {
                $api = RouterOSAPI::fromSession($config);
                $api->attempts = 1;
                $api->timeout = 3;
                if ($api->connect($config['ip_address'], $config['username'], $config['password'])) {
                    $logs = $api->comm('/log/print', ['?topics' => 'hotspot,info,debug']);
                    if (empty($logs) || isset($logs['!trap'])) {
                        $logs = $api->comm('/log/print', []);
                    }
                    $api->disconnect();

                    if (is_array($logs)) {
                        foreach (array_reverse($logs) as $log) {
                            $msg = (string) ($log['message'] ?? '');
                            if (stripos($msg, 'logged in') !== false) {
                                $items[] = ['icon' => 'log-in', 'text' => 'User connected', 'detail' => $msg];
                            } elseif (stripos($msg, 'logged out') !== false) {
                                $items[] = ['icon' => 'log-out', 'text' => 'User disconnected', 'detail' => $msg];
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // log feed bersifat best-effort
        }

        return array_slice($items, 0, 12);
    }

    /** Seed intraday snapshots sintetis utk session demo (sekali, bila kosong). */
    private function seedDemoSnapshots(): void
    {
        $svc = new ActivitySnapshotService('demo');
        if ($svc->getLatestSnapshot() !== null) {
            return;
        }

        $values = [18, 20, 22, 21, 24, 26, 25, 27, 28, 30, 29, 28];
        $now = time();
        foreach ($values as $i => $v) {
            $ts = $now - ((count($values) - $i) * 300);
            \App\Core\Database::getInstance()->getConnection()
                ->prepare("INSERT INTO session_activity_snapshots (session_name, active_users, recorded_at)
                           VALUES ('demo', ?, datetime(?, 'unixepoch','localtime'))")
                ->execute([$v, $ts]);
        }
    }
}
