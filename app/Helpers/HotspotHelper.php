<?php

namespace App\Helpers;

class HotspotHelper
{
    /**
     * Parse profile on-login script metadata (Standard format)
     * Format: :put (",mode,price,validity,selling_price,,")
     */
    public static function parseProfileMetadata($script)
    {
        if (empty($script)) {
            return [];
        }

        // Look for :put (",...,") pattern
        preg_match('/:put \("([^"]+)"\)/', $script, $matches);
        if (isset($matches[1])) {
            // Explode CSV: ,mode,price,validity,selling_price,,
            $data = explode(',', $matches[1]);

            $clean = function ($val) {
                return ($val === '0' || $val === '0d' || $val === '0h' || $val === '0m') ? '' : $val;
            };

            return [
                'expired_mode' => $data[1] ?? '',
                'price' => $clean($data[2] ?? ''),
                'validity' => self::formatValidity($clean($data[3] ?? '')),
                'validity_raw' => $clean($data[3] ?? ''),
                'selling_price' => $clean($data[4] ?? ''),
            ];
        }

        return [];
    }

    /**
     * Format validity string (e.g., 3d2h5m -> 3d 2h 5m)
     */
    public static function formatValidity($val)
    {
        if (empty($val)) {
            return '';
        }
        $parts = [];
        $map = ['d' => 'Day', 'h' => 'Hour', 'm' => 'Minute'];
        if (preg_match('/(\d+)d/i', $val, $m)) {
            $n = (int) $m[1];
            $parts[] = $n.' '.($n === 1 ? 'Day' : 'Days');
        }
        if (preg_match('/(\d+)h/i', $val, $m)) {
            $n = (int) $m[1];
            $parts[] = $n.' '.($n === 1 ? 'Hour' : 'Hours');
        }
        if (preg_match('/(\d+)m/i', $val, $m)) {
            $n = (int) $m[1];
            $parts[] = $n.' '.($n === 1 ? 'Minute' : 'Minutes');
        }
        return implode(' ', $parts);
    }

    /**
     * Format expired mode code to readable text
     */
    public static function formatExpiredMode($mode)
    {
        switch ($mode) {
            case 'rem': return 'Remove';
            case 'ntf': return 'Notice';
            case 'remc': return 'Remove & Record';
            case 'ntfc': return 'Notice & Record';
            default: return $mode;
        }
    }

    /**
     * Format bytes to human readable string (KB, MB, GB)
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        if (empty($bytes) || $bytes === '0') {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    /**
     * Get User Status Code
     * Returns: active, limited, locked, expired
     */
    public static function getUserStatus($user)
    {
        // Stempel "exp:" ditulis saat LOGIN PERTAMA sebagai penanda terjadwal,
        // BUKAN berarti sudah expired. Yang menentukan: flag disabled / limit.
        $comment = strtolower($user['comment'] ?? '');
        $disabled = in_array(strtolower((string) ($user['disabled'] ?? 'false')), ['true', 'yes'], true);

        // 1. Disabled + stempel exp = expired oleh scheduler
        if ($disabled && strpos($comment, 'exp') !== false) {
            return 'expired';
        }

        // 2. Kuota data habis
        $limitBytes = (int) ($user['limit-bytes-total'] ?? 0);
        if ($limitBytes > 0) {
            $usedBytes = (int) ($user['bytes-in'] ?? 0) + (int) ($user['bytes-out'] ?? 0);
            if ($usedBytes >= $limitBytes) {
                return 'limited';
            }
        }

        // 3. Uptime habis (sesi diputus router; disabled menyusul via scheduler)
        $limitUp = self::parseUptimeToSeconds((string) ($user['limit-uptime'] ?? ''));
        if ($limitUp > 0) {
            $usedUp = self::parseUptimeToSeconds((string) ($user['uptime'] ?? ''));
            if ($usedUp >= $limitUp) {
                return 'limited';
            }
        }

        // 4. Disabled manual tanpa stempel exp
        if ($disabled) {
            return 'locked';
        }

        // 5. Masih dalam masa aktif
        return 'active';
    }

    /**
     * Parse uptime RouterOS (e.g. 1w2d3h4m5s) menjadi detik
     */
    public static function parseUptimeToSeconds($val)
    {
        $val = strtolower(trim((string) $val));
        if ($val === '' || $val === '-') {
            return 0;
        }
        $multipliers = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];
        $total = 0;
        if (preg_match_all('/(\d+)([wdhms])/', $val, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $total += ((int) $m[1]) * $multipliers[$m[2]];
            }
        }
        return $total;
    }
}
