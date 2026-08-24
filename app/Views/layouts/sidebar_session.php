<?php

use App\Helpers\LanguageHelper;
use App\Models\Config;

// Determine active link state
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$isDashboard = strpos($uri, '/dashboard') !== false;
$isGenerate = strpos($uri, '/hotspot/generate') !== false;
$isTemplates = strpos($uri, '/voucher-templates') !== false;
$isLogos = strpos($uri, '/logos') !== false;
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
$statusPages = ['/hotspot/active', '/hotspot/hosts'];
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
    <aside id="sidebar" data-session="<?= htmlspecialchars($session ?? '') ?>" class="w-64 flex-shrink-0 border-r border-white/20 dark:border-accents-2 dark:border-white/10 bg-[#5f7f67] fixed md:static inset-y-0 left-0 z-40 transform -translate-x-full md:translate-x-0 transition-transform duration-200 flex flex-col h-full">
        <!-- Sidebar Header -->
        <!-- Sidebar Header -->
        <div id="sidebar-header" class="group flex flex-col items-center py-5 border-b border-accents-2 flex-shrink-0 relative cursor-default overflow-hidden">
            <div class="relative w-full h-10 flex items-center justify-center">
                <!-- Brand (Slides out to the Left) -->
                <div class="flex items-center gap-2 font-bold text-2xl tracking-tighter transition-all duration-500 ease-in-out group-hover:-translate-x-full group-hover:opacity-0">
                    <img src="/assets/img/logo-sage.webp" alt="Kaiarasa Logo" width="120" height="32" class="h-10 w-auto block dark:hidden">
                    <img src="/assets/img/logo-white.webp" alt="Kaiarasa Logo" width="120" height="32" class="h-10 w-auto hidden dark:block">
                </div>

                <!-- Premium Control Pill (Slides in from the Right to replace Brand) -->
                <div class="absolute inset-0 hidden md:flex items-center justify-center transition-all duration-500 ease-in-out translate-x-full opacity-0 group-hover:translate-x-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto z-10">
                    <div class="control-pill scale-90 transition-transform hover:scale-100 shadow-lg bg-white/10 dark:bg-black/20 backdrop-blur-md">
                        <!-- Language Switcher -->
                        <!-- Language Switcher (Kaiarasa Component) -->
                        <!-- Language Switcher -->
                        <div class="pill-divider"></div>

                        <!-- Theme Toggle (Segmented) -->
                        <div class="segmented-switch theme-toggle" title="Toggle Theme">
                            <div class="segmented-switch-slider"></div>
                            <div class="segmented-switch-btn theme-toggle-light-icon">
                                <i data-lucide="sun" class="w-4 h-4" stroke-width="3.5"></i>
                            </div>
                            <div class="segmented-switch-btn theme-toggle-dark-icon">
                                <i data-lucide="moon" class="w-4 h-4" stroke-width="3.5"></i>
                            </div>
                        </div>
                    </div>
                </div>
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
            <!-- Brand -->
            <div class="px-3 mb-5">
                <img src="/assets/img/logo-white.webp" alt="Kaiarasa" class="h-9 w-auto drop-shadow">
            </div>

            <!-- Session Switcher -->
            <div class="px-3 mb-6 relative" onmouseleave="closeMenu('session-dropdown')">
                <button type="button" class="w-full group grid grid-cols-[auto_1fr_auto] items-center gap-3 px-4 py-2.5 rounded-xl bg-white/50 dark:bg-white/5 border border-accents-2 dark:border-accents-2 dark:border-white/10 hover:bg-white/80 dark:hover:bg-white/10 transition-all decoration-0 overflow-hidden shadow-sm" onclick="toggleMenu('session-dropdown', this)">
                    <!-- Initials -->
                    <div class="h-8 w-8 rounded-lg bg-accents-2/50 group-hover:bg-accents-2 flex items-center justify-center text-xs font-bold text-accents-6 group-hover:text-foreground transition-colors flex-shrink-0">
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
                    <div class="h-8 w-8 flex-shrink-0 flex items-center justify-center rounded-lg bg-accents-2/50 group-hover:bg-accents-2 transition-colors">
                        <i data-lucide="chevrons-up-down" class="!w-4 !h-4 !text-accents-6 dark:!text-accents-6 transition-colors"></i>
                    </div>
                </button>

                <!-- Dropdown -->
                <div id="session-dropdown" class="absolute top-full left-3 w-[calc(100%-1.5rem)] z-50 mt-1 bg-background border border-accents-2 rounded-lg shadow-lg overflow-hidden transition-all duration-200 ease-out origin-top opacity-0 scale-95 invisible pointer-events-none dropdown-bridge" onmouseenter="if(typeof menuTimeout !== 'undefined') clearTimeout(menuTimeout)">
                    <div class="py-1 max-h-60 overflow-y-auto">
                        <div class="px-3 py-2 text-xs font-semibold text-accents-5 uppercase tracking-wider bg-accents-1/50 border-b border-accents-2" data-i18n="sidebar.switch_session">
                            Switch Session
                        </div>
                        <?php foreach ($allSessions as $s) { ?>
                        <a href="/<?= htmlspecialchars($s['session_name']) ?>/dashboard" class="flex items-center gap-3 px-3 py-2 text-sm hover:bg-accents-1 transition-colors group/item">
                            <div class="h-6 w-6 rounded flex-shrink-0 bg-accents-2 flex items-center justify-center text-[10px] font-bold">
                                 <?= $getInitials($s['session_name']) ?>
                            </div>
                            <div class="flex flex-col overflow-hidden">
                                <span class="truncate <?= ($session === $s['session_name']) ? 'font-medium text-foreground' : 'text-accents-5 group-hover/item:text-foreground' ?>">
                                    <?= htmlspecialchars($s['session_name']) ?>
                                </span>
                                <span class="text-[10px] text-accents-4 truncate">
                                    <?= htmlspecialchars($s['hotspot_name'] ?: $s['ip_address']) ?>
                                </span>
                            </div>
                             <?php if ($session === $s['session_name']) { ?>
                                <i data-lucide="check" class="w-3 h-3 ml-auto text-primary"></i>
                            <?php } ?>
                        </a>
                        <?php } ?>
                    </div>
                    <div class="border-t border-accents-2 p-1 bg-accents-1/30">
                         <a href="/settings/add" class="flex items-center gap-2 px-3 py-2 text-sm hover:bg-accents-2 rounded-md transition-colors text-accents-5 hover:text-foreground">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                            <span data-i18n="settings.add_router"><?= LanguageHelper::t('settings.add_router', 'Connect Router') ?></span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dashboard -->
            <a href="/<?= htmlspecialchars($session) ?>/dashboard" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?= $isDashboard ? 'bg-white/40 dark:bg-white/5 shadow-sm text-foreground ring-1 ring-white/10' : 'text-accents-6 hover:text-foreground hover:bg-white/5' ?>">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                <span data-i18n="sidebar.dashboard"><?= LanguageHelper::t('sidebar.dashboard', 'Dashboard') ?></span>
            </a>

            <!-- Access -->
            <a href="/<?= htmlspecialchars($session) ?>/hotspot/users" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?= (strpos($uri, '/hotspot/users') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10' : 'text-accents-6 hover:text-foreground hover:bg-white/5' ?>">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span data-i18n="access.user_accounts">User Accounts</span>
            </a>

            <a href="/<?= htmlspecialchars($session) ?>/hotspot/generate" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?= (strpos($uri, '/hotspot/generate') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10' : 'text-accents-6 hover:text-foreground hover:bg-white/5' ?>">
                <i data-lucide="ticket-plus" class="w-4 h-4"></i>
                <span data-i18n="access.vouchers">Vouchers</span>
            </a>

            <a href="/<?= htmlspecialchars($session) ?>/hotspot/profiles" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?= (strpos($uri, '/hotspot/profile') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10' : 'text-accents-6 hover:text-foreground hover:bg-white/5' ?>">
                <i data-lucide="package-open" class="w-4 h-4"></i>
                <span data-i18n="access.packages">Access Packages</span>
            </a>
            <!-- Activity -->
            <a href="/<?= htmlspecialchars($session) ?>/hotspot/active" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?= (strpos($uri, '/hotspot/active') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10' : 'text-accents-6 hover:text-foreground hover:bg-white/5' ?>">
                <i data-lucide="user-check" class="w-4 h-4"></i>
                <span data-i18n="activity.active_users">Active Users</span>
            </a>

            <a href="/<?= htmlspecialchars($session) ?>/hotspot/hosts" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?= (strpos($uri, '/hotspot/hosts') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10' : 'text-accents-6 hover:text-foreground hover:bg-white/5' ?>">
                <i data-lucide="monitor-smartphone" class="w-4 h-4"></i>
                <span data-i18n="activity.devices">Connected Devices</span>
            </a>

            <a href="/<?= htmlspecialchars($session) ?>/reports/user-log" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?= (strpos($uri, '/reports/user-log') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10' : 'text-accents-6 hover:text-foreground hover:bg-white/5' ?>">
                <i data-lucide="scroll-text" class="w-4 h-4"></i>
                <span data-i18n="activity.activity_log">Activity Log</span>
            </a>
            <!-- Sales Separator -->
             <div class="pt-4 pb-1 px-3">
                <div class="text-xs font-semibold text-accents-5 uppercase tracking-wider" data-i18n="sidebar.sales">Sales</div>
            </div>

            <a href="/<?= htmlspecialchars($session) ?>/reports/sales" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition-colors <?= (strpos($uri, '/reports/sales') !== false) ? 'bg-white/40 dark:bg-white/5 shadow-sm text-foreground ring-1 ring-white/10' : 'text-accents-6 hover:text-foreground hover:bg-white/5' ?>">
                <i data-lucide="receipt" class="w-4 h-4"></i>
                <span data-i18n="sales.report">Sales Report</span>
            </a>

            <!-- Administration Separator -->
             <div class="pt-4 pb-1 px-3">
                <div class="text-xs font-semibold text-accents-5 uppercase tracking-wider" data-i18n="sidebar.administration">Administration</div>
            </div>
             <!-- Administration Group (Collapsible) -->
             <div class="space-y-1">
                <button type="button" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium transition-colors text-accents-5 hover:text-foreground hover:bg-accents-2/50 group" onclick="toggleMenu('administration-menu', this)" aria-expanded="" aria-controls="administration-menu">
                    <div class="flex items-center gap-3">
                        <i data-lucide="settings-2" class="w-4 h-4"></i>
                        <span data-i18n="sidebar.administration"><?= LanguageHelper::t('sidebar.administration', 'Administration') ?></span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300 <?= $isSecurityActive ? 'rotate-180' : '' ?>"></i>
                </button>

                <div id="administration-menu" data-nav-group class="space-y-1 pl-9 overflow-hidden transition-[max-height] duration-300 ease-in-out" style="max-height: <?= $isSecurityActive ? '800px' : '0px' ?>">
                    <a href="/<?= htmlspecialchars($session ?? '') ?>/voucher-templates" class="block px-3 py-2 rounded-md text-sm transition-colors <?= $isTemplates ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10 font-medium' : 'text-accents-6 hover:text-foreground' ?>">
                        <span data-i18n="sidebar.templates"><?= LanguageHelper::t('sidebar.templates', 'Voucher Templates') ?></span>
                    </a>
                    <a href="/<?= htmlspecialchars($session ?? '') ?>/logos" class="block px-3 py-2 rounded-md text-sm transition-colors <?= $isLogos ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10 font-medium' : 'text-accents-6 hover:text-foreground' ?>">
                        <span data-i18n="sidebar.logos"><?= LanguageHelper::t('sidebar.logos', 'Logos') ?></span>
                    </a>
                    <a href="/<?= htmlspecialchars($session) ?>/network/dhcp" class="block px-3 py-2 rounded-md text-sm transition-colors <?= (strpos($uri, '/network/dhcp') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10 font-medium' : 'text-accents-6 hover:text-foreground' ?>">
                        <span data-i18n="sidebar.network"><?= LanguageHelper::t('sidebar.network', 'Network — DHCP') ?></span>
                    </a>
                    <a href="/<?= htmlspecialchars($session) ?>/hotspot/bindings" class="block px-3 py-2 rounded-md text-sm transition-colors <?= (strpos($uri, '/hotspot/bindings') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10 font-medium' : 'text-accents-6 hover:text-foreground' ?>">
                        <span data-i18n="hotspot_menu.bindings"><?= LanguageHelper::t('hotspot_menu.bindings', 'IP Bindings') ?></span>
                    </a>
                    <a href="/<?= htmlspecialchars($session) ?>/hotspot/walled-garden" class="block px-3 py-2 rounded-md text-sm transition-colors <?= (strpos($uri, '/hotspot/walled-garden') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10 font-medium' : 'text-accents-6 hover:text-foreground' ?>">
                        <span data-i18n="hotspot_menu.walled_garden"><?= LanguageHelper::t('hotspot_menu.walled_garden', 'Walled Garden') ?></span>
                    </a>
                    <a href="/<?= htmlspecialchars($session) ?>/system/scheduler" class="block px-3 py-2 rounded-md text-sm transition-colors <?= (strpos($uri, '/system/scheduler') !== false) ? 'bg-white/40 dark:bg-white/5 text-foreground ring-1 ring-white/10 font-medium' : 'text-accents-6 hover:text-foreground' ?>">
                        <span data-i18n="system_menu.scheduler"><?= LanguageHelper::t('system_menu.scheduler', 'Scheduler') ?></span>
                    </a>
                    <button onclick="confirmAction('/<?= htmlspecialchars($session) ?>/system/reboot', 'Reboot Router?')" class="w-full text-left block px-3 py-2 rounded-md text-sm text-accents-5 hover:text-red-500 transition-colors">
                        <span data-i18n="system_menu.reboot"><?= LanguageHelper::t('system_menu.reboot', 'Reboot') ?></span>
                    </button>
                    <button onclick="confirmAction('/<?= htmlspecialchars($session) ?>/system/shutdown', 'Shutdown Router?')" class="w-full text-left block px-3 py-2 rounded-md text-sm text-accents-5 hover:text-red-500 transition-colors">
                        <span data-i18n="system_menu.shutdown"><?= LanguageHelper::t('system_menu.shutdown', 'Shutdown') ?></span>
                    </button>
                </div>
            </div>

            <!-- Settings -->



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
    .session-mobile-header { background: #5f7f67 !important; backdrop-filter: none !important; }
    </style>
</aside>

    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden w-full">
        <!-- Mobile Header (Visible only on small screens) -->
        <header class="session-mobile-header h-14 flex items-center justify-between px-4 border-b border-white/20 md:hidden z-20 sticky top-0">
             <div class="flex items-center gap-2">
                <img src="/assets/img/logo-sage.webp" class="h-6 w-auto block dark:hidden">
                <img src="/assets/img/logo-white.webp" class="h-6 w-auto hidden dark:block">
            </div>
            <div class="flex items-center gap-4">
                <!-- Mobile Premium Control Pill -->
                <div class="control-pill scale-90 origin-right transition-transform hover:scale-95">
                    <!-- Language Switcher -->
                    <div class="relative group">
                        <button type="button" class="pill-lang-btn" onclick="toggleMenu('lang-dropdown-mobile', this)" aria-expanded="false" aria-controls="lang-dropdown-mobile" title="Change Language">
                             <i data-lucide="languages" class="w-4 h-4"></i>
                        </button>
                         <div id="lang-dropdown-mobile" class="absolute right-0 top-full mt-3 w-48 bg-background/90 backdrop-blur-xl border border-accents-2 rounded-xl shadow-xl overflow-hidden transition-all duration-200 ease-out origin-top-right opacity-0 scale-95 invisible pointer-events-none z-50 dropdown-bridge" onmouseenter="if(typeof menuTimeout !== 'undefined') clearTimeout(menuTimeout)">
                            <div class="px-3 py-2 text-[10px] font-bold text-accents-4 uppercase tracking-widest border-b border-accents-2/50 bg-accents-1/50" data-i18n="sidebar.switch_language"><?= LanguageHelper::t('sidebar.switch_language', 'Select Language') ?></div>
                            <?php
                            $languages = LanguageHelper::getAvailableLanguages();
foreach ($languages as $lang) {
    ?>
                            <button onclick="changeLanguage('<?= $lang['code'] ?>')" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-accents-1 transition-colors text-accents-6 hover:text-foreground group/lang">
                                <span class="fi fi-<?= $lang['flag'] ?> rounded-sm shadow-sm transition-transform group-hover/lang:scale-110"></span>
                                <span><?= $lang['name'] ?></span>
                            </button>
                            <?php } ?>
                        </div>
                    </div>

                    <div class="pill-divider"></div>

                    <!-- Theme Toggle (Segmented) -->
                    <div class="segmented-switch theme-toggle" title="Toggle Theme">
                        <div class="segmented-switch-slider"></div>
                        <div class="segmented-switch-btn theme-toggle-light-icon">
                            <i data-lucide="sun" class="w-4 h-4" stroke-width="3.5"></i>
                        </div>
                        <div class="segmented-switch-btn theme-toggle-dark-icon">
                            <i data-lucide="moon" class="w-4 h-4" stroke-width="3.5"></i>
                        </div>
                    </div>
                </div>
                 <button id="mobile-menu-toggle" aria-label="Open menu" class="text-accents-5 hover:text-foreground">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                 </button>
            </div>
        </header>

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
            var TOP_ON = ['bg-white/40', 'dark:bg-white/5', 'shadow-sm', 'text-foreground', 'ring-1', 'ring-white/10'];
            var TOP_OFF = ['text-accents-6', 'hover:text-foreground', 'hover:bg-white/5'];
            var SUB_ON = ['bg-white/40', 'dark:bg-white/5', 'text-foreground', 'ring-1', 'ring-white/10', 'font-medium'];
            var SUB_OFF = ['text-accents-6', 'hover:text-foreground'];

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
                // PHP matches /hotspot/profile (singular) for both profile & profiles.
                var match = (rest === '/hotspot/profiles') ? '/hotspot/profile' : rest;
                return pathname.indexOf(match) !== -1;
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
                if (window.__kaiarasaSessionCleanup) {
                    try { window.__kaiarasaSessionCleanup(); } catch (e) {}
                    window.__kaiarasaSessionCleanup = null;
                }
            }

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

            function loadSession(url, push) {
                if (push === undefined) push = true;
                var u = new URL(url, window.location.href);
                u.hash = '';
                var target = u.pathname + u.search;
                cleanupSession();
                setLoading(true);
                fetch(target, { credentials: 'same-origin' })
                    .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.text(); })
                    .then(function (html) {
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
                            reinitScope(imported);
                            if (push) history.pushState({ sessionSpa: true, url: u.href }, '', u.href);
                            setLoading(false);
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                            window.dispatchEvent(new CustomEvent('session:loaded', { detail: { url: u.href } }));
                        });
                    })
                    .catch(function (err) {
                        console.error('[session-spa] load failed, falling back to full navigation:', err);
                        window.location.href = url;
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
                    if (!isSessionPath(u.pathname, session)) return; // settings/disconnect/logout -> full load
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
            <div id="session-dynamic" class="contents">


