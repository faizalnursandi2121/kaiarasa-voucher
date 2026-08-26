<?php

use App\Config\SiteConfig;
use App\Helpers\LanguageHelper;

// Determine active link state
$uri = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!-- Modern Navbar (Tailwind) -->
<nav class="sticky top-0 z-50 w-full border-b border-accents-2 bg-background">
    <!-- Container sama dengan konten halaman (max-w-7xl) agar logo sejajar kartu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <!-- Brand & Global Search -->
            <div class="flex items-center gap-3 sm:gap-5 flex-1 min-w-0">
                <a href="/" class="flex items-center gap-2 group shrink-0">
                    <img src="/assets/img/logo-sage.webp" alt="<?= SiteConfig::APP_NAME ?> Logo" width="120" height="32" class="h-8 w-auto block dark:hidden transition-transform group-hover:scale-110">
                    <img src="/assets/img/logo-white.webp" alt="<?= SiteConfig::APP_NAME ?> Logo" width="120" height="32" class="h-8 w-auto hidden dark:block transition-transform group-hover:scale-110">
                </a>

                <!-- Global Search (Desktop) — tepat di sebelah kanan logo -->
                <div class="hidden md:block w-full max-w-sm" id="global-search-wrap">
                    <div class="relative">
                        <i data-lucide="search" class="absolute inset-y-0 left-3 w-4 h-4 my-auto text-accents-5"></i>
                        <input type="search" id="global-search" autocomplete="off"
                            placeholder="Search router, IP address, location…"
                            class="w-full h-10 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/[.08] dark:border-white/[.08] pl-9 pr-3 text-sm outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    </div>
                </div>
            </div>

            <!-- Right side controls -->
            <div class="flex items-center gap-3">
                <?php if (isset($_SESSION['user_id'])) { ?>
                <!-- Add Router (Primary) -->
                <a href="/settings/add" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[13px] font-semibold transition-colors">
                    <i data-lucide="plus" class="w-4 h-4"></i> <span class="hidden sm:inline">Add Router</span>
                </a>

                <!-- Notifikasi Bell -->
                <div class="relative" id="notif-wrap">
                    <button type="button" onclick="KaiarasaNav.toggle('notif-dropdown')"
                        class="relative w-10 h-10 inline-flex items-center justify-center rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/[.08] dark:border-white/[.08] text-accents-6 hover:text-foreground hover:border-[#92aa96] transition-colors">
                        <i data-lucide="bell" class="w-[18px] h-[18px]"></i>
                        <span id="notif-dot" class="hidden absolute top-2 right-2.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-background"></span>
                    </button>
                    <div id="notif-dropdown" class="hidden absolute right-0 top-full mt-2 w-80 bg-background dark:bg-[#1a1c19] border border-black/[.08] dark:border-white/[.08] rounded-xl shadow-xl z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-black/[.06] dark:border-white/[.06] flex items-center justify-between">
                            <span class="text-sm font-bold">Notifications</span>
                            <a href="/api/routers/events" class="text-[11px] text-accents-5 hover:text-foreground" onclick="event.preventDefault()">Router events</a>
                        </div>
                        <div id="notif-list" class="max-h-72 overflow-y-auto divide-y divide-black/[.05] dark:divide-white/[.05]">
                            <p class="px-4 py-6 text-center text-xs opacity-50">Loading…</p>
                        </div>
                    </div>
                </div>

                <!-- Profile Dropdown -->
                <div class="relative" id="profile-wrap">
                    <button type="button" onclick="KaiarasaNav.toggle('profile-dropdown')"
                        class="flex items-center gap-2 h-10 pl-1 pr-2 rounded-xl hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                        <span class="w-8 h-8 rounded-full bg-[#5f7f67] text-white text-xs font-bold flex items-center justify-center uppercase">
                            <?= htmlspecialchars(substr($_SESSION['username'] ?? 'A', 0, 2)) ?>
                        </span>
                        <i data-lucide="chevron-down" class="w-4 h-4 opacity-50"></i>
                    </button>
                    <div id="profile-dropdown" class="hidden absolute right-0 top-full mt-2 w-56 bg-background dark:bg-[#1a1c19] border border-black/[.08] dark:border-white/[.08] rounded-xl shadow-xl z-50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-black/[.06] dark:border-white/[.06]">
                            <p class="text-sm font-bold"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                            <p class="text-[11px] opacity-50">Signed in</p>
                        </div>
                        <a href="/settings" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                            <i data-lucide="settings" class="w-4 h-4 opacity-60"></i> Settings
                        </a>
                        <form action="/logout" method="POST"><button type="submit" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-500/[.07] transition-colors w-full text-left cursor-pointer bg-transparent border-0"><i data-lucide="log-out" class="w-4 h-4"></i> Logout</button></form>
                    </div>
                </div>
                <?php } else { ?>
                <a href="/login" class="inline-flex items-center h-10 px-4 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[13px] font-semibold transition-colors">Sign In</a>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Drawer (Hidden by default) -->
    <div id="mobile-navbar-menu" class="md:hidden border-t border-accents-2 bg-background transition-all duration-300 ease-in-out max-h-0 opacity-0 invisible overflow-hidden">
        <div class="px-4 pt-4 pb-6 space-y-4">
            <!-- Nav Links -->
            <?php if (isset($_SESSION['user_id'])) { ?>
            <div class="flex flex-col gap-1">
                <a href="/" data-app-nav data-nav-path="/" data-on="bg-foreground/5 text-foreground font-bold" data-off="text-accents-5 hover:bg-accents-1" class="flex items-center gap-3 px-4 py-3 rounded-xl <?= ($uri == '/' || $uri == '/home') ? 'bg-foreground/5 text-foreground font-bold' : 'text-accents-5 hover:bg-accents-1' ?>">
                    <i data-lucide="home" class="w-5 h-5 text-foreground dark:text-foreground" stroke-width="2.5"></i>
                    <span>Home</span>
                </a>
                <a href="/settings" data-app-nav data-nav-path="/settings" data-on="bg-foreground/5 text-foreground font-bold" data-off="text-accents-5 hover:bg-accents-1" class="flex items-center gap-3 px-4 py-3 rounded-xl <?= (strpos($uri, '/settings') === 0) ? 'bg-foreground/5 text-foreground font-bold' : 'text-accents-5 hover:bg-accents-1' ?>">
                    <i data-lucide="settings" class="w-5 h-5 text-foreground dark:text-foreground" stroke-width="2.5"></i>
                    <span>Settings</span>
                </a>
                <form action="/logout" method="POST"><button type="submit" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-600 hover:bg-red-500/[.07] transition-colors w-full text-left cursor-pointer bg-transparent border-0"><i data-lucide="log-out" class="w-5 h-5" stroke-width="2.5"></i>
                    <span>Logout</span></button></form>
            </div>
            <?php } ?>
        </div>
    </div>
</nav>

<script>
// ===== Dropdown helpers (notifikasi & profil) =====
// Tutup dropdown saat klik di luar
(function () {
    var DD_IDS = ['notif-dropdown', 'profile-dropdown'];
    document.addEventListener('click', function (e) {
        DD_IDS.forEach(function (id) {
            var dd = document.getElementById(id);
            if (!dd || dd.classList.contains('hidden')) return;
            var wrap = dd.parentElement;
            if (!wrap.contains(e.target)) dd.classList.add('hidden');
        });
    });
})();

// Toggle mandiri (tidak memakai toggleMenu global yang ditimpa footer_main)
window.KaiarasaNav = {
    toggle: function (id) {
        var dd = document.getElementById(id);
        if (!dd) return;
        ['notif-dropdown', 'profile-dropdown'].forEach(function (other) {
            if (other !== id) {
                var el = document.getElementById(other);
                if (el && !el.classList.contains('hidden')) el.classList.add('hidden');
            }
        });
        dd.classList.toggle('hidden');
    }
};

// Tombol Add Router — berfungsi dari halaman mana pun:
// di home -> langsung buka modal; di halaman lain -> navigasi home lalu auto-open.
document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href="/settings/add"]');
    if (!a) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    if (document.getElementById('router-modal') && typeof window.__openRouterModal === 'function') {
        window.__openRouterModal('add', null);
        return;
    }
    try { sessionStorage.setItem('mivoOpenAddRouter', '1'); } catch (err) {}
    if (window.location.pathname !== '/') {
        history.pushState({}, '', '/');
        window.dispatchEvent(new PopStateEvent('popstate'));
    } else {
        window.location.href = '/';
    }
}, true);

// Muat notifikasi saat pertama dibuka (capture-phase: jalan sebelum toggle)
var __notifLoaded = false;
document.addEventListener('click', function (e) {
    if (! e.target.closest('#notif-wrap button')) return;
    if (__notifLoaded) return;
    __notifLoaded = true;
    fetch('/api/routers/events?limit=8', { headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (events) {
            var list = document.getElementById('notif-list');
            if (!events.length) { list.innerHTML = '<p class="px-4 py-6 text-center text-xs opacity-50">No activity yet.</p>'; return; }
            list.innerHTML = events.map(function (ev) {
                var icon = ev.event_type === 'connected'     ? ['arrow-up-circle',   'text-emerald-600', 'connected']
                        : ev.event_type === 'went_offline'   ? ['arrow-down-circle', 'text-red-600',      'went offline']
                        :                                      ['alert-triangle',    'text-amber-600',    'high CPU usage'];
                return '<div class="px-4 py-3 flex items-start gap-3 text-xs">'
                    + '<i data-lucide="'+icon[0]+'" class="w-4 h-4 mt-0.5 shrink-0 '+icon[1]+'"></i>'
                    + '<div class="flex-grow"><p class="font-medium">Router '+escNav(ev.router_name)+' '+icon[2]+'</p>'
                    + '<p class="opacity-50 mt-0.5">'+escNav((ev.created_at || '').replace(' ', 'T')+'Z').slice(11, 16)+' UTC</p></div></div>';
            }).join('');
            var dot = document.getElementById('notif-dot');
            if (dot) dot.classList.add('hidden');
            if (window.lucide) lucide.createIcons();
        })
        .catch(function () {
            var list = document.getElementById('notif-list');
            if (list) list.innerHTML = '<p class="px-4 py-6 text-center text-xs opacity-50">Failed to load notifications.</p>';
        });
}, true);
function escNav(s) {
    return String(s ?? '').replace(/[&<>"']/g, function (c) {
        return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
}
</script>

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
