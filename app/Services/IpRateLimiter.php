<?php

namespace App\Services;

use App\Core\Database;

/**
 * Rate limiter generik per IP untuk endpoint publik (fixes vuln:
 * unauthenticated voucher telemetry disclosure with no rate limiting).
 *
 * Sliding window per "bucket" (nama endpoint). Tabel dibuat lazily.
 */
class IpRateLimiter
{
    /**
     * @param  string  $ip        IP klien (REMOTE_ADDR)
     * @param  string  $bucket    nama kelompok, mis. 'voucher_check'
     * @param  int     $max       maksimum permintaan
     * @param  int     $windowSec jendela waktu dalam detik
     */
    public static function tooManyRequests(string $ip, string $bucket, int $max = 30, int $windowSec = 60): bool
    {
        self::table();
        $db = Database::getInstance();
        $stmt = $db->query(
            'SELECT COUNT(*) FROM ip_rate WHERE ip = ? AND bucket = ? AND ts > ?',
            [$ip, $bucket, time() - $windowSec]
        );

        return (int) $stmt->fetchColumn() >= $max;
    }

    public static function hit(string $ip, string $bucket): void
    {
        self::table();
        Database::getInstance()->query(
            'INSERT INTO ip_rate (ip, bucket, ts) VALUES (?, ?, ?)',
            [$ip, $bucket, time()]
        );
        // Housekeeping ringan: buang catatan lebih tua dari 1 jam.
        if (random_int(1, 50) === 1) {
            Database::getInstance()->query('DELETE FROM ip_rate WHERE ts < ?', [time() - 3600]);
        }
    }

    private static function table(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        Database::getInstance()->query(
            'CREATE TABLE IF NOT EXISTS ip_rate (
                ip TEXT NOT NULL,
                bucket TEXT NOT NULL,
                ts INTEGER NOT NULL
            )'
        );
        $done = true;
    }
}
