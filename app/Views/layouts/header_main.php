<?php

use App\Config\SiteConfig;
use App\Core\Hooks;

// Initialize variables to avoid undefined notices if not set
$hotspotname = isset($hotspotname) ? $hotspotname : SiteConfig::APP_NAME;
$themecolor = isset($themecolor) ? $themecolor : '#fafaf8'; // light-only
$theme = 'light'; // Default theme
$title = isset($title) ? SiteConfig::getTitle($title) : SiteConfig::getTitle();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title; ?></title>
    <meta name="theme-color" content="<?= $themecolor ?>" />
    
    <!-- Icons -->
    <link rel="icon" href="/assets/img/favicon.png" />
    
    <!-- Tailwind CSS (Local) -->
    <link rel="stylesheet" href="/assets/css/styles.css?v=<?= filemtime(ROOT.'/public/assets/css/styles.css') ?>">
    
    <!-- Flag Icons (Local) -->
    <link rel="stylesheet" href="/assets/vendor/flag-icons/css/flag-icons.min.css" />

    
    <link rel="preload" href="/assets/fonts/Geist-Regular.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/Geist-Bold.woff2" as="font" type="font/woff2" crossorigin>
    <style>
        /* Transisi konten dinamis — Home (#table-scroll) & sesi (#session-dynamic) */
        #table-scroll, #session-dynamic { transition: opacity .22s ease; }
        @keyframes kaiRowIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: none; } }
        .kai-pop > * { animation: kaiRowIn .3s ease both; }
        @font-face {
            font-family: 'Geist';
            src: url('/assets/fonts/Geist-Regular.woff2') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Geist';
            src: url('/assets/fonts/Geist-Bold.woff2') format('woff2');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        @font-face {
            font-family: 'Geist Mono';
            src: url('/assets/fonts/GeistMono-Regular.woff2') format('woff2');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
    </style>

    <script>
        // Light-only lock — dark mode dinonaktifkan permanen
        document.documentElement.classList.remove('dark');
        try { localStorage.removeItem('theme'); } catch (e) {}
    </script>
    <link rel="preload" as="script" href="/assets/js/lucide.min.js">
    <script src="/assets/js/lucide.min.js" defer onload="if(window.lucide&&window.lucide.createIcons){window.lucide.createIcons()}"></script>
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
        <div class="absolute inset-0 bg-gradient-to-b from-black/[.02] to-transparent dark:from-white/[.02]"></div>
    </div>
    <?php
    if (isset($session) && ! empty($session)) {
        // Session Layout: Topbar + Sidebar
        include ROOT.'/app/Views/layouts/sidebar_session.php';
    } else {
        // Global Layout (Navbar)
        include ROOT.'/app/Views/layouts/navbar_main.php';
        if (! isset($no_main_container) || ! $no_main_container) {
            echo '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full flex flex-col">';
        }
    }
?>
    

