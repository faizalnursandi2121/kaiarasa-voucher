<?php

namespace App\Services;

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
        if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $comment, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        if (preg_match('/\b(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2,4})\b/', $comment, $m)) {
            $p1 = intval($m[1]); $p2 = intval($m[2]); $y = intval($m[3]);
            $y = $y < 100 ? $y + 2000 : $y;
            // Bisnis Indonesia: tanggal numerik ambigu dibaca DAY/MONTH/YEAR
            return sprintf('%04d-%02d-%02d', $y, $p2, $p1);
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
            'price' => $price,
            'comment' => $comment,
            'sale_type' => $saleType,
            'billable' => $billable,
            'date' => self::parseDate($comment),
            'used' => ($uptime !== '' && $uptime !== '0s') || (is_numeric($bytesOut) && $bytesOut > 0),
            'uptime' => $uptime,
        ];
    }

    // computeFromRecords & agregasi menyusul di Task 2.

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

        $summary = ['revenue' => 0, 'vouchers_sold' => 0, 'avg_sale' => null, 'top_package' => null,
            'issued' => 0, 'used' => 0, 'unused' => 0, 'undated' => 0,
            'sold_dated' => 0, 'revenue_dated' => 0];
        $byType = [
            'bulk_generate' => ['count' => 0, 'revenue' => 0],
            'quick_print' => ['count' => 0, 'revenue' => 0],
            'manual_user' => ['count' => 0, 'revenue' => 0],
        ];
        $pkgAgg = [];
        $daily = [];
        $batches = [];
        $undatedCount = 0;
        $totalRecords = 0;

        foreach ($records as $rec) {
            if ($pkg !== null && $rec['profile'] !== $pkg) {
                continue;
            }
            if ($stype !== null && $rec['sale_type'] !== $stype) {
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

            // Undated: keluar dari agregasi BER-RANGE (konsistensi dashboard);
            // masuk total hanya pada view all-time. Tipe & paket tetap tercatat
            // karena diketahui (hanya tanggalnya yang tidak ada).
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
                continue;
            }

            if ($sold && ! $inRange) {
                continue; // dated di luar rentang: eksklusi dari semua agregasi
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

            if (! isset($daily[$rec['date']])) {
                $daily[$rec['date']] = ['date' => $rec['date'], 'revenue' => 0, 'sold' => 0];
            }
            $daily[$rec['date']]['revenue'] += $rec['price'];
            $daily[$rec['date']]['sold']++;

            $refParts = explode(' ', trim($rec['comment']));
            $ref = $refParts[0] ?? '';
            $key = $rec['date'].'|'.$rec['sale_type'].'|'.$rec['profile'].'|'.$rec['price'].'|'.$ref;
            if (! isset($batches[$key])) {
                $batches[$key] = ['date' => $rec['date'], 'package' => $rec['profile'],
                    'quantity' => 0, 'unit_price' => $rec['price'], 'total' => 0,
                    'sale_type' => $rec['sale_type'], 'used_count' => 0, 'reference' => $ref];
            }
            $batches[$key]['quantity']++;
            $batches[$key]['total'] += $rec['price'];
            if ($rec['used']) {
                $batches[$key]['used_count']++;
            }
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

        $trend = array_values($daily);
        usort($trend, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $breakdown = array_map(fn ($d) => ['date' => $d['date'], 'vouchers' => $d['sold'], 'revenue' => $d['revenue']], $trend);

        $list = array_values($batches);
        usort($list, fn ($a, $b) => [$b['date'], $a['reference']] <=> [$a['date'], $b['reference']]);

        ksort($byType);

        return [
            'filters' => ['start' => $start, 'end' => $end, 'package' => $pkg, 'sale_type' => $stype],
            'meta' => ['total_records' => $totalRecords, 'undated_count' => $undatedCount],
            'summary' => $summary,
            'by_type' => $byType,
            'by_package' => $byPackage,
            'revenue_trend' => $trend,
            'sales_volume' => $trend,
            'daily_breakdown' => $breakdown,
            'list' => $list,
        ];
    }
}
