<?php

/**
 * HTML Tag Balance Checker untuk View Templates (app/Views).
 *
 * Mendeteksi kesalahan struktur HTML di file view:
 *   - ERROR   : tag penutup yatim (tidak ada pembukanya)
 *   - ERROR   : force-close (tag penutup memaksa menutup elemen lain,
 *               mis. </aside> menutup <div> yang belum selesai)
 *   - WARNING : elemen yang masih terbuka di akhir file pada view biasa
 *               (di file parsial layout seperti header_/footer_/sidebar_
 *               ini normal karena pasangannya ada di file lain)
 *   - NOTICE  : elemen terbuka di akhir file parsial layout (informatif)
 *
 * Latar belakang: kerusakan layout session (commit 7d24e96) disebabkan
 * sisa penghapusan toggle tema yang menyisakan tag </div> berlebih.
 * Tidak terdeteksi `php -l` karena yang rusak struktur HTML-nya,
 * bukan sintaks PHP-nya.
 *
 * Catatan: parser ini sengaja ketat — tidak meniru auto-close browser
 * untuk <li>, <p>, <td> dll. yang lupa ditutup tetap akan tertangkap.
 *
 * Usage:
 *   php scripts/check-view-tags.php                        # scan semua app/Views
 *   php scripts/check-view-tags.php app/Views/dashboard.php # scan file tertentu
 *   php scripts/check-view-tags.php --strict                # warning juga gagal
 *
 * Exit code: 0 = bersih, 1 = ada error (atau warning saat --strict).
 */

define('ROOT', dirname(__DIR__));

// Elemen kosong & daun SVG yang sah tanpa tag penutup.
const VOID_TAGS = [
    'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link',
    'meta', 'param', 'source', 'track', 'wbr',
    'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse', 'stop', 'use',
];

// File parsial layout yang pembuka/penutupnya sengaja lintas-file.
const PARTIAL_PATTERN = '/^(header_|footer_|sidebar_|navbar_|page_)/';

/**
 * Normalisasi sumber PHP -> HTML murni dengan MEMPERTAHANKAN jumlah baris,
 * sehingga nomor baris hasil parse tetap akurat terhadap file asli.
 */
function normalize(string $src): string
{
    // Komentar HTML dibuang (tag di dalam komentar tidak dihitung).
    $src = preg_replace_callback('/<!--.*?-->/s', function ($m) {
        return str_repeat("\n", substr_count($m[0], "\n"));
    }, $src);

    // Blok PHP penuh dan short-echo diganti newline setara.
    $src = preg_replace_callback('/<\?(?:php|=).*?\?>/s', function ($m) {
        return str_repeat("\n", substr_count($m[0], "\n"));
    }, $src);

    // Isi <script> dibuang (JS bisa mengandung "<" yang bukan tag HTML).
    $src = preg_replace_callback('/<script\b[^>]*>.*?<\/script>/is', function ($m) {
        return preg_replace('/[^\n]/', '', $m[0]);
    }, $src);

    // Isi <style> di-escape agar selector CSS tidak dianggap tag.
    $src = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function ($m) {
        return '<style>'.str_replace('<', '&lt;', $m[1]).'</style>';
    }, $src);

    return $src;
}

/**
 * Analisis keseimbangan tag satu file.
 *
 * @return array{errors: string[], warnings: string[], notices: string[]}
 */
function analyze(string $file): array
{
    $raw = file_get_contents($file);
    if ($raw === false) {
        return ['errors' => ["$file: file tidak dapat dibaca"], 'warnings' => [], 'notices' => []];
    }

    $src = normalize($raw);
    $isPartial = (bool) preg_match(PARTIAL_PATTERN, basename($file));

    $stack = [];          // [tag, baris]
    $errors = [];
    $warnings = [];
    $notices = [];

    if (! preg_match_all('/<(\/?)([a-zA-Z][a-zA-Z0-9-]*)((?:"[^"]*"|\'[^\']*\'|[^>"\'])*)(\/?)>/', $src, $matches, PREG_OFFSET_CAPTURE)) {
        return compact('errors', 'warnings', 'notices');
    }

    foreach ($matches[0] as $i => $full) {
        [$tagRaw, $attrsRaw, $slashRaw] = [$matches[2][$i][0], $matches[3][$i][0], $matches[4][$i][0]];
        $isClose = $matches[1][$i][0] === '/';
        $line = substr_count(substr($src, 0, $matches[0][$i][1]), "\n") + 1;
        $tag = strtolower($tagRaw);

        if ($slashRaw === '/' || in_array($tag, VOID_TAGS, true)) {
            continue;
        }

        if (! $isClose) {
            $stack[] = [$tag, $line];
            continue;
        }

        if ($stack && end($stack)[0] === $tag) {
            array_pop($stack);
            continue;
        }

        // Cari pasangan di kedalaman stack.
        $names = array_column($stack, 0);
        if (in_array($tag, array_reverse($names), true)) {
            while ($stack && end($stack)[0] !== $tag) {
                [$popped, $openedAt] = array_pop($stack);
                $errors[] = sprintf(
                    '%s:%d: </%s> memaksa menutup <%s> yang dibuka di baris %d',
                    $file, $line, $tag, $popped, $openedAt
                );
            }
            if ($stack) {
                array_pop($stack);
            }
            continue;
        }

        $errors[] = sprintf('%s:%d: tag penutup </%s> yatim (tidak ada pembukanya)', $file, $line, $tag);
    }

    // Sisa stack di akhir file.
    foreach ($stack as [$tag, $line]) {
        $msg = sprintf('%s:%d: <%s> (baris %d) tidak ditutup di akhir file', $file, $line, $tag, $line);
        if ($isPartial) {
            $notices[] = $msg.' — parsial layout, kemungkinan ditutup di file lain';
        } else {
            $warnings[] = $msg;
        }
    }

    return compact('errors', 'warnings', 'notices');
}

/** @return string[] daftar absolut path file .php yang akan dianalisis */
function collectTargets(array $argv): array
{
    $args = array_slice($argv, 1);
    $strict = in_array('--strict', $args, true);
    $paths = array_values(array_filter($args, fn ($a) => $a !== '--strict'));

    if (! $paths) {
        $paths = [ROOT.'/app/Views'];
    }

    $files = [];
    foreach ($paths as $p) {
        if (! is_dir($p) && ! is_file($p)) {
            fwrite(STDERR, "Lewati (bukan file/direktori): $p\n");
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            is_dir($p) ? new RecursiveDirectoryIterator($p, FilesystemIterator::SKIP_DOTS) : new ArrayIterator([$p => new SplFileInfo($p)])
        );
        foreach ($iterator as $f) {
            if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
                $files[] = $f->getPathname();
            }
        }
    }

    sort($files);

    return [$files, $strict];
}

[$targets, $strict] = collectTargets($argv);

$totalErrors = $totalWarnings = $totalNotices = 0;

foreach ($targets as $file) {
    ['errors' => $e, 'warnings' => $w, 'notices' => $n] = analyze($file);
    $totalErrors += count($e);
    $totalWarnings += count($w);
    $totalNotices += count($n);

    foreach (['errors' => $e, 'warnings' => $w, 'notices' => $n] as $msgs) {
        foreach ($msgs as $msg) {
            echo $msg."\n";
        }
    }
}

echo "\n";
echo "Selesai: ".count($targets)." file — "
    .$totalErrors." error, "
    .$totalWarnings." warning, "
    .$totalNotices." notice\n";

exit(($totalErrors > 0 || ($strict && $totalWarnings > 0)) ? 1 : 0);
