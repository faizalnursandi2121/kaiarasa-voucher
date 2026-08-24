<?php

use App\Helpers\FormatHelper;

$title = 'Dashboard';
require_once ROOT.'/app/Views/layouts/header_main.php';
?>
<div class="lg:pl-64 min-h-screen flex flex-col">

$uaMode = $ua_mode ?? 'today';
$uaSeries = $charts['user_activity']['series'] ?? [];
$va = $charts['voucher_activity'] ?? [];
$topPkgs = $charts['top_packages'] ?? [];

function dashRp(int $v): string
{
    return 'Rp'.number_format($v, 0, ',', '.');
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-grow w-full flex flex-col">

    <!-- ===== Page Header ===== -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
            <div class="flex items-center gap-2 mt-1.5">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-[#5f7f67]/10 text-[#47614d] dark:text-[#92aa96] text-[11px] font-semibold">
                    <i data-lucide="map-pin" class="w-3 h-3"></i> <?= htmlspecialchars($session) ?>
                </span>
                <span class="text-xs opacity-50">Operational overview for this location</span>
            </div>
        </div>
        <a href="?refresh=1" title="Refresh data"
            class="h-9 w-9 inline-flex items-center justify-center rounded-xl border border-black/10 dark:border-white/10 hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
        </a>
    </div>

    <?php if (! empty($cred_issue)): ?>
    <div class="mt-6 rounded-2xl border border-amber-500/30 bg-amber-500/[.07] p-6 text-center">
        <i data-lucide="key-round" class="w-8 h-8 mx-auto text-amber-600 mb-2"></i>
        <p class="font-semibold">Router password needs to be re-entered.</p>
        <p class="text-xs opacity-60 mt-1">Stored credentials cannot be decrypted (encryption key may have changed).</p>
        <a href="/?refresh=1" class="inline-flex items-center gap-2 mt-4 h-9 px-4 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[13px] font-semibold transition-colors">
            <i data-lucide="pencil" class="w-4 h-4"></i> Edit Router on Home
        </a>
    </div>
    <?php elseif (! empty($unreachable)): ?>
    <div class="mt-6 rounded-2xl border border-amber-500/30 bg-amber-500/[.07] p-6 text-center">
        <i data-lucide="wifi-off" class="w-8 h-8 mx-auto text-amber-600 mb-2"></i>
        <p class="font-semibold">Live data unavailable — router cannot be reached.</p>
        <p class="text-xs opacity-60 mt-1">KPI values may be stale. <a href="?refresh=1" class="underline">Retry</a></p>
    </div>
    <?php endif; ?>

    <!-- ===== Session Topbar ===== -->
    <header class="sticky top-0 z-30 bg-white dark:bg-[#1a1c19] border-b border-black/[.06] dark:border-white/[.06] px-4 sm:px-6 py-3 flex items-center gap-3">
        <button type="button" id="sb-open" aria-label="Open menu"
            class="lg:hidden w-10 h-10 inline-flex items-center justify-center rounded-xl hover:bg-black/[.04] dark:hover:bg-white/[.06] transition-colors">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        <div class="relative flex-1 max-w-sm">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40"></i>
            <input type="search" placeholder="Search users, vouchers…"
                class="w-full h-10 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-9 pr-3 text-sm outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition-colors">
        </div>
        <div class="ml-auto flex items-center gap-2">
            <div class="relative">
                <button type="button" id="tb-notif-btn"
                    class="relative w-10 h-10 inline-flex items-center justify-center rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 hover:border-[#92aa96] transition-colors" aria-label="Notifications">
                    <i data-lucide="bell" class="w-[18px] h-[18px]"></i>
                    <span id="tb-notif-dot" class="hidden absolute top-2 right-2.5 w-2 h-2 rounded-full bg-red-500 ring-2 ring-background"></span>
                </button>
                <div id="tb-notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-background dark:bg-[#1a1c19] border border-black/[.08] dark:border-white/[.08] rounded-xl shadow-xl z-40 overflow-hidden">
                    <div class="px-4 py-3 border-b border-black/[.06] dark:border-white/[.06] flex items-center justify-between">
                        <span class="text-sm font-bold">Notifications</span>
                        <span class="text-[11px] opacity-50">router events</span>
                    </div>
                    <div id="tb-notif-list" class="max-h-72 overflow-y-auto divide-y divide-black/[.05] dark:divide-white/[.05]">
                        <p class="px-4 py-6 text-center text-xs opacity-50">Loading…</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <button type="button" id="tb-profile-btn"
                    class="flex items-center gap-2 h-10 pl-1 pr-2 rounded-xl hover:bg-black/[.04] dark:hover:bg-white/[.06] transition-colors" aria-label="Account menu">
                    <span class="w-8 h-8 rounded-full bg-[#5f7f67] text-white text-xs font-bold flex items-center justify-center uppercase"><?= htmlspecialchars(strtoupper(substr($_SESSION['username'] ?? 'A', 0, 2))) ?></span>
                    <span class="hidden sm:block text-sm font-semibold max-w-[120px] truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></span>
                    <i data-lucide="chevron-down" class="w-4 h-4 opacity-50"></i>
                </button>
                <div id="tb-profile-dropdown" class="hidden absolute right-0 mt-2 w-56 bg-background dark:bg-[#1a1c19] border border-black/[.08] dark:border-white/[.08] rounded-xl shadow-xl z-40 overflow-hidden">
                    <div class="px-4 py-3 border-b border-black/[.06] dark:border-white/[.06]">
                        <p class="text-sm font-bold truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                        <p class="text-[11px] opacity-50 truncate">Session: <?= htmlspecialchars($session) ?></p>
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

    <div class="px-4 sm:px-6 py-2 flex-grow">

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
        <?php
        $kpis = [
            ['Active Users', 'users', ($kpis['active_users'] ?? null) !== null ? number_format($kpis['active_users']) : '—', 'bg-emerald-500/10 text-emerald-600'],
            ['Sold Today', 'ticket', (string) ($kpis['sold_today'] ?? 0), 'bg-[#5f7f67]/10 text-[#47614d] dark:text-[#92aa96]'],
            ['Revenue Today', 'banknote', dashRp((int) ($kpis['revenue_today'] ?? 0)), 'bg-sky-500/10 text-sky-600'],
            ['Created Today', 'ticket-plus', (string) ($kpis['created_today'] ?? 0), 'bg-amber-500/10 text-amber-600'],
        ];
        foreach ($kpis as [$label, $icon, $value, $chip]): ?>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-11 h-11 rounded-full flex items-center justify-center <?= $chip ?>">
                    <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                </span>
                <span class="text-sm font-semibold"><?= $label ?></span>
            </div>
            <p class="text-3xl font-bold tabular-nums leading-none"><?= $value ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ===== Quick Actions + Recent Activity ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        <div class="flex flex-col gap-3">
            <?php foreach (($quick_actions ?? []) as $qa): ?>
            <a href="<?= $qa['href'] ?>" class="flex items-center gap-3 rounded-xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] px-4 py-3 hover:bg-[#5f7f67]/[.08] hover:border-[#5f7f67]/40 transition-colors group">
                <span class="w-9 h-9 rounded-lg bg-[#5f7f67]/10 flex items-center justify-center shrink-0">
                    <i data-lucide="<?= $qa['icon'] ?>" class="w-[18px] h-[18px] text-[#47614d] dark:text-[#92aa96]"></i>
                </span>
                <span class="text-[13px] font-semibold group-hover:text-[#47614d] dark:group-hover:text-[#92aa96] transition-colors"><?= $qa['label'] ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 ml-auto opacity-30"></i>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="lg:col-span-2 rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold tracking-tight">Recent Activity</h3>
                <span class="text-[11px] opacity-50">today</span>
            </div>
            <ul id="activity-list" class="space-y-3 max-h-[260px] overflow-y-auto pr-1">
                <?php if (empty($activity)): ?>
                <li class="text-xs opacity-50">No activity yet.</li>
                <?php else: ?>
                <?php foreach (($activity ?? []) as $item): ?>
                <li class="flex items-start gap-3 text-xs">
                    <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 mt-0.5 shrink-0 opacity-60"></i>
                    <div class="flex-grow min-w-0">
                        <p class="font-medium"><?= htmlspecialchars($item['text']) ?></p>
                        <p class="opacity-50 truncate"><?= htmlspecialchars($item['detail']) ?></p>
                    </div>
                </li>
                <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- ===== Charts ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-4">
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold tracking-tight">User Activity</h3>
                <div class="flex items-center gap-1" id="ua-pills">
                    <?php foreach ([['today', 'Today'], ['7d', '7 Days'], ['30d', '30 Days']] as [$m, $lbl]): ?>
                    <a href="?<?= http_build_query(array_filter(array_merge($_GET, ['ua' => $m]))) ?>"
                        class="px-2.5 py-1 rounded-lg text-[11px] font-semibold transition-colors <?= $uaMode === $m ? 'bg-[#5f7f67]/10 text-[#47614d] dark:text-[#92aa96]' : 'text-accents-5 hover:text-foreground' ?>"><?= $lbl ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div id="chart-ua"></div>
        </div>

        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold tracking-tight">Voucher Activity</h3>
                <span class="text-[11px] opacity-50">created · last 30 days</span>
            </div>
            <div id="chart-va"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4 mb-4">
        <div class="lg:col-span-2 rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <h3 class="font-bold tracking-tight mb-3">Top Packages</h3>
            <div id="chart-packages"></div>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <h3 class="font-bold tracking-tight mb-4">Top Packages — List</h3>
            <div class="space-y-2">
                <?php if (empty($topPkgs)): ?>
                <p class="text-xs opacity-50">No package data yet.</p>
                <?php else: ?>
                <?php foreach (($topPkgs ?? []) as $i => $pkg): ?>
                <div class="flex items-center justify-between p-3 rounded-xl bg-black/[.03] dark:bg-white/[.04]">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-6 h-6 rounded-md bg-[#5f7f67]/15 text-[#47614d] dark:text-[#92aa96] text-[11px] font-bold flex items-center justify-center shrink-0"><?= $i + 1 ?></span>
                        <span class="text-sm font-medium truncate"><?= htmlspecialchars($pkg['name']) ?></span>
                    </div>
                    <span class="text-sm font-bold tabular-nums"><?= $pkg['count'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/vendor/apexcharts.min.js"></script>
<script>
(function () {
    'use strict';

    var isDark = function () { return document.documentElement.classList.contains('dark'); };
    var gridColor = function () { return isDark() ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)'; };

    function baseChart(extra) {
        var base = {
            chart: Object.assign({ fontFamily: 'inherit', foreColor: isDark() ? '#e9ebe7' : '#1c2420', toolbar: { show: false } }, extra.chart || {}),
            grid: { borderColor: gridColor() },
            tooltip: { theme: isDark() ? 'dark' : 'light' },
        };
        return Object.assign(base, extra);
    }

    var uaSeries = <?= json_encode($charts['user_activity']['series'] ?? []) ?>;
    var uaMode = <?= json_encode($ua_mode ?? 'today') ?>;
    var va = <?= json_encode($charts['voucher_activity'] ?? []) ?>;
    var topPkgs = <?= json_encode($top_pkgs ?? []) ?>;

    // ---- User Activity ----
    var uaEl = document.getElementById('chart-ua');
    if (uaEl) {
        if (! uaSeries.length) {
            uaEl.innerHTML = '<p class="text-xs opacity-50 py-10 text-center">Not enough data yet — activity is sampled every few minutes.</p>';
        } else if (uaMode === 'today') {
            new ApexCharts(uaEl, baseChart({
                series: [{ name: 'Active users', data: uaSeries.map(function (p) { return [p.label, p.value]; }) }],
                chart: Object.assign({ type: 'area', height: 240 }, {}),
                colors: ['#5f7f67'],
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .02 } },
                xaxis: { categories: uaSeries.map(function (p) { return p.label; }), tickAmount: Math.min(10, uaSeries.length - 1) },
                yaxis: { min: 0, tickAmount: 4, labels: { formatter: function (v) { return Math.round(v); } } },
            })).render();
        } else {
            new ApexCharts(uaEl, baseChart({
                series: [
                    { name: 'Avg active', data: uaSeries.map(function (p) { return p.avg; }) },
                    { name: 'Peak', data: uaSeries.map(function (p) { return p.max; }) },
                ],
                chart: Object.assign({ type: 'bar', height: 240 }, {}),
                colors: ['#5f7f67', '#92aa96'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                xaxis: { categories: uaSeries.map(function (p) { return p.label.slice(5); }) },
                legend: { position: 'top' },
            })).render();
        }
    }

    // ---- Voucher Activity ----
    var vaEl = document.getElementById('chart-va');
    if (vaEl) {
        if ((va.daily || []).length === 0) {
            vaEl.innerHTML = '<p class="text-xs opacity-50 py-10 text-center">No voucher activity yet.</p>';
        } else {
            new ApexCharts(vaEl, baseChart({
                series: [{ name: 'Vouchers created', data: va.daily.map(function (d) { return d.count; }) }],
                chart: Object.assign({ type: 'bar', height: 240 }, {}),
                colors: ['#5f7f67'],
                plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
                xaxis: { categories: va.daily.map(function (d) { return d.date.slice(5); }) },
                yaxis: { min: 0, labels: { formatter: function (v) { return Math.round(v); } } },
                dataLabels: { enabled: false },
            })).render();
        }
    }

    // ---- Top Packages donut ----
    var pkgEl = document.getElementById('chart-packages');
    if (pkgEl) {
        if (! topPkgs.length) {
            pkgEl.innerHTML = '<p class="text-xs opacity-50 py-10 text-center">No package data yet.</p>';
        } else {
            new ApexCharts(pkgEl, baseChart({
                series: topPkgs.map(function (p) { return p.count; }),
                labels: topPkgs.map(function (p) { return p.name; }),
                chart: Object.assign({ type: 'donut', height: 260 }, {}),
                colors: ['#5f7f67', '#92aa96', '#6b8b73', '#47614d', '#c9d6cc'],
                legend: { position: 'bottom', fontSize: '12px' },
                plotOptions: { pie: { donut: { size: '72%', labels: {
                    show: true,
                    name: { fontSize: '11px' },
                    value: { fontSize: '20px', fontWeight: 700 },
                    total: { show: true, label: 'Created', fontSize: '11px',
                        formatter: function (w) { return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0); } },
                } } } },
                dataLabels: { enabled: false },
            })).render();
        }
    }

    // ---- Topbar dropdown (mandiri, tanpa toggleMenu global) ----
    var pairs = [['tb-notif-btn','tb-notif-dropdown'], ['tb-profile-btn','tb-profile-dropdown']];
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
    document.addEventListener('click', function (e) {
        pairs.forEach(function (pair) {
            var dd = document.getElementById(pair[1]);
            var bt = document.getElementById(pair[0]);
            if (! dd || dd.classList.contains('hidden')) return;
            if (! dd.contains(e.target) && ! bt.contains(e.target)) dd.classList.add('hidden');
        });
    });

    // ---- Notifikasi (router events) ----
    var notifLoaded = false;
    document.getElementById('tb-notif-btn').addEventListener('click', function () {
        if (notifLoaded) return;
        notifLoaded = true;
        fetch('/api/routers/events?limit=8', { headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (events) {
                var list = document.getElementById('tb-notif-list');
                if (! events.length) { list.innerHTML = '<p class="px-4 py-6 text-center text-xs opacity-50">No activity yet.</p>'; return; }
                list.innerHTML = events.map(function (ev) {
                    var icon = ev.event_type === 'connected'   ? ['arrow-up-circle',   'text-emerald-600', 'connected']
                            : ev.event_type === 'went_offline' ? ['arrow-down-circle', 'text-red-600',      'went offline']
                            :                                    ['alert-triangle',    'text-amber-600',    'high CPU usage'];
                    return '<div class="px-4 py-3 flex items-start gap-3 text-xs">'
                        + '<i data-lucide="' + icon[0] + '" class="w-4 h-4 mt-0.5 shrink-0 ' + icon[1] + '"></i>'
                        + '<div class="flex-grow"><p class="font-medium">Router ' + esc(ev.router_name) + ' ' + icon[2] + '</p>'
                        + '<p class="opacity-50 mt-0.5">' + esc((ev.created_at || '').replace(' ', 'T') + 'Z').slice(11, 16) + ' UTC</p></div></div>';
                }).join('');
                var dot = document.getElementById('tb-notif-dot');
                if (dot) dot.classList.add('hidden');
                if (window.lucide) lucide.createIcons();
            })
            .catch(function () {
                var list = document.getElementById('tb-notif-list');
                if (list) list.innerHTML = '<p class="px-4 py-6 text-center text-xs opacity-50">Failed to load.</p>';
            });
    });

    // ---- Sidebar mobile toggle ----
    var sbOpen = document.getElementById('sb-open');
    if (sbOpen) sbOpen.addEventListener('click', function () {
        var sb = document.getElementById('sidebar');
        if (sb) sb.classList.remove('-translate-x-full');
    });
    document.addEventListener('click', function (e) {
        var sb = document.getElementById('sidebar');
        if (! sb || window.innerWidth >= 1024) return;
        if (! sb.contains(e.target) && ! e.target.closest('#sb-open')) {
            sb.classList.add('-translate-x-full');
        }
    });

    if (window.lucide) lucide.createIcons();
})();
</script>
</div>
<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
