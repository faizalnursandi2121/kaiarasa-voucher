<?php

/**
 * Activity Sampler — rekam snapshot Active Users per sesi router.
 *
 * Cron (opsional, agar chart terisi walau tak ada yang membuka dashboard):
 *   *\/5 * * * *  php /path/kaiarasa/scripts/sample-activity.php
 *
 * Usage:
 *   php scripts/sample-activity.php            # semua sesi di tabel routers
 *   php scripts/sample-activity.php demo       # sesi tertentu
 */

define('ROOT', __DIR__.'/..');

require ROOT.'/app/Core/Database.php';
require ROOT.'/app/Libraries/RouterOSAPI.php';
require ROOT.'/app/Models/Config.php';
require ROOT.'/app/Services/ActivitySnapshotService.php';

use App\Core\Database;
use App\Services\ActivitySnapshotService;

$sessions = array_slice($argv, 1);

if (! $sessions) {
    $rows = Database::getInstance()->getConnection()
        ->query('SELECT session_name FROM routers')
        ->fetchAll(\PDO::FETCH_ASSOC);
    $sessions = array_column($rows, 'session_name');
}

foreach ($sessions as $session) {
    $svc = new ActivitySnapshotService($session);
    $n = $svc->countActiveNow();

    if ($n === null) {
        echo $session.': unreachable — skipped'.PHP_EOL;

        continue;
    }

    $svc->recordNow($n);
    echo $session.': snapshot recorded ('.$n.' active users)'.PHP_EOL;
}
