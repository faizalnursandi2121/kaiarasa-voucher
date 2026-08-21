<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\HotspotHelper;
use App\Libraries\RouterOSAPI;
use App\Models\Config;

class ReportController extends Controller
{
    public function index($session)
    {
        $data = $this->getFinancialReportData($session);
        if (! $data) {
            header('Location: /');
            exit;
        }

        return $this->view('reports/financial', $data);
    }

    public function sellingExport($session, $type)
    {
        $data = $this->getFinancialReportData($session);
        if (! $data) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No data found']);
            exit;
        }

        // Export voucher details (flatten from batches)
        $report = $data['report'] ?? [];
        $exportData = [];

        foreach ($report as $row) {
            foreach ($row['vouchers'] ?? [] as $v) {
                $exportData[] = [
                    'Date' => $row['date'],
                    'Batch / Reference' => $row['reference'],
                    'Type' => $v['is_quickprint'] ? 'Quick Print' : 'Inventory',
                    'Voucher' => $v['username'],
                    'Profile' => $v['profile'],
                    'Price' => $v['price'],
                    'Status' => $v['status'],
                    'Uptime' => $v['uptime'],
                    'Comment' => $v['comment'],
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode($exportData);
        exit;
    }

    private function getFinancialReportData($session)
    {
        $configModel = new Config;
        $config = $configModel->getSession($session);

        if (! $config) {
            return null;
        }

        $API = RouterOSAPI::fromSession($config);
        $users = [];

        $profilePriceMap = [];
        if ($API->connect($config['ip_address'], $config['username'], $config['password'])) {
            $users = $API->comm('/ip/hotspot/user/print');
            $profiles = $API->comm('/ip/hotspot/user/profile/print');
            $API->disconnect();

            foreach ($profiles as $p) {
                $meta = HotspotHelper::parseProfileMetadata($p['on-login'] ?? '');
                if (! empty($meta['price'])) {
                    $profilePriceMap[$p['name']] = intval($meta['price']);
                }
            }
        }

        // --- BATCH REPORT (Tab 2) ---
        $report = [];
        
        // --- TIME SERIES (Tab 1) ---
        $daily = [];
        $monthly = [];
        $yearly = [];

        // --- SUMMARY METRICS ---
        $totalIncome = 0;          // Total Potential (All vouchers)
        $totalVouchers = 0;
        
        $realizedIncome = 0;      // Uang Masuk: Quick Print + Used Inventory
        $realizedVouchers = 0;
        
        $inventoryIncome = 0;     // Uang Tertunda: Inventory (Generated, not used)
        $inventoryVouchers = 0;

        $initPeriod = function () {
            return ['count' => 0, 'realized' => 0, 'inventory' => 0, 'total' => 0, 'realized_income' => 0, 'inventory_income' => 0];
        };

        foreach ($users as $user) {
            $price = $this->detectPrice($user, $profilePriceMap);
            if ($price <= 0) {
                continue;
            }

            $user['price'] = $price;
            $comment = $user['comment'] ?? '';

            // Determine Date and Reference/Comment
            $date = 'Unknown Date';
            $dateObj = null;
            $reference = $comment; // Default: use comment as reference

            if (! empty($comment)) {
                if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $comment, $m)) {
                    $date = $m[1].'-'.$m[2].'-'.$m[3];
                    $dateObj = (new \DateTime)->setDate($m[1], $m[2], $m[3]);
                } elseif (preg_match('/\b(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2,4})\b/', $comment, $m)) {
                    $p1 = intval($m[1]); $p2 = intval($m[2]); $p3 = intval($m[3]);
                    $year = $p3 < 100 ? $p3 + 2000 : $p3;
                    if ($p1 > 12) {
                        $date = sprintf('%04d-%02d-%02d', $year, $p2, $p1);
                        $dateObj = (new \DateTime)->setDate($year, $p2, $p1);
                    } else {
                        $date = sprintf('%04d-%02d-%02d', $year, $p1, $p2);
                        $dateObj = (new \DateTime)->setDate($year, $p1, $p2);
                    }
                } else {
                    $date = $comment;
                }
            }

            // Determine Voucher Type & Status
            $isQuickPrint = strpos($comment, '[QP]') !== false;
            $isUsed = (isset($user['uptime']) && $user['uptime'] != '0s') || (isset($user['bytes-out']) && $user['bytes-out'] > 0);

            // Financial Logic:
            // Quick Print = Direct Sale (Realized immediately)
            // Inventory (Generated) = Pending until Used. If Used -> Realized. If not -> Inventory.
            $status = 'In Stock';
            $isRealized = false;

            if ($isQuickPrint) {
                $status = 'Sold (Quick Print)';
                $isRealized = true;
            } elseif ($isUsed) {
                $status = 'Used (Inventory)';
                $isRealized = true;
            } else {
                $status = 'In Stock (Inventory)';
                $isRealized = false;
            }

            // Batch Report Data (Group by Date + Reference)
            $batchKey = $date.'|'.$reference;
            if (! isset($report[$batchKey])) {
                $report[$batchKey] = [
                    'date' => $date,
                    'reference' => $reference,
                    'count' => 0,
                    'total' => 0,
                    'realized_total' => 0,
                    'realized_count' => 0,
                    'vouchers' => [],
                ];
            }

            $report[$batchKey]['vouchers'][] = [
                'username' => $user['name'] ?? '',
                'password' => $user['password'] ?? '',
                'profile' => $user['profile'] ?? 'default',
                'price' => $price,
                'comment' => $comment,
                'is_quickprint' => $isQuickPrint,
                'status' => $status,
                'uptime' => $user['uptime'] ?? '0s',
            ];

            $report[$batchKey]['count']++;
            $report[$batchKey]['total'] += $price;
            $totalIncome += $price;
            $totalVouchers++;

            if ($isRealized) {
                $report[$batchKey]['realized_count']++;
                $report[$batchKey]['realized_total'] += $price;
                $realizedIncome += $price;
                $realizedVouchers++;
            } else {
                $inventoryIncome += $price;
                $inventoryVouchers++;
            }

            // Time Series Data
            if ($dateObj) {
                $dayKey = $dateObj->format('Y-m-d');
                $monthKey = $dateObj->format('Y-m');
                $yearKey = $dateObj->format('Y');
            } else {
                $dayKey = 'Unknown';
                $monthKey = 'Unknown';
                $yearKey = 'Unknown';
            }

            if (! isset($daily[$dayKey])) $daily[$dayKey] = $initPeriod();
            $daily[$dayKey]['count']++;
            $daily[$dayKey]['total'] += $price;
            if ($isRealized) {
                $daily[$dayKey]['realized']++;
                $daily[$dayKey]['realized_income'] += $price;
            } else {
                $daily[$dayKey]['inventory']++;
                $daily[$dayKey]['inventory_income'] += $price;
            }

            if (! isset($monthly[$monthKey])) $monthly[$monthKey] = $initPeriod();
            $monthly[$monthKey]['count']++;
            $monthly[$monthKey]['total'] += $price;
            if ($isRealized) {
                $monthly[$monthKey]['realized']++;
                $monthly[$monthKey]['realized_income'] += $price;
            } else {
                $monthly[$monthKey]['inventory']++;
                $monthly[$monthKey]['inventory_income'] += $price;
            }

            if (! isset($yearly[$yearKey])) $yearly[$yearKey] = $initPeriod();
            $yearly[$yearKey]['count']++;
            $yearly[$yearKey]['total'] += $price;
            if ($isRealized) {
                $yearly[$yearKey]['realized']++;
                $yearly[$yearKey]['realized_income'] += $price;
            } else {
                $yearly[$yearKey]['inventory']++;
                $yearly[$yearKey]['inventory_income'] += $price;
            }
        }

        // Calculate Batch Status
        foreach ($report as &$row) {
            if ($row['realized_count'] === 0) {
                $row['status'] = 'New';
            } elseif ($row['realized_count'] >= $row['count']) {
                $row['status'] = 'Sold Out';
            } else {
                $row['status'] = 'Selling';
            }
        }
        unset($row);

        krsort($report);
        ksort($daily);
        ksort($monthly);
        ksort($yearly);

        return [
            'session' => $session,
            'report' => $report,
            'daily' => $daily,
            'monthly' => $monthly,
            'yearly' => $yearly,
            'totalIncome' => $totalIncome,
            'totalVouchers' => $totalVouchers,
            'realizedIncome' => $realizedIncome,
            'realizedVouchers' => $realizedVouchers,
            'inventoryIncome' => $inventoryIncome,
            'inventoryVouchers' => $inventoryVouchers,
            'currency' => $config['currency'] ?? 'Rp',
        ];
    }

    /**
     * Smart Price Detection Logic
     */
    private function detectPrice($user, $profileMap)
    {
        $comment = $user['comment'] ?? '';

        // 1. Comment Override (p:5000, price:5000, price 5000, harga 5000)
        if (preg_match('/\b(?:p|price|harga)\s*[:-]?\s*(\d+)/i', $comment, $matches)) {
            return intval($matches[1]);
        }

        // 2. Profile Script
        $profile = $user['profile'] ?? 'default';
        if (isset($profileMap[$profile])) {
            return $profileMap[$profile];
        }

        // 3. Fallback: Parse Profile Name (Strict "K" notation only)
        if (preg_match('/(\d+)k\b/i', $profile, $m)) {
            return intval($m[1]) * 1000;
        }

        return 0;
    }
}
