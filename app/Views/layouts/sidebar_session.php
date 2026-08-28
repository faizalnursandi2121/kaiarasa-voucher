<?php

use App\Helpers\LanguageHelper;
use App\Models\Config;

// Determine active link state
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$isDashboard = strpos($uri, '/dashboard') !== false;
$isGenerate = strpos($uri, '/hotspot/generate') !== false;
$isTemplates = strpos($uri, '/voucher-templates') !== false;
$isLogos = strpos($uri, '/logos') !== false;
$sessionUser = $_SESSION['username'] ?? 'Admin';
        $sessionInitials = strtoupper(substr($sessionUser, 0, 2));
        $isSettings = ($uri === '/settings' || strpos($uri, '/settings/') !== false) && ! $isTemplates && ! $isLogos;

// Hotspot Group Active Check
$hotspotPages = ['/hotspot/users', '/hotspot/profiles', '/hotspot/generate'];
$isHotspotActive = false;
foreach ($hotspotPages as $page) {
    if (strpos($uri, $page) !== false) {
        $isHotspotActive = true;
        break;
    }
}

// Status Group Active Check
$statusPages = ['/hotspot/active'];
$isStatusActive = false;
foreach ($statusPages as $page) {
    if (strpos($uri, $page) !== false) {
        $isStatusActive = true;
        break;
    }
}

// Administration Group Active Check
$adminPages = ['/hotspot/bindings', '/hotspot/walled-garden', '/network/dhcp',
    '/reports/user-log', '/system/scheduler', '/system/reboot', '/system/shutdown'];
$isSecurityActive = false;
foreach ($adminPages as $page) {
    if (strpos($uri, $page) !== false) {
        $isSecurityActive = true;
        break;
    }
}

// Reports Group Active Check
$reportsPages = ['/reports/sales', '/reports/user-log'];
$isReportsActive = false;
foreach ($reportsPages as $page) {
    if (strpos($uri, $page) !== false) {
        $isReportsActive = true;
        break;
    }
}

// Network Group Active Check
$networkPages = ['/network/dhcp'];
$isNetworkActive = false;
foreach ($networkPages as $page) {
    if (strpos($uri, $page) !== false) {
        $isNetworkActive = true;
        break;
    }
}

// System Group Active Check
$systemPages = ['/system/scheduler'];
$isSystemActive = false;
foreach ($systemPages as $page) {
    if (strpos($uri, $page) !== false) {
        $isSystemActive = true;
        break;
    }
}

// Fetch all sessions for the switcher
$configModel = new Config;
$allSessions = $configModel->getAllSessions();

// Find current session details to get Hotspot Name / IP
$currentSessionDetails = [];
foreach ($allSessions as $s) {
    if (isset($session) && $s['session_name'] === $session) {
        $currentSessionDetails = $s;
        break;
    }
}
// Determine label: Hotspot Name > IP Address > 'Kaiarasa'
$sessionLabel = $currentSessionDetails['hotspot_name'] ?? $currentSessionDetails['ip_address'] ?? 'Kaiarasa';
if (empty($sessionLabel)) {
    $sessionLabel = $currentSessionDetails['ip_address'] ?? 'Kaiarasa';
}

// Helper for Session Initials (Kebab-friendly)
$getInitials = function ($name) {
    if (empty($name)) {
        return 'UN';
    }
    if (strpos($name, '-') !== false) {
        $parts = explode('-', $name);
        $initials = '';
        foreach ($parts as $part) {
            if (! empty($part)) {
                $initials .= substr($part, 0, 1);
            }
        }

        return strtoupper(substr($initials, 0, 2));
    }

    return strtoupper(substr($name, 0, 2));
};
?>
<div class="flex h-screen overflow-hidden">
    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity opacity-0"></div>

    <!-- Sidebar -->
    <aside id="sidebar" data-session="<?= htmlspecialchars($session ?? '') ?>" class="w-64 flex-shrink-0 border-r border-white/20 bg-[#5f7f67] fixed inset-y-0 left-0 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col">
        <!-- Sidebar Header -->
        <div id="sidebar-header" class="flex flex-col items-center py-5 border-b border-accents-2 flex-shrink-0 relative cursor-default">
            <div class="relative w-full h-10 flex items-center justify-center">
                
                <img src="/assets/img/logo-white.webp" alt="Kaiarasa Logo" width="120" height="32" class="h-10 w-auto">
            </div>

            <!-- Mobile Close Button -->
            <button id="sidebar-close" aria-label="Close sidebar" class="md:hidden absolute top-4 right-4 text-accents-5 hover:text-foreground">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <!-- Sidebar Content -->
        <!-- Sidebar Content (RTL for left scrollbar) -->
        <div class="flex-1 overflow-y-auto" style="direction: rtl;">
            <div class="py-4 px-3 space-y-1" style="direction: ltr;">
            <!-- Session Switcher -->
            <div class="px-3 mb-6 relative" onmouseleave="closeMenu('session-dropdown')">
                <button type="button" class="w-full group grid grid-cols-[auto_1fr_auto] items-center gap-3 px-4 py-2.5 rounded-xl bg-white/10 border-white/20 hover:bg-white/20 transition-colors decoration-0 overflow-hidden shadow-sm" onclick="toggleMenu('session-dropdown', this)">
                    <!-- Initials -->
                    <div class="h-8 w-8 rounded-lg bg-white/15 group-hover:bg-white/25 flex items-center justify-center text-xs font-bold text-white transition-colors flex-shrink-0">
                        <?= $getInitials($session ?? '') ?>
                    </div>

                    <!-- Text Info -->
                    <div class="flex flex-col text-left min-w-0">
                        <span class="text-xs font-bold text-accents-6 group-hover:text-foreground transition-colors leading-none truncate"><?= htmlspecialchars($session ?? 'Select Session') ?></span>
                        <span class="text-[10px] text-accents-4 leading-none mt-1 truncate" title="<?= htmlspecialchars($sessionLabel) ?>">
                            <?= htmlspecialchars($sessionLabel) ?>
                        </span>
                    </div>

                    <!-- Chevron Icon -->
                    <div class="h-8 w-8 flex-shrink-0 flex items-center justify-center rounded-lg bg-white/15 group-hover:bg-white/25 transition-colors">
                        <i data-lucide="chevrons-up-down" class="!w-4 !h-4 !text-accents-6 dark:!text-accents-6 transition-colors"></i>
                    </div>
                </button>

                <!-- Dropdown -->
                <div id="session-dropdown" class="absolute top-full left-3 w-[calc(100%-1.5rem)] z-50 mt-1 bg-white dark:bg-[#1a1c19] border border-black/10 dark:border-white/10 rounded-lg shadow-lg overflow-hidden transition-colors duration-200 ease-out origin-top opacity-0 scale-95 invisible pointer-events-none dropdown-bridge" onmouseenter="if(typeof menuTimeout !== 'undefined') clearTimeout(menuTimeout)">
                    <div class="py-1 max-h-60 overflow-y-auto">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 border-b border-gray-200 dark:text-gray-400 dark:bg-white/5 dark:border-white/10" data-i18n="sidebar.switch_session">
                            Switch Session
                        </div>
                        <?php foreach ($allSessions as $s) { ?>
                        <a href="/<?= htmlspecialchars($s['session_name']) ?>/dashboard" class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-white/5 transition-colors group/item text-gray-700 dark:text-gray-200">
                            <div class="h-6 w-6 rounded flex-shrink-0 bg-gray-200 flex items-center justify-center text-[10px] font-bold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                 <?= $getInitials($s['session_name']) ?>
                            </div>
                            <div class="flex flex-col overflow-hidden">
                                <span class="truncate <?= ($session === $s['session_name']) ? 'font-semibold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-200' ?>">
                                    <?= htmlspecialchars($s['session_name']) ?>
                                </span>
                                <span class="text-[10px] text-gray-400 truncate">
                                    <?= htmlspecialchars($s['hotspot_name'] ?: $s['ip_address']) ?>
                                </span>
                            </div>
                             <?php if ($session === $s['session_name']) { ?>
                                <i data-lucide="check" class="w-3 h-3 ml-auto text-primary"></i>
                            <?php } ?>
                        </a>
                        <?php } ?>
                    </div>
                    <div class="border-t border-gray-200 dark:border-white/10 p-1 bg-gray-50 dark:bg-white/5">
                         <a href="/settings/add" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-100 hover:text-gray-800 rounded-md transition-colors text-gray-600 dark:text-gray-300 dark:hover:bg-white/5">
                            <i data-lucide="plus-circle" class="w-4 h-4 text-gray-600 dark:text-gray-300"></i>
                            <span data-i18n="settings.add_router" class="text-gray-600 dark:text-gray-300"><?= LanguageHelper::t('settings.add_router', 'Connect Router') ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dashboard -->
                <a href="/<?php echo htmlspecialchars($session) ?>/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-[10px] text-[13px] font-medium transition-colors <?= ($isDashboard) ? 'bg-white/[.18] text-white font-semibold' : 'text-white/75 hover:text-white hover:bg-white/10' ?>">
                    <i data-lucide="layout-dashboard" class="w-[18px] h-[18px]"></i>
                    <span data-i18n="sidebar.dashboard">Dashboard</span>
                    <span class="ml-auto w-1.5 h-1.5 rounded-full <?= ($isDashboard) ? 'bg-white' : 'bg-transparent' ?>"></span>
                </a>
            <div class="pt-5">
                <div class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/55">Voucher</div>
                <div class="space-y-0.5">
                    <a href="/<?= htmlspecialchars($session) ?>/hotspot/users" class="flex items-center gap-3 px-3 py-2 rounded-[10px] text-[13px] font-medium transition-colors <?= (strpos($uri, '/hotspot/users') !== false) ? 'bg-white/[.18] text-white font-semibold' : 'text-white/75 hover:text-white hover:bg-white/10' ?>">
                        <i data-lucide="ticket" class="w-[18px] h-[18px]"></i>
                        <span data-i18n="access.user_accounts">Vouchers</span>
                        <span class="ml-auto w-1.5 h-1.5 rounded-full <?= (strpos($uri, '/hotspot/users') !== false) ? 'bg-white' : 'bg-transparent' ?>"></span>
                    </a>
                    <a href="/<?= htmlspecialchars($session) ?>/hotspot/generate" class="flex items-center gap-3 px-3 py-2 rounded-[10px] text-[13px] font-medium transition-colors <?= (strpos($uri, '/hotspot/generate') !== false) ? 'bg-white/[.18] text-white font-semibold' : 'text-white/75 hover:text-white hover:bg-white/10' ?>">
                        <i data-lucide="ticket-plus" class="w-[18px] h-[18px]"></i>
                        <span data-i18n="access.vouchers">Generate Vouchers</span>
                        <span class="ml-auto w-1.5 h-1.5 rounded-full <?= (strpos($uri, '/hotspot/generate') !== false) ? 'bg-white' : 'bg-transparent' ?>"></span>
                    </a>
                    <a href="/<?= htmlspecialchars($session) ?>/hotspot/profiles" class="flex items-center gap-3 px-3 py-2 rounded-[10px] text-[13px] font-medium transition-colors <?= (strpos($uri, '/hotspot/profiles') !== false) ? 'bg-white/[.18] text-white font-semibold' : 'text-white/75 hover:text-white hover:bg-white/10' ?>">
                        <i data-lucide="package-open" class="w-[18px] h-[18px]"></i>
                        <span data-i18n="access.packages">Data Plans</span>
                        <span class="ml-auto w-1.5 h-1.5 rounded-full <?= (strpos($uri, '/hotspot/profiles') !== false) ? 'bg-white' : 'bg-transparent' ?>"></span>
                    </a>
                </div>
            </div>
            <div class="pt-5">
                <div class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/55">Activity</div>
                <div class="space-y-0.5">
                    <a href="/<?= htmlspecialchars($session) ?>/hotspot/active" class="flex items-center gap-3 px-3 py-2 rounded-[10px] text-[13px] font-medium transition-colors <?= (strpos($uri, '/hotspot/active') !== false) ? 'bg-white/[.18] text-white font-semibold' : 'text-white/75 hover:text-white hover:bg-white/10' ?>">
                        <i data-lucide="users" class="w-[18px] h-[18px]"></i>
                        <span data-i18n="activity.active_users">Active Users</span>
                        <span class="ml-auto w-1.5 h-1.5 rounded-full <?= (strpos($uri, '/hotspot/active') !== false) ? 'bg-white' : 'bg-transparent' ?>"></span>
                    </a>
                    <a href="/<?= htmlspecialchars($session) ?>/reports/user-log" class="flex items-center gap-3 px-3 py-2 rounded-[10px] text-[13px] font-medium transition-colors <?= (strpos($uri, '/reports/user-log') !== false) ? 'bg-white/[.18] text-white font-semibold' : 'text-white/75 hover:text-white hover:bg-white/10' ?>">
                        <i data-lucide="scroll-text" class="w-[18px] h-[18px]"></i>
                        <span data-i18n="activity.activity_log">User Log</span>
                        <span class="ml-auto w-1.5 h-1.5 rounded-full <?= (strpos($uri, '/reports/user-log') !== false) ? 'bg-white' : 'bg-transparent' ?>"></span>
                    </a>
                </div>
            </div>
            <div class="pt-5">
                <div class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/55">Reports</div>
                <div class="space-y-0.5">
                <a href="/<?php echo htmlspecialchars($session) ?>/reports/sales" class="flex items-center gap-3 px-3 py-2 rounded-[10px] text-[13px] font-medium transition-colors <?= (strpos($uri, '/reports/sales') !== false) ? 'bg-white/[.18] text-white font-semibold' : 'text-white/75 hover:text-white hover:bg-white/10' ?>">
                    <i data-lucide="receipt" class="w-[18px] h-[18px]"></i>
                    <span data-i18n="sales.report">Sales Report</span>
                    <span class="ml-auto w-1.5 h-1.5 rounded-full <?= (strpos($uri, '/reports/sales') !== false) ? 'bg-white' : 'bg-transparent' ?>"></span>
                </a>
                </div>
            </div>
            <div class="pt-5">
                <div class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/55">Branding</div>
                <div class="space-y-0.5">
                <a href="/<?php echo htmlspecialchars($session ?? '') ?>/voucher-templates" class="flex items-center gap-3 px-3 py-2 rounded-[10px] text-[13px] font-medium transition-colors <?= ($isTemplates) ? 'bg-white/[.18] text-white font-semibold' : 'text-white/75 hover:text-white hover:bg-white/10' ?>" data-x="1"><i data-lucide="ticket" class="w-[18px] h-[18px]"></i><span data-i18n="sidebar.templates">Voucher Templates</span>
                    <span class="ml-auto w-1.5 h-1.5 rounded-full <?= ($isTemplates) ? 'bg-white' : 'bg-transparent' ?>"></span>
                </a>
                <a href="/<?php echo htmlspecialchars($session ?? '') ?>/logos" class="flex items-center gap-3 px-3 py-2 rounded-[10px] text-[13px] font-medium transition-colors <?= ($isLogos) ? 'bg-white/[.18] text-white font-semibold' : 'text-white/75 hover:text-white hover:bg-white/10' ?>" data-y="1"><i data-lucide="image" class="w-[18px] h-[18px]"></i><span data-i18n="sidebar.logos">Logos</span>
                    <span class="ml-auto w-1.5 h-1.5 rounded-full <?= ($isLogos) ? 'bg-white' : 'bg-transparent' ?>"></span>
                </a>
                </div>
            </div>
        </div>
        </div>
            <style>
    /* Sage sidebar overrides */
    #sidebar a { color: rgba(255,255,255,.85); }
    #sidebar a:hover { color: #fff; background: rgba(255,255,255,.10); }
    #sidebar a.bg-white\/40 { background: rgba(255,255,255,.16) !important; color: #fff !important; }
    #sidebar a.text-foreground { color: #fff !important; }
    #sidebar .text-accents-6 { color: rgba(255,255,255,.92) !important; }
    #sidebar .text-accents-5 { color: rgba(255,255,255,.72) !important; }
    #sidebar .text-accents-4 { color: rgba(255,255,255,.55) !important; }
    #sidebar .border-accents-2 { border-color: rgba(255,255,255,.22) !important; }
    #sidebar .bg-accents-2\/50, #sidebar .group-hover\:bg-accents-2 { background: rgba(255,255,255,.16) !important; }
    #sidebar .hover\:bg-accents-2\/50:hover { background: rgba(255,255,255,.12) !important; }
    #sidebar .bg-accents-1\/30, #sidebar .bg-accents-1\/50 { background: rgba(255,255,255,.10) !important; }
    #sidebar .hover\:bg-accents-1:hover { background: rgba(255,255,255,.12) !important; }
    #sidebar .ring-white\/10 { --tw-ring-color: rgba(255,255,255,.25); }
    /* Dorong seluruh konten melewati sidebar fixed (desktop) */
    @media (min-width: 1024px) {
        body:has(#sidebar) > *:not(#sidebar) { margin-left: 16rem; }
    }
    .session-mobile-header { background: #5f7f67 !important; backdrop-filter: none !important; }
    </style>
</aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden w-full">

    <!-- Desktop Topbar (merged dari navbar_session) -->
<!-- ===== Session Topbar ===== -->
<header class="hidden md:flex h-16 shrink-0 bg-white dark:bg-[#1a1c19] border-b border-black/[.07] dark:border-white/[.08] items-center gap-4 px-4 sm:px-6 z-10">

    <div class="hidden md:block w-full max-w-sm relative">
        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40"></i>
        <input type="search" placeholder="Search routers, users, vouchers…"
            class="w-full h-10 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-9 pr-3 text-sm outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition-colors">
    </div>

    <div class="flex items-center gap-2 ml-auto">
        <!-- Notifications -->
        <div class="relative" id="topbar-notif-wrap">
            <button type="button"
                class="relative w-10 h-10 inline-flex items-center justify-center rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 hover:border-[#92aa96] transition-colors"
                id="tb-notif-btn" aria-label="Notifications">
                <i data-lucide="bell" class="w-[18px] h-[18px]"></i>
                <span id="topbar-notif-dot" class="hidden absolute top-2 right-2.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-background"></span>
            </button>
            <div id="topbar-notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-background dark:bg-[#1a1c19] border border-black/[.08] dark:border-white/[.08] rounded-xl shadow-xl z-50 overflow-hidden">
                <div class="px-4 py-3 border-b border-black/[.06] dark:border-white/[.06] flex items-center justify-between">
                    <span class="text-sm font-bold">Notifications</span>
                    <span class="text-[11px] opacity-50">router events</span>
                </div>
                <div id="topbar-notif-list" class="max-h-72 overflow-y-auto divide-y divide-black/[.05] dark:divide-white/[.05]">
                    <p class="px-4 py-6 text-center text-xs opacity-50">Loading…</p>
                </div>
            </div>
        </div>

        <!-- Profile Dropdown -->
        <div class="relative" id="topbar-profile-wrap">
            <button type="button"
                class="flex items-center gap-2 h-10 pl-1 pr-2 rounded-xl hover:bg-black/[.04] dark:hover:bg-white/[.06] transition-colors"
                id="tb-profile-btn" aria-label="Account menu">
                <span class="w-8 h-8 rounded-full bg-[#5f7f67] text-white text-xs font-bold flex items-center justify-center uppercase"><?= $sessionInitials ?></span>
                <span class="hidden sm:block text-sm font-semibold max-w-[120px] truncate"><?= htmlspecialchars($sessionUser) ?></span>
                <i data-lucide="chevron-down" class="w-4 h-4 opacity-50"></i>
            </button>
            <div id="topbar-profile-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-background dark:bg-[#1a1c19] border border-black/[.08] dark:border-white/[.08] rounded-xl shadow-xl z-50 overflow-hidden">
                <div class="px-4 py-3 border-b border-black/[.06] dark:border-white/[.06]">
                    <p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                    <p class="text-[11px] opacity-50 truncate">Session: <?= htmlspecialchars($session ?? '-') ?></p>
                </div>
                <a href="/settings" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                    <i data-lucide="settings" class="w-4 h-4 opacity-60"></i> Settings
                </a>
                <div class="border-t border-black/[.06] dark:border-white/[.06]"></div>
                <a href="/" title="Exit session — back to Home" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                    <i data-lucide="cast" class="w-4 h-4 opacity-60"></i> Disconnect
                </a>
                <form action="/logout" method="POST"><button type="submit" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-500/[.07] transition-colors w-full text-left cursor-pointer bg-transparent border-0"><i data-lucide="log-out" class="w-4 h-4"></i> Logout</button></form>
            </div>
        </div>
    </div>
</header>

        <!-- Mobile Header (Visible only on small screens) -->
        <header class="session-mobile-header relative h-14 flex items-center justify-between px-4 border-b border-white/20 md:hidden z-20 sticky top-0">
            <!-- Kiri: hamburger -->
            <button id="mobile-menu-toggle" aria-label="Open menu"
                class="w-9 h-9 -ml-1 inline-flex items-center justify-center rounded-xl text-white hover:bg-white/10 transition-colors">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>

            <!-- Tengah: logo putih -->
            <a href="/<?= htmlspecialchars($session ?? '') ?>/dashboard" class="absolute left-1/2 -translate-x-1/2" aria-label="MIVO">
                <img src="/assets/img/logo-white.webp" class="h-6 w-auto block" alt="MIVO">
            </a>

            <!-- Kanan: avatar profile -->
            <div class="relative" id="mob-profile-wrap">
                <button type="button" id="mob-profile-btn" aria-label="Account menu"
                    class="flex items-center justify-center w-9 h-9 rounded-full bg-white text-[#47614d] shadow-sm hover:bg-white/90 transition-colors">
                    <span class="text-xs font-bold uppercase"><?= $sessionInitials ?></span>
                </button>
                <div id="mob-profile-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-background dark:bg-[#1a1c19] border border-black/[.08] dark:border-white/[.08] rounded-xl shadow-xl z-50 overflow-hidden">
                    <div class="px-4 py-3 border-b border-black/[.06] dark:border-white/[.06]">
                        <p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                        <p class="text-[11px] opacity-50 truncate">Session: <?= htmlspecialchars($session ?? '-') ?></p>
                    </div>
                    <a href="/settings" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                        <i data-lucide="settings" class="w-4 h-4 opacity-60"></i> Settings
                    </a>
                    <div class="border-t border-black/[.06] dark:border-white/[.06]"></div>
                    <a href="/" title="Exit session — back to Home" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                        <i data-lucide="cast" class="w-4 h-4 opacity-60"></i> Disconnect
                    </a>
                    <form action="/logout" method="POST"><button type="submit" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-500/[.07] transition-colors w-full text-left cursor-pointer bg-transparent border-0"><i data-lucide="log-out" class="w-4 h-4"></i> Logout</button></form>
                </div>
            </div>
        </header>

<script>
// Notifikasi topbar session: pola sama dengan navbar global
(function () {
    var loaded = false;
    document.addEventListener('click', function (e) {
        if (! e.target.closest('#topbar-notif-wrap button')) return;
        if (loaded) return;
        loaded = true;
        fetch('/api/routers/events?limit=8', { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (events) {
                var list = document.getElementById('topbar-notif-list');
                if (! events.length) { list.innerHTML = '<p class="px-4 py-6 text-center text-xs opacity-50">No activity yet.</p>'; return; }
                list.innerHTML = events.map(function (ev) {
                    var icon = ev.event_type === 'connected'   ? ['arrow-up-circle',   'text-emerald-600', 'connected']
                            : ev.event_type === 'went_offline' ? ['arrow-down-circle', 'text-red-600',      'went offline']
                            :                                    ['alert-triangle',    'text-amber-600',    'high CPU usage'];
                    return '<div class="px-4 py-3 flex items-start gap-3 text-xs">'
                        + '<i data-lucide="' + icon[0] + '" class="w-4 h-4 mt-0.5 shrink-0 ' + icon[1] + '"></i>'
                        + '<div class="flex-grow"><p class="font-medium">Router ' + escTop(ev.router_name) + ' ' + icon[2] + '</p>'
                        + '<p class="opacity-50 mt-0.5">' + escTop((ev.created_at || '').replace(' ', 'T') + 'Z').slice(11, 16) + ' UTC</p></div></div>';
                }).join('');
                var dot = document.getElementById('topbar-notif-dot');
                if (dot) dot.classList.add('hidden');
                if (window.lucide) lucide.createIcons();
            })
            .catch(function () {
                var list = document.getElementById('topbar-notif-list');
                if (list) list.innerHTML = '<p class="px-4 py-6 text-center text-xs opacity-50">Failed to load notifications.</p>';
            });
    }, true);

    function escTop(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    // Buka/tutup dropdown: satu handler eksklusif, independen dari toggleMenu global
    var pairs = [
        ['tb-notif-btn', 'topbar-notif-dropdown'],
        ['tb-profile-btn', 'topbar-profile-dropdown'],
        ['mob-profile-btn', 'mob-profile-dropdown'],
    ];
    pairs.forEach(function (pair) {
        var btn = document.getElementById(pair[0]);
        var dd = document.getElementById(pair[1]);
        if (! btn || ! dd) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var willOpen = dd.classList.contains('hidden');
            pairs.forEach(function (other) {
                var od = document.getElementById(other[1]);
                if (od && other[1] !== pair[1]) od.classList.add('hidden');
            });
            dd.classList.toggle('hidden', ! willOpen);
        });
    });

    // Tutup semua saat klik di luar area dropdown+trigger
    document.addEventListener('click', function (e) {
        pairs.forEach(function (pair) {
            var dd = document.getElementById(pair[1]);
            var btn = document.getElementById(pair[0]);
            if (! dd || dd.classList.contains('hidden')) return;
            if (! dd.contains(e.target) && ! btn.contains(e.target)) dd.classList.add('hidden');
        });
    });
})();
</script>

        <!-- Session SPA navigation (persistent; lives outside #session-dynamic) -->
        <script>
        (function () {
            if (window.__kaiarasaSessionSpa) return;
            window.__kaiarasaSessionSpa = true;

            var DYNAMIC_ID = 'session-dynamic';

            // Track external scripts already loaded on the page so SPA swaps don't
            // re-inject them (re-declaring `class X` throws a fatal SyntaxError and
            // aborts the rest of the script chain — that was breaking the selling
            // report's DataTable init after a SPA navigation).
            if (!window.__kaiarasaLoadedScripts) {
                window.__kaiarasaLoadedScripts = new Set();
                document.querySelectorAll('script[src]').forEach(function (s) {
                    try { window.__kaiarasaLoadedScripts.add(new URL(s.src, window.location.href).href); } catch (e) {}
                });
            }
            var TOP_ON = ['bg-white/[.18]', 'text-white', 'font-semibold'];
            var TOP_OFF = ['text-white/75', 'hover:text-white', 'hover:bg-white/10'];
            var SUB_ON = ['bg-white/[.18]', 'text-white', 'font-semibold'];
            var SUB_OFF = ['text-white/75', 'hover:text-white', 'hover:bg-white/10'];

            function getSession() {
                var aside = document.getElementById('sidebar');
                return aside ? (aside.getAttribute('data-session') || '') : '';
            }

            function isSessionPath(pathname, session) {
                if (!session) return false;
                return pathname === '/' + session || pathname.indexOf('/' + session + '/') === 0;
            }

            // Mirrors the PHP strpos() active checks in sidebar_session.php.
            function isLinkActive(hrefPath, pathname, session) {
                var prefix = '/' + session;
                if (hrefPath.indexOf(prefix) !== 0) return false;
                var rest = hrefPath.slice(prefix.length); // e.g. /hotspot/users
                if (!rest) return false;

                // Dashboard hanya aktif pada alamat yang persis.
                if (rest === '/dashboard') {
                    return pathname === prefix + '/dashboard';
                }

                // Halaman seksi tetap aktif pada subhalamannya (edit/detail).
                // Catatan PHP: profile (tunggal) ikut dianggap profiles.
                var probe = (rest === '/hotspot/profiles') ? '/hotspot/profile' : rest;
                return pathname.indexOf(prefix + probe) === 0;
            }

            function setClasses(el, add, remove) {
                remove.forEach(function (c) { el.classList.remove(c); });
                add.forEach(function (c) { el.classList.add(c); });
            }

            function updateSidebarActive(pathname) {
                var session = getSession();
                document.querySelectorAll('#sidebar a').forEach(function (a) {
                    var href = a.getAttribute('href') || '';
                    var u;
                    try { u = new URL(href, window.location.href); } catch (e) { return; }
                    if (!isSessionPath(u.pathname, session)) return;
                    var active = isLinkActive(u.pathname, pathname, session);
                    var inGroup = !!a.closest('[data-nav-group]');
                    var on = inGroup ? SUB_ON : TOP_ON;
                    var off = inGroup ? SUB_OFF : TOP_OFF;
                    setClasses(a, active ? on : off, active ? off : on);

                    // Titik indikator wajib mengikuti menu aktif — sebelumnya
                    // tidak disentuh SPA sehingga membeku/menumpuk.
                    var dot = a.querySelector('span.ml-auto.rounded-full');
                    if (dot) {
                        dot.classList.toggle('bg-white', active);
                        dot.classList.toggle('bg-transparent', !active);
                    }
                });
                // Expand active groups, collapse others (matches PHP initial state).
                document.querySelectorAll('#sidebar [data-nav-group]').forEach(function (menu) {
                    var hasActive = false;
                    menu.querySelectorAll('a').forEach(function (a) {
                        var href = a.getAttribute('href') || '';
                        try { var uu = new URL(href, window.location.href); if (isSessionPath(uu.pathname, session) && isLinkActive(uu.pathname, pathname, session)) hasActive = true; } catch (e) {}
                    });
                    menu.style.maxHeight = hasActive ? '500px' : '0px';
                    var btn = menu.previousElementSibling;
                    if (btn) {
                        var chev = btn.querySelector('svg.lucide-chevron-down') || btn.querySelector('[data-lucide="chevron-down"]');
                        if (chev) { chev.classList.toggle('rotate-180', hasActive); }
                    }
                });
            }

            function executeScriptsAsync(root) {
                var scripts = Array.prototype.slice.call(root.querySelectorAll('script'));
                return scripts.reduce(function (p, oldScript) {
                    return p.then(function () {
                        return new Promise(function (resolve) {
                            // Inline scripts always re-run (view init logic).
                            // External scripts run once; later swaps skip them to
                            // avoid re-declaring top-level `class`/`const` bindings.
                            if (oldScript.src) {
                                var absUrl;
                                try { absUrl = new URL(oldScript.src, window.location.href).href; } catch (e) { absUrl = oldScript.src; }
                                if (window.__kaiarasaLoadedScripts.has(absUrl)) {
                                    oldScript.parentNode.removeChild(oldScript);
                                    resolve();
                                    return;
                                }
                                window.__kaiarasaLoadedScripts.add(absUrl);
                                var s = document.createElement('script');
                                s.src = oldScript.src;
                                if (oldScript.type) { s.type = oldScript.type; }
                                s.async = false;
                                oldScript.parentNode.replaceChild(s, oldScript);
                                s.onload = resolve; s.onerror = resolve;
                            } else {
                                var inline = document.createElement('script');
                                inline.textContent = oldScript.textContent;
                                if (oldScript.type) { inline.type = oldScript.type; }
                                inline.async = false;
                                oldScript.parentNode.replaceChild(inline, oldScript);
                                resolve();
                            }
                        });
                    });
                }, Promise.resolve());
            }

            function reinitScope(root) {
                try { if (window.lucide) lucide.createIcons(); } catch (e) {}
                try { if (window.i18n && window.i18n.applyTranslations) window.i18n.applyTranslations(); } catch (e) {}
                try {
                    var SelectCtor = window.Kaiarasa && window.Kaiarasa.components && window.Kaiarasa.components.Select;
                    if (SelectCtor) {
                        root.querySelectorAll('select.custom-select').forEach(function (el) {
                            if (!SelectCtor.get(el)) { new SelectCtor(el); }
                        });
                    }
                } catch (e) {}
            }

            function cleanupSession() {
                // Sweep chart ApexCharts yang terdaftar — tanpa ini, timer
                // internal chart yatim terus mengukur DOM lepas dan membanjiri
                // console dengan "width: NaN" / "translate(NaN, 0)".
                if (window.__kaiChartRegistry) {
                    window.__kaiChartRegistry.forEach(function (c) {
                        try { c.destroy(); } catch (e) {}
                    });
                    window.__kaiChartRegistry.length = 0;
                }
                if (window.__kaiarasaSessionCleanup) {
                    try { window.__kaiarasaSessionCleanup(); } catch (e) {}
                    window.__kaiarasaSessionCleanup = null;
                }
                // Handler bahasa milik halaman sebelumnya dibuang;
                // halaman yang baru akan mendaftarkan ulang lewat helper di bawah.
                if (window.__kaiarasaLangFns) window.__kaiarasaLangFns.length = 0;
            }

            // Registry + helper pelacakan instance ApexCharts lintas halaman SPA
            // (build vendor ini tidak punya ApexCharts.getCharts).
            window.__kaiChartRegistry = window.__kaiChartRegistry || [];
            window.kaiTrackChart = function (inst) {
                if (inst) window.__kaiChartRegistry.push(inst);
                return inst;
            };

            // Pendaftaran handler 'languageChanged' bebas-duplikasi lintas navigasi SPA.
            // Satu listener window saja; fungsi dideduplikasi berdasarkan isinya.
            window.kaiarasaOnLangChange = function (fn) {
                if (!window.__kaiarasaLangListener) {
                    window.__kaiarasaLangFns = [];
                    window.__kaiarasaLangListener = function () {
                        window.__kaiarasaLangFns.forEach(function (f) { try { f(); } catch (e) {} });
                    };
                    window.addEventListener('languageChanged', window.__kaiarasaLangListener);
                }
                var sig = null;
                try { sig = fn.toString(); } catch (e) {}
                for (var i = 0; i < window.__kaiarasaLangFns.length; i++) {
                    try { if (window.__kaiarasaLangFns[i].toString() === sig) return; } catch (e2) {}
                }
                window.__kaiarasaLangFns.push(fn);
            };

            function setLoading(on) {
                var root = document.getElementById(DYNAMIC_ID);
                if (!root) return;
                for (var i = 0; i < root.children.length; i++) {
                    var kid = root.children[i];
                    if (kid.tagName === 'SCRIPT' || kid.tagName === 'TEMPLATE') continue;
                    kid.style.opacity = on ? '0.5' : '';
                    kid.style.pointerEvents = on ? 'none' : '';
                }
                document.body.style.cursor = on ? 'wait' : '';
            }

            var navSeq = 0;
            var navCtrl = null;

            function loadSession(url, push) {
                if (push === undefined) push = true;
                var u = new URL(url, window.location.href);
                u.hash = '';
                var target = u.pathname + u.search;

                var seq = ++navSeq;              // token urutan navigasi
                if (navCtrl) navCtrl.abort();    // hentikan fetch sebelumnya yang masih berjalan
                navCtrl = new AbortController();
                var signal = navCtrl.signal;

                cleanupSession();
                setLoading(true);

                // UX mobile: tutup drawer otomatis begitu menu dipilih /
                // navigasi terjadi, agar konten langsung terlihat.
                if (typeof window.kaiCloseMobileSidebar === 'function') {
                    window.kaiCloseMobileSidebar();
                }

                // Umpan balik instan: skeleton menggantikan isi selagi menunggu.
                var dynNow = document.getElementById(DYNAMIC_ID);
                if (dynNow) {
                    dynNow.innerHTML = '<div class="max-w-7xl mx-auto p-6 space-y-4">'
                        + '<div class="h-8 w-56 rounded-lg bg-black/[.05] dark:bg-white/[.06] animate-pulse"></div>'
                        + '<div class="rounded-2xl border border-black/[.06] dark:border-white/[.06] p-4 space-y-2">'
                        + Array.from({ length: 5 }, function () {
                            return '<div class="h-9 rounded-lg bg-black/[.04] dark:bg-white/[.04] animate-pulse"></div>';
                        }).join('') + '</div></div>';
                }

                fetch(target, { credentials: 'same-origin', signal: signal })
                    .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.text(); })
                    .then(function (html) {
                        if (seq !== navSeq) return; // respons basi — abaikan
                        var doc = new DOMParser().parseFromString(html, 'text/html');
                        var fresh = doc.getElementById(DYNAMIC_ID);
                        if (!fresh) { window.location.href = url; return; } // cross-layout / not a session page -> full nav
                        var current = document.getElementById(DYNAMIC_ID);
                        if (!current) { window.location.href = url; return; }
                        var imported = document.importNode(fresh, true);
                        current.replaceWith(imported);
                        updateSidebarActive(u.pathname);
                        if (doc.title) document.title = doc.title;
                        executeScriptsAsync(imported).then(function () {
                            if (seq !== navSeq) return; // basi setelah eksekusi skrip
                            reinitScope(imported);
                            if (push) history.pushState({ sessionSpa: true, url: u.href }, '', u.href);
                            setLoading(false);
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                            window.dispatchEvent(new CustomEvent('session:loaded', { detail: { url: u.href } }));
                        });
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') return; // dibatalkan oleh navigasi baru
                        console.error('[session-spa] load failed, falling back to full navigation:', err);
                        if (seq === navSeq) window.location.href = url;
                    });
            }

            document.addEventListener('click', function (e) {
                var a = e.target.closest('a');
                if (!a) return;
                var href = a.getAttribute('href');
                if (!href || href === '#' || a.target === '_blank') return;
                if (a.hasAttribute('data-no-spa') || a.closest('[data-no-spa]')) return;
                var session = getSession();
                try {
                    var u = new URL(href, window.location.href);
                    if (u.origin !== window.location.origin) return;
                    var xsw = /^\/([^\/]+)\/dashboard$/.exec(u.pathname);
                    if (!isSessionPath(u.pathname, session)) {
                        if (xsw && xsw[1] !== session && window.kaiRouteLoading) {
                            window.kaiRouteLoading(true, 'Entering ' + xsw[1] + '\u2026');
                        }
                        return; // settings/disconnect/logout -> full load
                    }
                    if (u.pathname === window.location.pathname && u.search === window.location.search) { e.preventDefault(); return; }
                    e.preventDefault();
                    loadSession(u.href, true);
                } catch (err) { /* allow default navigation */ }
            });

            window.addEventListener('popstate', function () {
                loadSession(window.location.href, false);
            });

            if (!history.state || !history.state.sessionSpa) {
                history.replaceState({ sessionSpa: true, url: window.location.href }, '', window.location.href);
            }
        })();
        </script>

        <!-- Scrollable Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-background p-4 md:p-8">
            <div class="max-w-7xl mx-auto">
            <div id="session-dynamic" class="contents kai-pop">


