<?php

use App\Config\SiteConfig;
use App\Core\Hooks;

// Initialize variables to avoid undefined notices if not set
$hotspotname = isset($hotspotname) ? $hotspotname : SiteConfig::APP_NAME;
$themecolor = isset($themecolor) ? $themecolor : '#000000';
$theme = 'light'; // Default theme
$title = isset($title) ? SiteConfig::getTitle($title) : SiteConfig::getTitle();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_COOKIE["kaiarasa_lang"] ?? "en") ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title; ?></title>
    <meta name="theme-color" content="<?= $themecolor ?>" />
    
    <!-- Icons -->
    <link rel="icon" href="/assets/img/favicon.png" />
    
    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="/assets/css/styles.css">
    
    <!-- Flag Icons (Local) -->
    <link rel="stylesheet" href="/assets/vendor/flag-icons/css/flag-icons.min.css" />

    
    <style>
        @font-face {
            font-family: 'Geist';
            src: url('/assets/fonts/Geist-Regular.woff2') format('woff2');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Geist';
            src: url('/assets/fonts/Geist-Bold.woff2') format('woff2');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'Geist Mono';
            src: url('/assets/fonts/GeistMono-Regular.woff2') format('woff2');
            font-weight: 400;
            font-style: normal;
        }
    </style>

    <script>
        // Check local storage or system preference on load
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/lucide.min.js"></script>
    <script>
        window.currentVersion = '<?= SiteConfig::APP_VERSION ?>';
        // Run a callback now if the DOM is already parsed, otherwise on DOMContentLoaded.
        // Lets view scripts work both on a full page load (waits for deferred libs)
        // and when injected via SPA (readyState is already 'complete').
        window.whenReady = function (cb) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', cb);
            } else {
                cb();
            }
        };
    </script>
    <script src="/assets/js/kaiarasa.js" defer></script>
    <script src="/assets/js/modules/updater.js" defer></script>
    <script src="/assets/js/components/select.js" defer></script>
    <script src="/assets/js/components/datatable.js" defer></script>
    <script src="/assets/js/sweetalert2.all.min.js" defer></script>
    <script src="/assets/js/modules/alert.js" defer></script>
    <script src="/assets/js/modules/i18n.js" defer></script>
    
    <?php Hooks::doAction('kaiarasa_head'); ?>
</head>
<body class="flex flex-col min-h-screen bg-background text-foreground antialiased relative">
    <a href="#main-content" class="skip-link">Skip to main content</a>
    <!-- Background Elements (Global Sci-Fi Grid) -->
    <div class="fixed inset-0 z-0 pointer-events-none">
        <!-- Subtle Grid Pattern -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMCwwLDAsMC4zKSIvPjwvc3ZnPg==')] dark:bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMSIgY3k9IjEiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
    </div>
    <?php
    if (isset($session) && ! empty($session)) {
        // Session Layout (Sidebar)
        include ROOT.'/app/Views/layouts/sidebar_session.php';
    } else {
        // Global Layout (Navbar)
        include ROOT.'/app/Views/layouts/navbar_main.php';
        if (! isset($no_main_container) || ! $no_main_container) {
            echo '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full flex flex-col">';
        }
    }
?>
    

