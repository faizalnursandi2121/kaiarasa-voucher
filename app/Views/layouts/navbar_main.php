<?php

use App\Config\SiteConfig;
use App\Helpers\LanguageHelper;

// Determine active link state
$uri = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!-- Modern Navbar (Tailwind) -->
<nav class="sticky top-0 z-50 w-full border-b border-accents-2 bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <!-- Brand & Desktop Nav -->
            <div class="flex items-center gap-8">
                <a href="/" class="flex items-center gap-2 group">
                    <img src="/assets/img/logo-sage.webp" alt="<?= SiteConfig::APP_NAME ?> Logo" class="h-6 w-auto block dark:hidden transition-transform group-hover:scale-110">
                    <img src="/assets/img/logo-white.webp" alt="<?= SiteConfig::APP_NAME ?> Logo" class="h-6 w-auto hidden dark:block transition-transform group-hover:scale-110">
                </a>

                <!-- Desktop Navigation Links (Hidden on Mobile) -->
                <?php if (isset($_SESSION['user_id'])) { ?>
                <div class="hidden md:flex items-center gap-6 text-sm font-medium">
                    <a href="/" data-app-nav data-nav-path="/" data-on="text-foreground after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-foreground" data-off="text-accents-5 hover:text-foreground transition-colors" class="relative py-1 <?= ($uri == '/' || $uri == '/home') ? 'text-foreground after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-foreground' : 'text-accents-5 hover:text-foreground transition-colors' ?>">Home</a>
                    <a href="/settings" data-app-nav data-nav-path="/settings" data-on="text-foreground after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-foreground" data-off="text-accents-5 hover:text-foreground transition-colors" class="relative py-1 <?= (strpos($uri, '/settings') === 0) ? 'text-foreground after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-foreground' : 'text-accents-5 hover:text-foreground transition-colors' ?>">Settings</a>
                </div>
                <?php } ?>
            </div>
            
            <!-- Right side controls -->
            <div class="flex items-center gap-3">
                <!-- Desktop Control Pill (Hidden on Mobile) -->
                <div class="hidden md:flex control-pill scale-95 hover:scale-100 transition-transform">
                    <!-- Language Switcher -->
                    <div class="relative group" onmouseleave="closeMenu('lang-dropdown-desktop')">
                        <button type="button" class="pill-lang-btn" onclick="toggleMenu('lang-dropdown-desktop', this)" title="Change Language">
                             <i data-lucide="languages" class="w-4 h-4"></i>
                        </button>
                         <div id="lang-dropdown-desktop" class="absolute right-0 top-full mt-3 w-48 bg-background/95 backdrop-blur-2xl border border-accents-2 rounded-xl shadow-xl overflow-hidden transition-all duration-200 ease-out origin-top-right opacity-0 scale-95 invisible pointer-events-none z-50 dropdown-bridge">
                            <div class="px-3 py-2 text-[10px] font-bold text-accents-4 uppercase tracking-widest border-b border-accents-2/50 bg-accents-1/50" data-i18n="sidebar.switch_language">Select Language</div>
                            <?php
                            $languages = LanguageHelper::getAvailableLanguages();
foreach ($languages as $lang) {
    $pathArg = isset($lang['path']) ? "', '".$lang['path'] : '';
    ?>
                            <button onclick="Kaiarasa.modules.I18n.loadLanguage('<?= $lang['code'] ?><?= $pathArg ?>')" class="w-full text-left flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-accents-1 transition-colors text-accents-6 hover:text-foreground group/lang">
                                <span class="fi fi-<?= $lang['flag'] ?> rounded-sm shadow-sm transition-transform group-hover/lang:scale-110"></span>
                                <span><?= $lang['name'] ?></span>
                            </button>
                            <?php } ?>
                        </div>
                    </div>

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

                    <?php if (isset($_SESSION['user_id'])) { ?>
                        <div class="pill-divider"></div>
                        <a href="/logout" class="p-1.5 rounded-lg text-accents-5 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all ml-0.5" title="Logout">
                            <i data-lucide="log-out" class="w-4 h-4 !text-black dark:!text-white" stroke-width="2.5"></i>
                        </a>
                    <?php } ?>
                </div>

                <!-- Mobile Menu Toggles -->
                <div class="flex md:hidden items-center gap-2">
                    <!-- Mobile Mode Control Pill (Condensed) -->
                    <div class="control-pill py-1.5 px-2">
                         <div class="segmented-switch theme-toggle scale-75" title="Toggle Theme">
                            <div class="segmented-switch-slider"></div>
                            <div class="segmented-switch-btn theme-toggle-light-icon"><i data-lucide="sun" class="w-4 h-4" stroke-width="3.5"></i></div>
                            <div class="segmented-switch-btn theme-toggle-dark-icon"><i data-lucide="moon" class="w-4 h-4" stroke-width="3.5"></i></div>
                        </div>
                    </div>

                    <button type="button" class="p-2 rounded-lg bg-accents-1 text-accents-5 hover:text-foreground transition-colors group" onclick="toggleMenu('mobile-navbar-menu', this)">
                        <i data-lucide="menu" class="w-5 h-5 !text-black dark:!text-white transition-transform duration-300" stroke-width="2.5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Drawer (Hidden by default) -->
    <div id="mobile-navbar-menu" class="md:hidden border-t border-accents-2 bg-background/95 backdrop-blur-xl transition-all duration-300 ease-in-out max-h-0 opacity-0 invisible overflow-hidden">
        <div class="px-4 pt-4 pb-6 space-y-4">
            <!-- Nav Links -->
            <?php if (isset($_SESSION['user_id'])) { ?>
            <div class="flex flex-col gap-1">
                <a href="/" data-app-nav data-nav-path="/" data-on="bg-foreground/5 text-foreground font-bold" data-off="text-accents-5 hover:bg-accents-1" class="flex items-center gap-3 px-4 py-3 rounded-xl <?= ($uri == '/' || $uri == '/home') ? 'bg-foreground/5 text-foreground font-bold' : 'text-accents-5 hover:bg-accents-1' ?>">
                    <i data-lucide="home" class="w-5 h-5 !text-black dark:!text-white" stroke-width="2.5"></i>
                    <span>Home</span>
                </a>
                <a href="/settings" data-app-nav data-nav-path="/settings" data-on="bg-foreground/5 text-foreground font-bold" data-off="text-accents-5 hover:bg-accents-1" class="flex items-center gap-3 px-4 py-3 rounded-xl <?= (strpos($uri, '/settings') === 0) ? 'bg-foreground/5 text-foreground font-bold' : 'text-accents-5 hover:bg-accents-1' ?>">
                    <i data-lucide="settings" class="w-5 h-5 !text-black dark:!text-white" stroke-width="2.5"></i>
                    <span>Settings</span>
                </a>
            </div>
            <?php } ?>

            <!-- Mobile Controls Overlay -->
            <div class="p-4 rounded-2xl bg-accents-1/50 border border-accents-2 space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-accents-4 uppercase tracking-wider">Select Language</span>
                </div>
                <div class="flex items-center gap-2 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide snap-x">
                    <?php foreach ($languages as $lang) {
                        $pathArg = isset($lang['path']) ? "', '".$lang['path'] : '';
                        ?>
                    <button onclick="changeLanguage('<?= $lang['code'] ?><?= $pathArg ?>')" class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-full border border-accents-2 bg-background hover:border-foreground transition-all text-sm font-medium snap-start shadow-sm">
                        <span class="fi fi-<?= $lang['flag'] ?> rounded-full shadow-sm"></span>
                        <span class="whitespace-nowrap"><?= $lang['name'] ?></span>
                    </button>
                    <?php } ?>
                </div>

                <?php if (isset($_SESSION['user_id'])) { ?>
                <div class="pt-2 border-t border-accents-2">
                    <a href="/logout" class="flex items-center justify-center gap-2 w-full px-4 py-3 rounded-xl bg-red-500/10 text-red-600 font-bold hover:bg-red-500/20 transition-all">
                        <i data-lucide="log-out" class="w-5 h-5 !text-black dark:!text-white" stroke-width="2.5"></i>
                        <span>Logout System</span>
                    </a>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>

<script>
// ===== Unified in-app SPA navigation (Home <-> Settings <-> settings sub-nav) =====
// The navbar persists; only #app-dynamic is swapped. Document-level delegation
// keeps it working after #app-dynamic is replaced. Scoped to global app routes
// ("/", "/home", "/settings*"); session dashboard links fall back to full load.
(function () {
    if (window.__kaiarasaAppSpa) return;
    window.__kaiarasaAppSpa = true;

    var DYNAMIC_ID = 'app-dynamic';

    function isGlobalAppPath(pathname) {
        return pathname === '/' || pathname === '/home' ||
               pathname === '/settings' || pathname.indexOf('/settings/') === 0;
    }

    function isActivePath(itemPath, currentPath) {
        if (itemPath === '/' || itemPath === '/home') {
            return currentPath === '/' || currentPath === '/home';
        }
        if (itemPath === '/settings') {
            return currentPath === '/settings' || currentPath.indexOf('/settings/') === 0;
        }
        return currentPath.indexOf(itemPath) === 0;
    }

    function setActiveNav(pathname) {
        document.querySelectorAll('[data-app-nav]').forEach(function (a) {
            var p = a.getAttribute('data-nav-path') || '/';
            var on = (a.getAttribute('data-on') || '').split(/\s+/);
            var off = (a.getAttribute('data-off') || '').split(/\s+/);
            var active = isActivePath(p, pathname);
            on.forEach(function (c) { if (c) a.classList.remove(c); });
            off.forEach(function (c) { if (c) a.classList.remove(c); });
            (active ? on : off).forEach(function (c) { if (c) a.classList.add(c); });
        });
    }

    function executeScripts(root) {
        root.querySelectorAll('script').forEach(function (oldScript) {
            var s = document.createElement('script');
            if (oldScript.src) { s.src = oldScript.src; }
            else { s.textContent = oldScript.textContent; }
            if (oldScript.type) { s.type = oldScript.type; }
            s.async = false;
            oldScript.parentNode.replaceChild(s, oldScript);
        });
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

    function loadApp(url, push) {
        if (push === undefined) push = true;
        var u = new URL(url, window.location.href);
        u.hash = '';
        var target = u.pathname + u.search;
        setLoading(true);
        fetch(target, { credentials: 'same-origin' })
            .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var fresh = doc.getElementById(DYNAMIC_ID);
                if (!fresh) { window.location.href = url; return; }
                var current = document.getElementById(DYNAMIC_ID);
                if (!current) { window.location.href = url; return; }
                var imported = document.importNode(fresh, true);
                current.replaceWith(imported);
                executeScripts(imported);
                reinitScope(imported);
                if (push) history.pushState({ appSpa: true, url: u.href }, '', u.href);
                setActiveNav(u.pathname);
                if (doc.title) document.title = doc.title;
                setLoading(false);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                window.dispatchEvent(new CustomEvent('app:loaded', { detail: { url: u.href } }));
            })
            .catch(function (err) {
                console.error('[app-spa] load failed, falling back to full navigation:', err);
                window.location.href = url;
            });
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        var href = a.getAttribute('href');
        if (!href || href === '#' || a.target === '_blank') return;
        if (a.hasAttribute('data-no-spa') || a.closest('[data-no-spa]')) return;
        try {
            var u = new URL(href, window.location.href);
            if (u.origin !== window.location.origin) return;
            if (!isGlobalAppPath(u.pathname)) return;
            if (u.pathname === window.location.pathname && u.search === window.location.search) {
                e.preventDefault(); // same page
                return;
            }
            e.preventDefault();
            loadApp(u.href, true);
        } catch (err) { /* allow default navigation */ }
    });

    window.addEventListener('popstate', function () {
        loadApp(window.location.href, false);
    });

    if (!history.state || !history.state.appSpa) {
        history.replaceState({ appSpa: true, url: window.location.href }, '', window.location.href);
    }
})();
</script>
