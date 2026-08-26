<?php

use App\Config\SiteConfig;
use App\Core\Autoloader;
use App\Core\Env;
use App\Core\PluginManager;
use App\Core\Router;
use App\Helpers\ErrorHelper;

// Define Root Path
define('ROOT', dirname(__DIR__));

// Handle Static Files for PHP Built-in Server
// HARUS sebelum output buffering apa pun: saat router script me-return false,
// built-in server menyajikan file langsung dan buffer di-flush tanpa
// memicu callback (autoloader belum tersedia pada titik ini).
if (php_sapi_name() === 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__.$url['path'];
    if (is_file($file)) {
        return false;
    }
}

// Manual require for the Autoloader class since it can't autoload itself
require_once ROOT.'/app/Core/Autoloader.php';
Autoloader::register();

// Start Output Buffering — SETELAH autoloader siap, agar callback yang
// memakai class (i18n + injeksi token CSRF) tidak pernah gagal load.
// Process buffered HTML through server-side i18n to prevent FOUC,
// then auto-inject CSRF tokens into every form (centralized CSRF fix)
ob_start(function ($html) {
    if (! class_exists(\App\Helpers\LanguageHelper::class)) {
        return $html; // jaga-jaga: jangan pernah white-screen karena callback
    }

    return \App\Helpers\CsrfHelper::injectForms(
        \App\Helpers\LanguageHelper::translateHtml($html)
    );
});

// Start Session — hardened cookie flags (fixes vuln: cookie lacks
// HttpOnly/Secure/SameSite). Secure hanya saat request memang HTTPS
// (termasuk di belakang reverse proxy) agar deployment LAN/STB tanpa TLS
// tetap berfungsi.
$isHttps = ($_SERVER['HTTPS'] ?? '') !== ''
    || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Load Environment Variables
Env::load(ROOT.'/.env');

// Global CSRF defense for state-changing requests (runs before dispatch;
// /api/* is exempt here — those endpoints enforce Origin individually and
// public APIs are intentionally cross-origin-consumable per CORS config)
\App\Helpers\CsrfHelper::enforceRequestSafety();

// Initialize Router
$router = new Router;

// Initialize Plugin System
$pluginManager = new PluginManager;
$pluginManager->loadPlugins();

// Global Error Handling for Dev Mode
if (SiteConfig::IS_DEV) {
    // Catch Fatal Errors (Shutdown)
    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
            // Convert to exception format for our helper
            $e = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            ErrorHelper::showException($e);
        }
    });

    // Catch Uncaught Exceptions
    set_exception_handler(function ($e) {
        ErrorHelper::showException($e);
    });
}

// Define Routes
require_once ROOT.'/routes/web.php';
require_once ROOT.'/routes/api.php';

// Dispatch
// Dispatch
try {
    $router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
} catch (Exception $e) {
    if (SiteConfig::IS_DEV) {
        ErrorHelper::showException($e);
    } else {
        ErrorHelper::show(500, 'Internal Server Error', $e->getMessage());
    }
} catch (Error $e) {
    if (SiteConfig::IS_DEV) {
        ErrorHelper::showException($e);
    } else {
        ErrorHelper::show(500, 'System Error', $e->getMessage());
    }
}
