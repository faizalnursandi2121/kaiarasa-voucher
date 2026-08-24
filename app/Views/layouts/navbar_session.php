<?php

use App\Config\SiteConfig;

/**
 * Topbar untuk halaman Session (/{session}/...).
 * Logo · Global Search · Notifikasi · Profile (Disconnect/Logout di dropdown).
 *
 * Variabel yang dibutuhkan: $session
 */
$sessionUser = $_SESSION['username'] ?? 'Admin';
$sessionInitials = strtoupper(substr($sessionUser, 0, 2));
?>
<!-- ===== Session Topbar ===== -->
<header class="h-16 bg-white dark:bg-[#1a1c19] border-b border-black/[.07] dark:border-white/[.08] flex items-center gap-4 px-4 sm:px-6 sticky top-0 z-30 lg:ml-64">

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
                <a href="/" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                    <i data-lucide="layout-dashboard" class="w-4 h-4 opacity-60"></i> Home (NOC)
                </a>
                <a href="/settings" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                    <i data-lucide="settings" class="w-4 h-4 opacity-60"></i> Settings
                </a>
                <div class="border-t border-black/[.06] dark:border-white/[.06]"></div>
                <a href="/" title="Exit session — back to Home" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                    <i data-lucide="cast" class="w-4 h-4 opacity-60"></i> Disconnect
                </a>
                <a href="/logout" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-500/[.07] transition-colors">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                </a>
            </div>
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
