<?php

namespace App\Helpers;

use App\Config\SiteConfig;

class EncryptionHelper
{
    /**
     * Prefix of the current authenticated-encryption envelope (AES-256-GCM).
     * Anything without this marker is treated as legacy ciphertext or,
     * failing that, rejected entirely.
     */
    private const V2_PREFIX = 'enc2::';

    private const TAG_LENGTH = 16;

    public static function encrypt($text)
    {
        if (empty($text)) {
            return '';
        }

        $key = self::v2Key();

        // AES-256-GCM: confidentiality + integrity (tamper-evident).
        // Envelope: enc2::<b64(nonce)>::<b64(ciphertext||tag)> — textual
        // separators are safe because both fields are base64-encoded.
        $nonce = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt((string) $text, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
        if ($cipher === false) {
            error_log('EncryptionHelper: GCM encryption failed');

            return '';
        }

        return self::V2_PREFIX.base64_encode($nonce).'::'.base64_encode($cipher.$tag);
    }

    public static function decrypt($text)
    {
        if ($text === null || $text === '') {
            return '';
        }
        $text = (string) $text;

        // --- Current v2 envelope: strict AES-256-GCM ---
        if (str_starts_with($text, self::V2_PREFIX)) {
            return self::decryptV2(substr($text, strlen(self::V2_PREFIX)));
        }

        // --- Legacy AES-256-CBC envelope: read-only support for old rows ---
        $key = substr(SiteConfig::getSecretKey(), 0, 32);
        $decoded = base64_decode($text, true);
        if ($decoded === false) {
            return null; // FAIL CLOSED (previously returned input unchanged)
        }

        $parts = explode('::', $decoded, 2);
        if (count($parts) !== 2) {
            return null; // FAIL CLOSED — plaintext is never accepted anymore
        }
        [$encrypted_data, $iv] = $parts;

        $plain = openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);

        return $plain === false ? null : $plain;
    }

    private static function decryptV2(string $payload)
    {
        $parts = explode('::', $payload);
        if (count($parts) !== 2) {
            return null;
        }

        $nonce = base64_decode($parts[0], true);
        $ct = base64_decode($parts[1], true);
        if ($nonce === false || $ct === false || strlen($nonce) !== 12 || strlen($ct) <= self::TAG_LENGTH) {
            return null;
        }

        $tag = substr($ct, -self::TAG_LENGTH);
        $cipher = substr($ct, 0, -self::TAG_LENGTH);

        $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::v2Key(), OPENSSL_RAW_DATA, $nonce, $tag);

        return $plain === false ? null : $plain; // wrong key / tampered -> null
    }

    /**
     * Fixed-length 32-byte key derived from the application secret,
     * independent of the secret's original length.
     */
    private static function v2Key(): string
    {
        return hash('sha256', SiteConfig::getSecretKey(), true);
    }

    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}
