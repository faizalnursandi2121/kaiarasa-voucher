<?php

namespace App\Config;

class SiteConfig
{
    const APP_NAME = 'Kaiarasa';

    const APP_VERSION = 'v1.2.3';

    const APP_FULL_NAME = 'Kaiarasa - Voucher Management';

    const CREDIT_NAME = 'Kaiarasa';

    const CREDIT_URL = 'https://github.com/kaiarasa';

    const YEAR = '2026';

    const REPO_URL = 'https://github.com/kaiarasa/kaiarasa';

    // Security Keys
    // Fetched from .env or fallback to default
    public static function getSecretKey()
    {
        // 1) Environment variable eksplisit (Dokploy/host) yang bukan placeholder
        $env = getenv('APP_KEY');
        if ($env && $env !== 'kaiarasa_official_secret_key_32bytes') {
            return $env;
        }

        // 2) Fallback: kunci yang disimpan permanen di volume (tahan restart/redeploy)
        $persisted = @file_get_contents(ROOT.'/app/Database/app_key');
        if ($persisted !== false && trim($persisted) !== '') {
            return trim($persisted);
        }

        // 3) Tidak ada sumber kunci sama sekali: buat kunci acak dan simpan
        //    permanen. Placeholder publik TIDAK BOLEH dipakai untuk enkripsi
        //    nyata (CWE-798: hardcoded cryptographic key).
        $generated = bin2hex(random_bytes(32));
        if (@file_put_contents(ROOT.'/app/Database/app_key', $generated, LOCK_EX) !== false) {
            @chmod(ROOT.'/app/Database/app_key', 0600);

            return $generated;
        }

        error_log('SiteConfig: cannot persist generated APP_KEY at '.ROOT.'/app/Database/app_key'.
            ' — refusing strong default; using public placeholder (DO NOT USE IN PRODUCTION)');

        return 'kaiarasa_official_secret_key_32bytes';
    }

    private static function envAny(string $key): string
    {
        foreach ([getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null] as $v) {
            if ($v !== null && $v !== false && trim((string) $v) !== '') {
                return trim((string) $v);
            }
        }
        // Fallback terakhir: baca langsung berkas .env (tanpa memicu loader).
        static $parsed = null;
        if ($parsed === null) {
            $parsed = [];
            $path = ROOT . '/.env';
            if (is_readable($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#') continue;
                    if (strpos($line, '=') !== false) {
                        [$k, $v] = explode('=', $line, 2);
                        $parsed[trim($k)] = trim(trim($v), "\"'");
                    }
                }
            }
        }
        return isset($parsed[$key]) ? $parsed[$key] : '';
    }

    private static function dbSetting(string $suffix): string
    {
        try {
            $v = (new \App\Models\Setting)->get('turnstile_' . $suffix);
            return $v !== null ? trim((string) $v) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** Urutan: env proses/.env -> database settings. */
    public static function getTurnstileSiteKey(): string
    {
        $v = self::envAny('TURNSTILE_SITE_KEY');
        return $v !== '' ? $v : self::dbSetting('site_key');
    }

    public static function getTurnstileSecretKey(): string
    {
        $v = self::envAny('TURNSTILE_SECRET_KEY');
        return $v !== '' ? $v : self::dbSetting('secret_key');
    }

    /** Anti-bot aktif hanya bila kedua kunci Turnstile terisi. */
    public static function turnstileEnabled(): bool
    {
        return self::getTurnstileSiteKey() !== '' && self::getTurnstileSecretKey() !== '';
    }

    /**
     * Kunci aplikasi masih placeholder default?
     */
    public static function isDefaultSecretKey(): bool
    {
        return self::getSecretKey() === 'kaiarasa_official_secret_key_32bytes';
    }

    /**
     * Simpan kunci secara permanen di direktori database (volume).
     */
    public static function persistSecretKey(string $key): void
    {
        @file_put_contents(ROOT.'/app/Database/app_key', $key);
        @chmod(ROOT.'/app/Database/app_key', 0600);
    }

    const IS_DEV = false; // Set to true only for local development.

    /**
     * Get the formatted page title
     */
    public static function getTitle($page = '')
    {
        return empty($page) ? self::APP_FULL_NAME : $page.' | '.self::APP_FULL_NAME;
    }

    /**
     * Get footer text
     */
    public static function getFooter()
    {
        $currentYear = date('Y');
        $yearDisplay = ($currentYear == self::YEAR) ? self::YEAR : self::YEAR.' - '.$currentYear;

        return self::APP_FULL_NAME.' &copy; 2026 - '.$yearDisplay.' &bull; Created with Love <i data-lucide="heart" class="w-3 h-3 inline text-red-500 fill-red-500 mx-1"></i> Developed by <a href="'.self::CREDIT_URL.'" target="_blank" class="font-medium hover:text-foreground transition-colors">'.self::CREDIT_NAME.'</a>';
    }
}
