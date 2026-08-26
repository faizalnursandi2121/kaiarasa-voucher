<?php

namespace App\Helpers;

/**
 * Centralized CSRF defense (fixes vuln: no CSRF protection anywhere).
 *
 * Two layers, both server-side:
 *  1. Same-origin enforcement — browsers attach an Origin header to
 *     cross-origin state-changing requests; a mismatched Origin is rejected.
 *  2. Per-session synchronizer token — auto-injected into every HTML form
 *     through the global output buffer and verified on submission.
 *
 * Public /api/* endpoints are exempt here (no session authority to abuse);
 * admin XHR endpoints under /api/* enforce the Origin layer individually.
 */
class CsrfHelper
{
    public const FIELD = '_csrf';

    /** Paths exempt from full CSRF enforcement (prefix match). */
    private static array $exemptPrefixes = ['/api/', '/assets/'];

    public static function token(): string
    {
        if (empty($_SESSION[self::FIELD])) {
            $_SESSION[self::FIELD] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::FIELD];
    }

    /**
     * Rotate the token after a privilege change (e.g. successful login) so a
     * token leaked in a pre-auth context cannot be replayed authenticated.
     */
    public static function rotate(): void
    {
        $_SESSION[self::FIELD] = bin2hex(random_bytes(32));
    }

    /**
     * Global gate. Call once from public/index.php before router dispatch.
     */
    public static function enforceRequestSafety(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return; // safe methods
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        foreach (self::$exemptPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0 || strpos($path, '/'.$prefix) === 0) {
                return; // handled separately (origin checks / CORS config)
            }
        }

        // Layer 1 — same-origin enforcement.
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && ! self::isSameOrigin($origin)) {
            http_response_code(403);
            error_log('CSRF: cross-origin request blocked from '.$origin.' to '.$path);
            exit('Forbidden (cross-origin request rejected)');
        }

        // Layer 2 — synchronizer token.
        $sent = $_POST[self::FIELD]
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (! is_string($sent) || $sent === '' || ! hash_equals(self::token(), $sent)) {
            http_response_code(403);
            error_log('CSRF: missing/invalid token for '.$path);
            exit('Forbidden (invalid or missing CSRF token)');
        }
    }

    /**
     * Origin-only check for JSON/XHR endpoints (fetch() sends Origin on POST;
     * legacy same-origin clients may omit it, which we then allow — auth and
     * other controls still apply).
     */
    public static function enforceSameOrigin(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (! in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && ! self::isSameOrigin($origin)) {
            http_response_code(403);
            header('Content-Type: application/json');
            error_log('CSRF: cross-origin API request blocked from '.$origin);

            exit(json_encode(['error' => 'Cross-origin request rejected']));
        }
    }

    /**
     * Buang komentar HTML (<!-- ... -->) dari output final — dokumentasi
     * tetap ada di source code, tapi inspector/browser menerima markup
     * bersih. Blok <script> tidak disentuh agar JS inline yang memuat
     * string berkomentar tidak rusak.
     */
    public static function stripHtmlComments(string $html): string
    {
        if (strpos($html, '<!--') === false) {
            return $html;
        }

        // Pisahkan blok script dari segmen lainnya.
        $parts = preg_split('/(<script\b[^>]*>.*?<\/script>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($parts)) {
            return $html;
        }

        foreach ($parts as $i => $part) {
            // Index ganjil = hasil capture (blok script) — biarkan utuh.
            if ($i % 2 === 1) {
                continue;
            }
            $parts[$i] = preg_replace('/<!--.*?-->/s', '', $part) ?? $part;
        }

        return implode('', $parts);
    }

    /**
     * Inject a hidden CSRF input into every HTML form served by the app.
     * Runs inside the existing output-buffer pipeline — no view edits needed.
     *
     * CATATAN IMPLEMENTASI: regex naif '<form[^>]*>' SALAH untuk atribut
     * yang mengandung '>' di dalam nilai quoted — mis. arrow function
     * "onsubmit=\"...then(res => { ... })\"". Karena itu posisi penutup tag
     * dicari dengan pemindai sadar-kutip, karakter demi karakter.
     */
    public static function injectForms(string $html): string
    {
        if (stripos($html, '<form') === false && stripos($html, '<FORM') === false) {
            return $html;
        }

        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        $input = '<input type="hidden" name="'.self::FIELD.'" value="'.$token.'">';

        $out = '';
        $pos = 0;
        $len = strlen($html);

        while (($start = stripos($html, '<form', $pos)) !== false) {
            // Harus '<form' sebagai tag (dibatasi batas kata).
            $after = $start + 5;
            if ($after >= $len || ! preg_match('/[\s\/]/', $html[$after])) {
                // Bukan tag form (<formx dsb.) — salin apa adanya.
                $out .= substr($html, $pos, $after - $pos);
                $pos = $after;
                continue;
            }

            // Pindai sampai '>' di luar kutip.
            $i = $after;
            $quote = null;
            while ($i < $len) {
                $ch = $html[$i];
                if ($quote !== null) {
                    if ($ch === $quote) {
                        $quote = null;
                    }
                } elseif ($ch === '"' || $ch === "'") {
                    $quote = $ch;
                } elseif ($ch === '>') {
                    break;
                }
                ++$i;
            }
            if ($i >= $len) {
                break; // tag tidak tertutup — salin sisanya apa adanya
            }

            $attrs = substr($html, $after + 0, $i - $after); // isi atribut tanpa '>'
            $tagEnd = substr($html, $i, 1); // '>'

            // Salin segmen sebelum tag ini.
            $out .= substr($html, $pos, $start - $pos);

            $skip = false;
            // Sudah berisi token (hindari injeksi ganda).
            if (stripos($attrs, 'name="'.self::FIELD.'"') !== false
                || stripos($attrs, "name='".self::FIELD."'") !== false) {
                $skip = true;
            }
            // Jangan sentuh form menuju host eksternal.
            if (preg_match('#action\s*=\s*(["\'])https?://#i', $attrs)) {
                $skip = true;
            }

            if ($skip) {
                $out .= '<form'.$attrs.$tagEnd;
            } else {
                $out .= '<form'.$attrs.$tagEnd.$input;
            }

            $pos = $i + 1;
        }

        $out .= substr($html, $pos);

        return $out;
    }

    private static function isSameOrigin(string $origin): bool
    {
        $hostPart = parse_url($origin, PHP_URL_HOST);
        if ($hostPart === null) {
            return false;
        }

        $expected = $_SERVER['HTTP_HOST'] ?? '';
        if ($expected === '') {
            return false;
        }

        // Hostnames compare case-insensitively; ports compared only when sent.
        return strcasecmp($hostPart, parse_url('http://'.$expected, PHP_URL_HOST)) === 0
            && (parse_url($origin, PHP_URL_PORT) ?: null) === (parse_url('http://'.$expected, PHP_URL_PORT) ?: null);
    }
}
