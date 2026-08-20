<?php
$uri = $_SERVER['REQUEST_URI'];
function isActive($path, $current)
{
    if ($path === '/settings') {
        // Routers is the new home. Active if exactly /settings or /settings/routers
        return $current === '/settings' || $current === '/settings/' || strpos($current, '/settings/routers') !== false;
    }

    return strpos($current, $path) !== false;
}

$menu = [
    ['label' => 'routers_title', 'url' => '/settings', 'namespace' => 'settings'],
    ['label' => 'system', 'url' => '/settings/system', 'namespace' => 'settings'],
    ['label' => 'templates_title', 'url' => '/settings/voucher-templates', 'namespace' => 'settings'],
    ['label' => 'logos_title', 'url' => '/settings/logos', 'namespace' => 'settings'],
    ['label' => 'api_cors_title', 'url' => '/settings/api-cors', 'namespace' => 'settings'],
    ['label' => 'plugins_title', 'url' => '/settings/plugins', 'namespace' => 'settings'],
];
?>
<nav id="settings-sidebar" class="w-full sticky top-[64px] z-40 bg-background/95 backdrop-blur border-b border-accents-2 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 md:px-8"> <!-- Aligned with header_main max-w-7xl -->
        <div class="relative py-2 flex items-start gap-2">
            
            <!-- Menu Container (Toggles between flex-row/scroll and grid) -->
            <div id="sub-navbar-menu" class="flex-1 flex flex-row items-center overflow-x-auto no-scrollbar mask-fade-right gap-2 transition-all duration-300">
                <?php foreach ($menu as $item) {
                    $active = isActive($item['url'], $uri);
                    ?>
                <a href="<?= $item['url'] ?>" 
                   class="sub-nav-item whitespace-nowrap px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 border border-transparent 
                   <?= $active ? 'bg-foreground text-background shadow-sm' : 'text-accents-5 hover:text-foreground hover:bg-accents-1' ?>"
                   data-i18n="<?= ($item['namespace'] ?? 'settings').'.'.$item['label'] ?>">
                    <?= $item['label'] ?>
                </a>
                <?php } ?>
            </div>

            <!-- Toggle Button -->
            <button id="sub-navbar-toggle" class="flex-shrink-0 p-2 text-accents-5 hover:text-foreground hover:bg-accents-1 rounded-full transition-colors hidden sm:block" title="Expand Menu">
                <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300"></i>
            </button>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggleBtn = document.getElementById('sub-navbar-toggle');
        const menu = document.getElementById('sub-navbar-menu');
        const icon = toggleBtn?.querySelector('i');
        let isExpanded = false;

        if (toggleBtn && menu) {
            // Check if content overflows to decide if we even show the toggle initially?
            // For now, always show it on sm+ screens if desired, or we can check scrollWidth > clientWidth.
            // Let's keep it simple: always available on desktop/tablet to see full grid.

            toggleBtn.addEventListener('click', () => {
                isExpanded = !isExpanded;
                
                if (isExpanded) {
                    // Expand: Grid Layout
                    menu.classList.remove('flex-row', 'overflow-x-auto', 'whitespace-nowrap', 'mask-fade-right', 'items-center');
                    menu.classList.add('grid', 'grid-cols-2', 'sm:grid-cols-3', 'md:grid-cols-4', 'lg:grid-cols-5', 'gap-2', 'pb-4');
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    // Collapse: Scroll Layout
                    menu.classList.add('flex-row', 'overflow-x-auto', 'whitespace-nowrap', 'mask-fade-right', 'items-center');
                    menu.classList.remove('grid', 'grid-cols-2', 'sm:grid-cols-3', 'md:grid-cols-4', 'lg:grid-cols-5', 'gap-2', 'pb-4');
                    icon.style.transform = 'rotate(0deg)';
                    
                    // Reset scroll position to start? or keep?
                    menu.scrollLeft = 0;
                }
            });
        }
    });

    // Re-run Lucide mainly for the chevron if this is loaded via PJAX (though sidebar is usually persistent in SPA layout? 
    // Wait, in PJAX we replace content, not the sidebar if it's outside. 
    // BUT sidebar_settings.php is INSIDE the view in the current PHP architecture.
    // So it gets re-rendered on every navigation if we don't change that.
    // The current SPA script replaces `#settings-content-area`.
    // We need to move the sidebar OUT of the `#settings-content-area` target in the PHP files if we want it to persist...
    // OR we just re-init the script. Since it's inline, it runs on content injection.
    
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>

<script>
// ===== Settings SPA navigation (PJAX-style content swap) =====
// Intercepts clicks on the settings sub-nav so only #settings-dynamic is
// swapped instead of doing a full page reload. The sub-nav lives outside
// #settings-dynamic, so this listener persists across swaps.
(function () {
    if (window.__mivoSettingsSpa) return;
    window.__mivoSettingsSpa = true;

    var menu = document.getElementById('sub-navbar-menu');
    if (!menu) return;

    var DYNAMIC_ID = 'settings-dynamic';

    function isSettingsPath(pathname) {
        return pathname === '/settings' || pathname.indexOf('/settings/') === 0;
    }

    // Mirror of PHP isActive() in this file.
    function isActivePath(itemPath, currentPath) {
        if (itemPath === '/settings') {
            return currentPath === '/settings' || currentPath === '/settings/' ||
                   currentPath.indexOf('/settings/routers') === 0 ||
                   currentPath.indexOf('/settings/add') === 0;
        }
        return currentPath.indexOf(itemPath) === 0;
    }

    function setActiveNav(pathname) {
        menu.querySelectorAll('.sub-nav-item').forEach(function (a) {
            var itemPath = a.getAttribute('href') || '';
            var active = isActivePath(itemPath, pathname);
            ['bg-foreground', 'text-background', 'shadow-sm'].forEach(function (c) {
                a.classList.toggle(c, active);
            });
            ['text-accents-5', 'hover:text-foreground', 'hover:bg-accents-1'].forEach(function (c) {
                a.classList.toggle(c, !active);
            });
        });
    }

    // Re-execute inline <script> tags inside the swapped fragment
    // (imported nodes do not run scripts automatically).
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

    // Re-initialise scoped components/luxuries after a swap.
    function reinitScope(root) {
        try { if (window.lucide) lucide.createIcons(); } catch (e) {}
        try { if (window.i18n && window.i18n.applyTranslations) window.i18n.applyTranslations(); } catch (e) {}
        try {
            var SelectCtor = window.Mivo && window.Mivo.components && window.Mivo.components.Select;
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

    function loadSettings(url, push) {
        if (push === undefined) push = true;
        var u = new URL(url, window.location.href);
        u.hash = '';
        var target = u.pathname + u.search;
        setLoading(true);
        fetch(target, { credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var fresh = doc.getElementById(DYNAMIC_ID);
                if (!fresh) { window.location.href = url; return; } // not a settings page -> full nav
                var current = document.getElementById(DYNAMIC_ID);
                if (!current) { window.location.href = url; return; }
                var imported = document.importNode(fresh, true);
                current.replaceWith(imported);
                executeScripts(imported);
                reinitScope(imported);
                if (push) history.pushState({ settingsSpa: true, url: u.href }, '', u.href);
                setActiveNav(u.pathname);
                if (doc.title) document.title = doc.title;
                setLoading(false);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                window.dispatchEvent(new CustomEvent('settings:loaded', { detail: { url: u.href } }));
            })
            .catch(function (err) {
                console.error('[settings-spa] load failed, falling back to full navigation:', err);
                window.location.href = url;
            });
    }

    // Intercept sub-nav clicks (only the 6 settings menu items).
    menu.addEventListener('click', function (e) {
        var a = e.target.closest('.sub-nav-item');
        if (!a) return;
        var href = a.getAttribute('href');
        if (!href || href === '#' || a.target === '_blank') return;
        try {
            var u = new URL(href, window.location.href);
            if (u.origin !== window.location.origin) return;
            if (!isSettingsPath(u.pathname)) return;
            if (u.pathname === window.location.pathname && u.search === window.location.search) {
                e.preventDefault(); // same page
                return;
            }
            e.preventDefault();
            loadSettings(u.href, true);
        } catch (err) { /* allow default navigation */ }
    });

    // Back / forward.
    window.addEventListener('popstate', function () {
        loadSettings(window.location.href, false);
    });

    // Seed history state so popstate has a marker on the initial settings page.
    if (!history.state || !history.state.settingsSpa) {
        history.replaceState({ settingsSpa: true, url: window.location.href }, '', window.location.href);
    }
})();
</script>
