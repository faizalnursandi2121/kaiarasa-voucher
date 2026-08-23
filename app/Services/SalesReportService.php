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
}
