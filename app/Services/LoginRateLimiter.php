<?php

namespace App\Services;

use App\Core\Database;

/**
 * Rate limiter untuk endpoint login (fixes vuln: no rate limiting/lockout).
 *
 * Sliding window berbasis SQLite — tabel dibuat lazily sehingga tidak ada
 * migrasi khusus. Kegagalan dicatat per IP+username; sukses menghapus catatan.
 */
class LoginRateLimiter
{
    private const MAX_FAILURES   = 5;     // percobaan gagal
    private const WINDOW_SECONDS = 900;   // dalam 15 menit

    public static function tooManyAttempts(string $ip, string $username): bool
    {
        self::table();
        $db = Database::getInstance();
        $stmt = $db->query(
            'SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND username = ? AND ts > ?',
            [$ip, mb_strtolower($username), time() - self::WINDOW_SECONDS]
        );

        return (int) $stmt->fetchColumn() >= self::MAX_FAILURES;
    }

    public static function recordFailure(string $ip, string $username): void
    {
        self::table();
        $db = Database::getInstance();
        $db->query(
            'INSERT INTO login_attempts (ip, username, ts) VALUES (?, ?, ?)',
            [$ip, mb_strtolower($username), time()]
        );
        self::prune();
    }

    public static function clear(string $ip, string $username): void
    {
        self::table();
        $db = Database::getInstance();
        $db->query('DELETE FROM login_attempts WHERE ip = ? AND username = ?', [$ip, mb_strtolower($username)]);
    }

    private static function table(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        Database::getInstance()->query(
            'CREATE TABLE IF NOT EXISTS login_attempts (
                ip TEXT NOT NULL,
                username TEXT NOT NULL,
                ts INTEGER NOT NULL
            )'
        );
        $done = true;
    }

    private static function prune(): void
    {
        Database::getInstance()->query('DELETE FROM login_attempts WHERE ts < ?', [time() - 86400]);
    }
}
