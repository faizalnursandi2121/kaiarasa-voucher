<?php

/**
 * Atur kunci Cloudflare Turnstile langsung ke basis data.
 *
 * Pemakaian (di dalam kontainer / folder proyek):
 *   php scripts/set-turnstile.php "SITE_KEY" "SECRET_KEY"
 */

if (PHP_SAPI !== 'cli') { exit("Hanya via CLI.\n"); }

define('ROOT', dirname(__DIR__));
require ROOT . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

if ($argc < 3) {
    echo "Pemakaian: php scripts/set-turnstile.php \"SITE_KEY\" \"SECRET_KEY\"\n";
    exit(1);
}

$s = new App\Models\Setting;
$s->set('turnstile_site_key',  $argv[1]);
$s->set('turnstile_secret_key', $argv[2]);

echo "Kunci Turnstile tersimpan di basis data ✅\n";
