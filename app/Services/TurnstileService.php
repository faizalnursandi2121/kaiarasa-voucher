<?php

namespace App\Services;

use App\Config\SiteConfig;

/**
 * Verifikasi server-side Cloudflare Turnstile.
 *
 * Aktif hanya bila TURNSTILE_SITE_KEY dan TURNSTILE_SECRET_KEY terisi.
 * Verifikasi dilakukan sebelum kredensial disentuh — bot ditolak lebih dulu.
 */
class TurnstileService
{
    /** IP klien sebenarnya (mendukung rantai X-Forwarded-For). */
    private static function clientIp(): string
    {
        $fwd = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($fwd !== '') {
            return trim(explode(',', $fwd)[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /** @return array{success:bool,error_codes?:array} */
    public static function verify(string $token): array
    {
        if ($token === '') {
            return ['success' => false, 'error_codes' => ['missing-input-response']];
        }

        $res = self::http(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            http_build_query([
                'secret'   => SiteConfig::getTurnstileSecretKey(),
                'response' => $token,
                // Di belakang Traefik/reverse-proxy, IP asli ada di header
                // forwarded — ambil IP pertama dari rantai.
                'remoteip' => self::clientIp(),
            ])
        );

        if ($res === null) {
            // Layanan Cloudflare tidak terjangkau: gagal-tutup agar tidak
            // ada celah lewat saat jaringan sedang bermasalah.
            return ['success' => false, 'error_codes' => ['service-unreachable']];
        }

        return [
            'success'     => ! empty($res['success']),
            'error_codes' => isset($res['error-codes']) && is_array($res['error-codes']) ? $res['error-codes'] : [],
        ];
    }

    private static function http(string $url, string $body): ?array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 6,
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
        } else {
            $ctx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 6,
            ]]);
            $raw = @file_get_contents($url, false, $ctx);
        }

        $json = json_decode((string) $raw, true);

        return is_array($json) ? $json : null;
    }
}
