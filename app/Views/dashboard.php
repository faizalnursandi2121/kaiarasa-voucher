<?php

use App\Helpers\FormatHelper;

$title = 'Dashboard';
require_once ROOT.'/app/Views/layouts/header_main.php';

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

    <!-- ===== KPI Cards ===== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
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
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <h3 class="font-bold tracking-tight mb-4">Quick Actions</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <?php foreach (($quick_actions ?? []) as $qa): ?>
                <a href="<?= $qa['href'] ?>"
                    class="flex flex-col items-center justify-center gap-2 min-h-[110px] rounded-xl border border-black/[.07] dark:border-white/[.08] p-4 text-center hover:bg-[#5f7f67]/[.08] hover:border-[#5f7f67]/40 transition-colors group">
                    <i data-lucide="<?= $qa['icon'] ?>" class="w-6 h-6 text-[#47614d] dark:text-[#92aa96]"></i>
                    <span class="text-[13px] font-semibold group-hover:text-[#47614d] dark:group-hover:text-[#92aa96] transition-colors"><?= $qa['label'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
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

    if (window.lucide) lucide.createIcons();
})();
</script>
<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
