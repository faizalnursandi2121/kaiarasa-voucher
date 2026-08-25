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
        @keyframes kaiSlide { from { transform: translateX(-110%); } to { transform: translateX(320%); } }
        /* Ikon: ruang ter-reserve (ukuran dari kelas), muncul halus saat lucide siap */
        [data-lucide] { opacity: 0; transition: opacity .18s ease; }
        html.lucide-ready [data-lucide] { opacity: 1; }
        /* Elemen yang memang dimulai tersembunyi (mis. batch toolbar)
           TIDAK ikut dianimasikan — deterministik di semua kondisi. */
        .kai-pop > *:not([class*="opacity-0"]) { animation: kaiRowIn .3s ease both; }
        @media (prefers-reduced-motion: reduce) {
            .kai-pop > * { animation: none !important; }
        }
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
        // Diagnosa performa (aktif bila URL mengandung debug=1)
        if (location.search.indexOf('debug=1') !== -1) {
            window.__kaiDiag = { marks: [] };
            window.kaiMark = function (n) { window.__kaiDiag.marks.push([n, Math.round(performance.now())]); };
            window.kaiMark('head');
            document.addEventListener('DOMContentLoaded', function(){ window.kaiMark('DCL'); });
            window.addEventListener('load', function(){
                window.kaiMark('load');
                if (document.fonts) document.fonts.ready.then(function(){ window.kaiMark('fonts-ready'); });
                console.log('[DIAG]', JSON.stringify(window.__kaiDiag.marks));
            });
        }
    </script>
    <script>
        // Refresh di subhalaman sesi -> langsung ke Dashboard
        // (dipasang di head agar tidak ada kilasan konten lama)
        try {
            var __nav = performance.getEntriesByType('navigation')[0];
            var __seg = location.pathname.split('/').filter(Boolean);
            if (__nav && __nav.type === 'reload'
                && __seg.length >= 2 && __seg[1] !== 'dashboard'
                && ['settings','login','logout'].indexOf(__seg[0]) === -1
                && location.pathname.search(/(edit|preview|print|add)/) === -1) {
                location.replace('/' + __seg[0] + '/dashboard');
            }
        } catch (e) {}
    </script>
    <script>
        // Light-only lock — dark mode dinonaktifkan permanen
        document.documentElement.classList.remove('dark');
        try { localStorage.removeItem('theme'); } catch (e) {}
    </script>
    <link rel="preload" as="script" href="/assets/js/lucide.min.js">
    <script src="/assets/js/lucide.min.js" defer onload="try{if(window.kaiMark)window.kaiMark('lucide-exec');if(window.lucide&&window.lucide.createIcons){window.lucide.createIcons();}if(window.kaiMark)window.kaiMark('icons-drawn');document.documentElement.classList.add('lucide-ready')}catch(e){}"></script>
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

<!-- Loading state perpindahan rute berat (Home -> Dashboard, pindah sesi) -->
<div id="kai-route-loading" class="fixed inset-0 hidden items-center justify-center" style="z-index:100;background:rgba(250,250,248,.85)">
    <div class="flex flex-col items-center gap-4 px-6 text-center">
        <div class="w-12 h-12 rounded-full border-[3px] border-black/[.08]" style="border-top-color:#5f7f67;animation:spin .8s linear infinite"></div>
        <p class="text-sm font-semibold opacity-70" data-label>Preparing…</p>
        <div class="w-44 h-1.5 rounded-full overflow-hidden" style="background:rgba(0,0,0,.07)">
            <div class="h-full w-1/3 rounded-full" style="background:#5f7f67;animation:kaiSlide 1.1s ease-in-out infinite alternate"></div>
        </div>
    </div>
</div>
<script>
// Fallback awal: atribut on* di sidebar bisa terpicu sebelum footer
        // (tempat definisi asli toggleMenu/closeMenu) selesai di-stream.
        window.toggleMenu = window.toggleMenu || function () {};
        window.closeMenu  = window.closeMenu  || function () {};

        window.kaiRouteLoading = function (show, label) {
    var el = document.getElementById('kai-route-loading');
    if (!el) return;
    if (label) { var t = el.querySelector('[data-label]'); if (t) t.textContent = label; }
    el.classList.toggle('hidden', !show);
    el.classList.toggle('flex', !!show);
};
</script>
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
    

