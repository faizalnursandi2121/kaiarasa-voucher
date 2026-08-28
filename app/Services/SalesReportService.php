<?php

namespace App\Services;

use App\Helpers\EncryptionHelper;
use App\Libraries\RouterOSAPI;

/**
 * SalesReportService — satu sumber kalkulasi Sold/Revenue (issuance = sale).
 *
 * Semantika (domain decision 2026-08-24):
 * - Bulk Generate & Quick Print = issuance/sale saat voucher diterbitkan.
 * - Used = metrik pemakaian TERPISAH, tidak mempengaruhi sold/revenue.
 * - manual_user hanya billable bila ada harga eksplisit di comment.
 * - Undated records: keluar dari agregasi BER-RANGE (konsistensi dashboard),
 *   masuk total hanya pada view all-time; selalu dilaporkan meta.undated_count.
 */
class SalesReportService
{
    /** Deteksi sale type dari comment voucher. */
    public static function detectSaleType(string $comment): string
    {
        if (strpos($comment, '[QP]') !== false) {
            return 'quick_print';
        }
        if (preg_match('/^(vc|up)-/', $comment)) {
            return 'bulk_generate';
        }

        return 'manual_user';
    }

    /** Parse tanggal dari comment (legacy format support). Null bila tidak ada. */
    public static function parseDate(string $comment): ?string
    {
        return self::parseDateTime($comment)['date'];
    }

    /** Parse tanggal (+ jam opsional "H:i" tepat setelah tanggal) dari comment. */
    public static function parseDateTime(string $comment): array
    {
        if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $comment, $m, PREG_OFFSET_CAPTURE)) {
            $date = sprintf('%04d-%02d-%02d', $m[1][0], $m[2][0], $m[3][0]);
            $time = self::extractTimeAfter($comment, $m[3][1] + strlen($m[3][0]));

            return ['date' => $date, 'time' => $time];
        }
        if (preg_match('/\b(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2,4})\b/', $comment, $m, PREG_OFFSET_CAPTURE)) {
            $p1 = intval($m[1][0]); $p2 = intval($m[2][0]); $y = intval($m[3][0]);
            $y = $y < 100 ? $y + 2000 : $y;
            // Disambiguasi aman: bila komponen kedua > 12 berarti format m.d.y;
            // selain itu (ambigu) ikuti konvensi bisnis Indonesia d/m/y.
            if ($p1 > 12) {
                $date = sprintf('%04d-%02d-%02d', $y, $p2, $p1);
            } elseif ($p2 > 12) {
                $date = sprintf('%04d-%02d-%02d', $y, $p1, $p2);
            } else {
                $date = sprintf('%04d-%02d-%02d', $y, $p2, $p1);
            }
            $time = self::extractTimeAfter($comment, $m[3][1] + strlen($m[3][0]));

            return ['date' => $date, 'time' => $time];
        }

        return ['date' => null, 'time' => null];
    }

    private static function extractTimeAfter(string $comment, int $offset): ?string
    {
        $tail = substr($comment, $offset);
        if (preg_match('/^\s+(\d{1,2}:\d{2})\b/', $tail, $tm)) {
            return substr('0'.$tm[1], -5); // normalisasi H:MM -> HH:MM
        }

        return null;
    }

    /** Harga: comment override -> profile map -> notasi K pada nama profile. */
    public static function detectPrice(array $user, array $profileMap): int
    {
        $comment = $user['comment'] ?? '';
        if (preg_match('/\b(?:p|price|harga)\s*[:-]?\s*(\d+)/i', $comment, $m)) {
            return intval($m[1]);
        }
        $profile = $user['profile'] ?? 'default';
        if (isset($profileMap[$profile])) {
            return intval($profileMap[$profile]);
        }
        if (preg_match('/(\d+)\s*k\b/i', $profile, $m)) {
            return intval($m[1]) * 1000;
        }
        if (preg_match('/k\s*(\d+)\b/i', $profile, $m)) {
            return intval($m[1]) * 1000;
        }

        return 0;
    }

    public static function hasExplicitPriceMarker(string $comment): bool
    {
        return (bool) preg_match('/\b(?:p|price|harga)\s*[:-]?\s*(\d+)/i', $comment);
    }

    /** Normalisasi satu baris user RouterOS menjadi record penjualan. */
    public static function normalizeUser(array $user, array $profilePriceMap): array
    {
        $comment = (string) ($user['comment'] ?? '');
        $saleType = self::detectSaleType($comment);
        $price = self::detectPrice($user, $profilePriceMap);

        $explicit = self::hasExplicitPriceMarker($comment);
        $billable = $saleType !== 'manual_user'
            ? true
            : ($explicit && $price > 0);

        $uptime = (string) ($user['uptime'] ?? '0s');
        $bytesOut = $user['bytes-out'] ?? 0;

        return [
            'name' => (string) ($user['name'] ?? ''),
            'password' => (string) ($user['password'] ?? ''),
            'profile' => (string) ($user['profile'] ?? 'default'),
            'server' => (string) ($user['server'] ?? 'all'),
            'price' => $price,
            'comment' => $comment,
            'sale_type' => $saleType,
            'billable' => $billable,
            'datetime' => (($dp = self::parseDateTime($comment))),
            'date' => $dp['date'],
            'time' => $dp['time'],
            'used' => ($uptime !== '' && $uptime !== '0s') || (is_numeric($bytesOut) && $bytesOut > 0),
            'uptime' => $uptime,
            'deleted_at' => (string) ($user['deleted_at'] ?? ''),
            'sold_at' => (string) ($user['sold_at'] ?? ''),
        ];
    }


    /**
     * MURNI (tanpa I/O): agregasi records hasil normalizeUser.
     *
     * Filter: start,end (Y-m-d, hanya menyaring record bertanggal),
     *         package (nama profile), sale_type.
     *
     * Undated billable: DIKECUALIKAN dari agregasi ber-range agar
     * Dashboard "Today" ≡ Sales Report "Today" by construction;
     * masuk total hanya pada view all-time. Dilaporkan meta.undated_count,
     * plus summary.sold_dated/revenue_dated untuk KPI dashboard.
     */
    public static function computeFromRecords(array $records, array $f = []): array
    {
        $start = $f['start'] ?? null;
        $end = $f['end'] ?? null;
        $pkg = $f['package'] ?? null;
        $stype = $f['sale_type'] ?? null;
        $server = $f['server'] ?? null;

        $summary = ['revenue' => 0, 'vouchers_sold' => 0, 'avg_sale' => null, 'top_package' => null,
            'issued' => 0, 'used' => 0, 'unused' => 0, 'undated' => 0,
            'sold_dated' => 0, 'revenue_dated' => 0];
        $byType = [
            'bulk_generate' => ['count' => 0, 'revenue' => 0],
            'quick_print' => ['count' => 0, 'revenue' => 0],
            'manual_user' => ['count' => 0, 'revenue' => 0],
        ];
        $pkgAgg = [];
        $serverAgg = [];
        $daily = [];
        $list = [];
        $undatedCount = 0;
        $totalRecords = 0;

        foreach ($records as $rec) {
            if ($pkg !== null && $rec['profile'] !== $pkg) {
                continue;
            }
            if ($stype !== null && $rec['sale_type'] !== $stype) {
                continue;
            }
            if ($server !== null && ($rec['server'] ?? '') !== $server) {
                continue;
            }
            $totalRecords++;

            $isIssuance = in_array($rec['sale_type'], ['bulk_generate', 'quick_print'], true);
            if ($isIssuance) {
                $summary['issued']++;
                if ($rec['used']) {
                    $summary['used']++;
                }
            }

            $sold = $rec['billable'] && $rec['price'] > 0;
            $isUndated = $rec['date'] === null;
            $rangeActive = ($start !== null || $end !== null);
            $inRange = ! $isUndated
                && ($start === null || $rec['date'] >= $start)
                && ($end === null || $rec['date'] <= $end);

            if ($sold && $isUndated) {
                $undatedCount++;
                if (! $rangeActive) {
                    $summary['revenue'] += $rec['price'];
                    $summary['vouchers_sold']++;
                }
                $byType[$rec['sale_type']]['count']++;
                $byType[$rec['sale_type']]['revenue'] += $rec['price'];
                if (! isset($pkgAgg[$rec['profile']])) {
                    $pkgAgg[$rec['profile']] = ['name' => $rec['profile'], 'count' => 0, 'revenue' => 0];
                }
                $pkgAgg[$rec['profile']]['count']++;
                $pkgAgg[$rec['profile']]['revenue'] += $rec['price'];
                $srv = $rec['server'] ?? '';
                if ($srv !== '' && ! isset($serverAgg[$srv])) {
                    $serverAgg[$srv] = ['name' => $srv, 'count' => 0, 'revenue' => 0];
                }
                if ($srv !== '') {
                    $serverAgg[$srv]['count']++;
                    $serverAgg[$srv]['revenue'] += $rec['price'];
                }
                continue;
            }

            if ($sold && ! $inRange) {
                continue;
            }

            if ($sold) {
                $summary['revenue'] += $rec['price'];
                $summary['vouchers_sold']++;
                $summary['sold_dated']++;
                $summary['revenue_dated'] += $rec['price'];
            }

            if (! $sold || ! $inRange) {
                continue;
            }

            $byType[$rec['sale_type']]['count']++;
            $byType[$rec['sale_type']]['revenue'] += $rec['price'];

            if (! isset($pkgAgg[$rec['profile']])) {
                $pkgAgg[$rec['profile']] = ['name' => $rec['profile'], 'count' => 0, 'revenue' => 0];
            }
            $pkgAgg[$rec['profile']]['count']++;
            $pkgAgg[$rec['profile']]['revenue'] += $rec['price'];

            $srv = $rec['server'] ?? '';
            if ($srv !== '' && ! isset($serverAgg[$srv])) {
                $serverAgg[$srv] = ['name' => $srv, 'count' => 0, 'revenue' => 0];
            }
            if ($srv !== '') {
                $serverAgg[$srv]['count']++;
                $serverAgg[$srv]['revenue'] += $rec['price'];
            }

            if (! isset($daily[$rec['date']])) {
                $daily[$rec['date']] = ['date' => $rec['date'], 'revenue' => 0, 'sold' => 0];
            }
            $daily[$rec['date']]['revenue'] += $rec['price'];
            $daily[$rec['date']]['sold']++;

            $refParts = explode(' ', trim($rec['comment']));
            $ref = $refParts[0] ?? '';

            $list[] = [
                'date' => $rec['date'],
                'time' => $rec['time'],
                'code' => $rec['name'],
                'package' => $rec['profile'],
                'server' => $srv,
                'sale_type' => $rec['sale_type'],
                'batch_id' => $ref,
                'price' => $rec['price'],
                'deleted_at' => (string) ($rec['deleted_at'] ?? ''),
            ];
        }

        $summary['unused'] = max($summary['issued'] - $summary['used'], 0);
        $summary['avg_sale'] = $summary['vouchers_sold'] > 0
            ? (int) round($summary['revenue'] / $summary['vouchers_sold']) : null;

        $byPackage = array_values($pkgAgg);
        usort($byPackage, fn ($a, $b) => $b['count'] <=> $a['count']);
        foreach ($byPackage as &$p) {
            $p['pct'] = $summary['vouchers_sold'] > 0
                ? round($p['count'] / $summary['vouchers_sold'] * 100, 1) : 0.0;
        }
        unset($p);
        if (isset($byPackage[0])) {
            $summary['top_package'] = ['name' => $byPackage[0]['name'], 'count' => $byPackage[0]['count']];
        }

        $byServer = array_values($serverAgg);
        usort($byServer, fn ($a, $b) => $b['count'] <=> $a['count']);

        $trend = array_values($daily);
        usort($trend, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $breakdown = array_map(fn ($d) => ['date' => $d['date'], 'vouchers' => $d['sold'], 'revenue' => $d['revenue']], $trend);

        usort($list, function ($a, $b) {
            $ad = $a['date'].($a['time'] ?? ''); $bd = $b['date'].($b['time'] ?? '');
            return [$bd, $a['code']] <=> [$ad, $b['code']];
        });

        ksort($byType);

        return [
            'filters' => ['start' => $start, 'end' => $end, 'package' => $pkg, 'sale_type' => $stype, 'server' => $server],
            'meta' => ['total_records' => $totalRecords, 'undated_count' => $undatedCount],
            'summary' => $summary,
            'by_type' => $byType,
            'by_package' => $byPackage,
            'by_server' => $byServer,
            'revenue_trend' => $trend,
            'sales_volume' => $trend,
            'daily_breakdown' => $breakdown,
            'list' => $list,
        ];
    }

    private string $session;

    public function __construct(string $session)
    {
        $this->session = $session;
    }

    private function cachePath(): string
    {
        return sys_get_temp_dir().'/mivo-sales-'.$this->session.'.json';
    }

    /** Ambil users + profile price map dari RouterOS. ['__unreachable'] bila gagal. */
    public function fetchRaw(): array
    {
        $configModel = new \App\Models\Config;
        $config = $configModel->getSession($this->session);
        if (! $config) {
            return ['__unreachable' => true];
        }
        // CATATAN: Config::getSession sudah mendekripsi password — jangan
        // dekripsi ulang; fail-closed akan mengembalikan null.

        try {
            $api = \App\Libraries\RouterOSAPI::fromSession($config);
            $api->attempts = 1;
            $api->timeout = 5;
            $api->delay = 0;
            if (! $api->connect($config['ip_address'], $config['username'], $config['password'])) {
                return ['__unreachable' => true];
            }
            $users = $api->comm('/ip/hotspot/user/print');
            $profiles = $api->comm('/ip/hotspot/user/profile/print');
            $api->disconnect();
        } catch (\Throwable $e) {
            return ['__unreachable' => true];
        }

        $map = [];
        foreach ((array) $profiles as $p) {
            $meta = \App\Helpers\HotspotHelper::parseProfileMetadata($p['on-login'] ?? '');
            if (! empty($meta['price'])) {
                $map[$p['name']] = intval($meta['price']);
            }
        }
        return $map;
    }
    /**
     * Ambil data dari sales_records (persistent local snapshot).
     * Tabel ini adalah SUMBER PRIMER — INSERT saat voucher dibuat
     * (Generator/QuickPrint/Add), soft-delete saat voucher dihapus.
     * Return shape compatible dengan normalizeUser.
     */
    private function fetchFromSalesTable(): array
    {
        $srModel = new \App\Models\SalesRecordModel;
        $routerId = $srModel->routerIdBySession($this->session);
        if (! $routerId) {
            return ['__no_router' => true];
        }

        // Include soft-deleted: sales report adalah financial record, immutable.
        // User delete voucher dari MikroTik TIDAK menghapus row di sini —
        // hanya flag deleted_at yang di-set (audit trail).
        $rows = $srModel->getByRouter($routerId, true);
        if (empty($rows)) {
            return ['__empty' => true, 'router_id' => $routerId];
        }
        // Convert sales_records row → shape yang diharapkan normalizeUser
        $users = [];
        foreach ($rows as $r) {
            $comment = (string) ($r['comment'] ?? '');
            $users[] = [
                'name' => (string) ($r['voucher_name'] ?? ''),
                'password' => (string) ($r['voucher_password'] ?? ''),
                'profile' => (string) ($r['profile_name'] ?? 'default'),
                'server' => (string) ($r['server'] ?? 'all'),
                'comment' => $comment,
                'uptime' => '0s',
                'bytes-out' => 0,
                'deleted_at' => (string) ($r['deleted_at'] ?? ''),
                'sold_at' => (string) ($r['sold_at'] ?? ''),
            ];
        }
        // price_map tidak diperlukan kalau setiap record sudah punya price (dari snapshot)
        $priceMap = [];
        return ['users' => $users, 'price_map' => $priceMap, 'source' => 'sales_table', 'router_id' => $routerId];
    }

    /**
     * First-time backfill: copy existing MikroTik users ke sales_records
     * supaya sales report punya historical data. Idempotent — dipanggil
     * sekali per router (heuristic: count=0).
     */
    private function maybeBackfillFromMikrotik(int $routerId): void
    {
        $srModel = new \App\Models\SalesRecordModel;
        if ($srModel->countByRouter($routerId) > 0) {
            return; // sudah ada data
        }
        $raw = $this->fetchRaw();
        if (isset($raw['__unreachable']) || empty($raw['users'])) {
            return;
        }
        foreach ($raw['users'] as $u) {
            $comment = (string) ($u['comment'] ?? '');
            $saleType = self::detectSaleType($comment);
            // Skip manual_user (live data, bukan historical sale)
            if ($saleType === 'manual_user') {
                continue;
            }
            $price = self::detectPrice($u, $raw['price_map']);
            $name = (string) ($u['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $dp = self::parseDateTime($comment);
            try {
                $srModel->insert([
                    'router_id' => $routerId,
                    'voucher_name' => $name,
                    'voucher_password' => (string) ($u['password'] ?? ''),
                    'profile_name' => (string) ($u['profile'] ?? 'default'),
                    'profile_price' => 0,
                    'server' => (string) ($u['server'] ?? 'all'),
                    'comment' => $comment,
                    'sale_type' => $saleType,
                    'price' => $price,
                    'billable' => $price > 0,
                    'datetime' => $dp['date'] ? $dp['date'].' '.$dp['time'] : '',
                ]);
            } catch (\Throwable $e) {
                // ignore duplicate / error
            }
        }
    }

    public function getVoucherRecords(bool $force = false): array
    {
        $file = $this->cachePath();
        if (! $force && is_file($file)) {
            $json = json_decode((string) file_get_contents($file), true);
            if (isset($json['ts']) && (time() - $json['ts']) < 300) {
                return $json['payload'];
            }
        }

        if ($this->session === 'demo') {
            $raw = self::demoRaw();
        } else {
            // Try sales_records first (persistent), fall back ke MikroTik.
            $raw = $this->fetchFromSalesTable();
            if (isset($raw['__empty']) && ! empty($raw['router_id'])) {
                // First-time: backfill dari MikroTik users.
                $this->maybeBackfillFromMikrotik((int) $raw['router_id']);
                $raw = $this->fetchFromSalesTable();
                if (isset($raw['__empty'])) {
                    $raw = $this->fetchRaw(); // fallback MikroTik live
                }
            }
            if (isset($raw['__no_router'])) {
                $raw = $this->fetchRaw(); // router tidak dikenal → MikroTik
            }
        }

        if (isset($raw['__unreachable'])) {
            $payload = ['__unreachable' => true];
        } elseif (empty($raw['users']) || ! is_array($raw['users'])) {
            $payload = [];
        } else {
            $payload = array_map(
                fn ($u) => self::normalizeUser($u, $raw['price_map'] ?? []),
                array_values($raw['users'])
            );
        }

        @file_put_contents($file, json_encode(['ts' => time(), 'payload' => $payload]));

        return $payload;
    }

    /** Entry point utama: records + agregasi terfilter. */
    public function getReport(array $filters = [], bool $force = false): array
    {
        $records = $this->getVoucherRecords($force);
        if (isset($records['__unreachable'])) {
            return ['__unreachable' => true];
        }

        return self::computeFromRecords($records, $filters);
    }

    /** Ringkasan hari-ini untuk Dashboard KPI (dated-only). */
    public function getTodaySummary(bool $force = false): array
    {
        $today = date('Y-m-d');
        $rep = $this->getReport(['start' => $today, 'end' => $today], $force);
        if (isset($rep['__unreachable'])) {
            return ['__unreachable' => true];
        }

        return ['sold' => $rep['summary']['sold_dated'], 'revenue' => $rep['summary']['revenue_dated']];
    }

    /** Fixture session demo — menguji jalur deteksi harga via comment override. */
    public static function demoRaw(): array
    {
        $today = date('Y-m-d');
        $yest = date('Y-m-d', strtotime('-1 day'));
        $users = [];
        for ($i = 0; $i < 8; $i++) {
            $t = sprintf('%02d:%02d', 8 + intdiv($i, 2), ($i % 2) * 30);
            $users[] = ['name' => 'demo-q'.$i, 'profile' => '1 Hour', 'price' => 0, 'server' => 'hotspot-1',
                'comment' => "p:3000 [QP] {$today} {$t}", 'uptime' => $i < 3 ? '45m' : '0s', 'bytes-out' => 0];
        }
        for ($i = 0; $i < 10; $i++) {
            $t = sprintf('%02d:%02d', 9 + intdiv($i, 3), ($i % 3) * 20);
            $users[] = ['name' => 'demo-g'.$i, 'profile' => '1 Day', 'price' => 0, 'server' => $i < 5 ? 'hotspot-1' : 'hotspot-2',
                'comment' => "vc-D1-{$today} {$t}- p:5000", 'uptime' => $i < 4 ? '2h' : '0s', 'bytes-out' => 0];
        }
        for ($i = 0; $i < 6; $i++) {
            $users[] = ['name' => 'demo-y'.$i, 'profile' => '3 Hours', 'price' => 0, 'server' => 'hotspot-1',
                'comment' => "vc-Y2-{$yest}- p:3500", 'uptime' => '1h', 'bytes-out' => 2048];
        }
        $users[] = ['name' => 'demo-nodate', 'profile' => '1 Day', 'price' => 0, 'server' => 'all',
            'comment' => '[QP]', 'uptime' => '0s', 'bytes-out' => 0];

        return ['users' => $users, 'price_map' => ['1 Hour' => 3000, '1 Day' => 5000, '3 Hours' => 3500]];
    }
}
