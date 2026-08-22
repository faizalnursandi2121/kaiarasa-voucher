<?php

/**
 * One-off migration runner for Home v2 (router_probe_logs & router_events).
 * Usage: php scripts/migrate-home-v2.php
 */

define('ROOT', dirname(__DIR__));

require_once ROOT.'/app/Core/Autoloader.php';
App\Core\Autoloader::register();

App\Core\Migrations::up();

echo "OK\n";
