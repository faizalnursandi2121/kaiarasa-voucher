<?php

use App\Helpers\LanguageHelper;

$title = 'Sales Report';
$no_main_container = true;
require_once ROOT.'/app/Views/layouts/header_main.php';

$unreachable = isset($report['__unreachable']);
$summary = $report['summary'] ?? [];
$trend = $report['revenue_trend'] ?? [];
$daily = $report['daily_breakdown'] ?? [];

$ranges = [
    'today' => ['label' => 'Today', 's' => date('Y-m-d'), 'e' => date('Y-m-d')],
    'yesterday' => ['label' => 'Yesterday', 's' => date('Y-m-d', strtotime('-1 day')), 'e' => date('Y-m-d', strtotime('-1 day'))],
    '7d' => ['label' => 'Last 7 Days', 's' => date('Y-m-d', strtotime('-6 days')), 'e' => date('Y-m-d')],
    '30d' => ['label' => 'Last 30 Days', 's' => date('Y-m-d', strtotime('-29 days')), 'e' => date('Y-m-d')],
];
// Deteksi range aktif dari filter
$activeRange = 'custom';
foreach ($ranges as $k => $r) {
    if (($filters['start'] ?? null) === $r['s'] && ($filters['end'] ?? null) === $r['e']) {
        $activeRange = $k;
        break;
    }
}

function salesFmtRp(int $v): string
{
    return 'Rp'.number_format($v, 0, ',', '.');
}
?>
<style>
@media print {
    /* Hide everything except the sales report body */
    #sidebar, header.session-mobile-header,
    nav.page-breadcrumb, .page-header,
    #sales-filter-form, #sales-search,
    [onclick="window.print()"], #export-csv,
    #chart-trend, #chart-volume, #chart-package,
    #kai-route-loading, .skip-link,
    footer, .footer, [role="contentinfo"] { display: none !important; }
    /* Keep KPI cards + detail table */
    .card, .grid, #sales-table { break-inside: avoid; }
    body { background: #fff !important; color: #000 !important; }
    .print-header { display: block !important; margin-bottom: 12px; }
    .print-header h2 { font-size: 18px; font-weight: 700; margin: 0 0 4px; }
    .print-header p { font-size: 11px; margin: 0 0 2px; }
    /* Reduce padding for compact print */
    .max-w-7xl { max-width: 100% !important; padding: 0 !important; }
    @page { margin: 1.5cm; }
}
.print-header { display: none; }
</style>
<script>
window.addEventListener('beforeprint', function () {
    var h = document.querySelector('.print-header');
    if (!h) {
        h = document.createElement('div');
        h.className = 'print-header';
        document.body.prepend(h);
    }
    var range = (<?= json_encode([
        'start' => $filters['start'] ?? '',
        'end' => $filters['end'] ?? '',
        'range' => $activeRange,
    ]) ?>);
    var rangeText = range.range === 'custom'
        ? range.start + ' to ' + range.end
        : (range.range || 'all').toUpperCase();
    h.innerHTML = '<h2>Sales Report — ' + <?= json_encode($session ?? '') ?> + '</h2>' +
        '<p>Period: ' + rangeText + '</p>' +
        '<p>Generated: ' + new Date().toLocaleString() + '</p>';
});
</script>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full flex flex-col">

<?php
$page_title_key = 'sales.title';
$page_title = 'Sales Report';
$page_desc = 'Track voucher sales and revenue for this location.';
$breadcrumbs = [
    ['label' => LanguageHelper::t('common.dashboard', 'Dashboard'), 'href' => "/" . htmlspecialchars($session) . "/dashboard"],
    ['label' => 'Sales Report', 'href' => null],
];
require_once ROOT.'/app/Views/layouts/page_header.php';
?>

    <?php if ($unreachable): ?>
    <div class="mt-8 rounded-2xl border border-amber-500/30 bg-amber-500/[.07] p-8 text-center">
        <i data-lucide="alert-triangle" class="w-10 h-10 mx-auto text-amber-600 mb-3"></i>
        <h2 class="font-bold text-lg">Sales data unavailable</h2>
        <p class="text-sm opacity-60 mt-1">Unable to retrieve sales data from the router.</p>
        <a href="?refresh=1" class="inline-flex items-center gap-2 mt-5 h-10 px-4 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[13px] font-semibold transition-colors">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i> Retry
        </a>
    </div>
    <?php else: ?>

    <!-- ===== Filter Bar ===== -->
    <form method="GET" id="sales-filter-form" class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
        <div>
            <label class="block text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2">Date Range</label>
            <select name="range" id="f-range"
                class="w-full h-[46px] rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 cursor-pointer">
                <?php foreach ($ranges as $k => $r): ?>
                <option value="<?= $k ?>" data-s="<?= $r['s'] ?>" data-e="<?= $r['e'] ?>" <?= $activeRange === $k ? 'selected' : '' ?>><?= $r['label'] ?></option>
                <?php endforeach; ?>
                <option value="custom" <?= $activeRange === 'custom' ? 'selected' : '' ?>>Custom Range</option>
            </select>
        </div>
        <div id="custom-range" class="<?= $activeRange !== 'custom' ? 'hidden' : '' ?> contents sm:contents">
            <input type="date" name="start" value="<?= htmlspecialchars($filters['start'] ?? '') ?>"
                class="w-full h-[46px] rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20">
            <input type="date" name="end" value="<?= htmlspecialchars($filters['end'] ?? '') ?>"
                class="w-full h-[46px] rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3 mt-2 sm:mt-0 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20">
        </div>
        <div>
            <select name="package"
                class="w-full h-[46px] rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 cursor-pointer">
                <option value="">All Packages</option>
                <?php foreach ($packages as $p): ?>
                <option value="<?= htmlspecialchars($p) ?>" <?= ($filters['package'] ?? '') === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <select name="sale_type"
                class="w-full h-[46px] rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 cursor-pointer">
                <option value="">All Types</option>
                <option value="bulk_generate" <?= ($filters['sale_type'] ?? '') === 'bulk_generate' ? 'selected' : '' ?>>Bulk Generate</option>
                <option value="quick_print" <?= ($filters['sale_type'] ?? '') === 'quick_print' ? 'selected' : '' ?>>Quick Print</option>
            </select>
        </div>
        <div>
            <select name="server"
                class="w-full h-[46px] rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 cursor-pointer">
                <option value="">All Servers</option>
                <?php foreach ($servers as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>" <?= ($filters['server'] ?? '') === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" id="apply-filter"
            class="h-[46px] px-4 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] active:scale-[0.98] text-white text-[13px] font-semibold transition-[background-color,transform] duration-150 inline-flex items-center justify-center gap-2">
            <span id="apply-label" class="inline-flex items-center gap-2"><i data-lucide="filter" class="w-4 h-4"></i> Apply</span>
            <span id="apply-spinner" class="hidden items-center gap-2">
                <svg class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                Applying…
            </span>
        </button>
        <input type="hidden" name="end_custom_dummy">
    </form>

    <?php if (($summary['vouchers_sold'] ?? 0) === 0): ?>
    <div class="mt-8 card py-16 text-center">
        <i data-lucide="receipt" class="w-10 h-10 mx-auto opacity-30 mb-3"></i>
        <p class="text-sm font-semibold">No sales for this period</p>
        <p class="text-xs opacity-50 mt-1">Try a different date range or clear filters.</p>
        <a href="?range=30d" class="inline-flex mt-4 text-[13px] font-semibold text-[#47614d] dark:text-[#92aa96] hover:underline">Adjust filters</a>
    </div>
    <?php else: ?>

    <?php if (($report['meta']['undated_count'] ?? 0) > 0): ?>
    <div class="mt-6 flex items-center gap-2 text-[11px] text-amber-600 dark:text-amber-400 bg-amber-500/[.07] border border-amber-500/20 rounded-lg px-3 py-2">
        <i data-lucide="info" class="w-3.5 h-3.5 shrink-0"></i>
        <?= (int) $report['meta']['undated_count'] ?> voucher(s) without a readable date are included in totals but not in the trend chart.
    </div>
    <?php endif; ?>

    <!-- ===== KPI Cards ===== -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
        <?php
        $kpis = [
            ['Total Revenue', 'banknote', salesFmtRp((int) ($summary['revenue'] ?? 0)), 'bg-[#5f7f67]/10 text-[#47614d] dark:text-[#92aa96]'],
            ['Vouchers Sold', 'ticket', number_format($summary['vouchers_sold'] ?? 0), 'bg-emerald-500/10 text-emerald-600'],
            ['Average Sale', 'calculator', isset($summary['avg_sale']) ? salesFmtRp($summary['avg_sale']) : '—', 'bg-sky-500/10 text-sky-600'],
            ['Top Package', 'crown', $summary['top_package']['name'] ?? '—', 'bg-purple-500/10 text-purple-600'],
        ];
        foreach ($kpis as [$label, $icon, $value, $chip]): ?>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="w-11 h-11 rounded-full flex items-center justify-center <?= $chip ?>">
                    <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                </span>
                <span class="text-sm font-semibold"><?= $label ?></span>
            </div>
            <p class="text-2xl font-bold tabular-nums leading-tight truncate"><?= $value ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ===== Charts ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-6">
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold tracking-tight">Revenue Trend</h3>
                <span class="text-[11px] opacity-50"><?= htmlspecialchars(strtoupper($activeRange)) ?></span>
            </div>
            <div id="chart-trend"></div>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <h3 class="font-bold tracking-tight mb-3">Vouchers Sold</h3>
            <div id="chart-volume"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
        <div class="lg:col-span-2 rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <h3 class="font-bold tracking-tight mb-3">Top Packages</h3>
            <div id="chart-package"></div>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <h3 class="font-bold tracking-tight mb-4">Sales by Type</h3>
            <div class="space-y-3">
                <?php
                $bt = $report['by_type'];
                $typeRows = [
                    ['bulk_generate', 'Bulk Generate', 'layers'],
                    ['quick_print', 'Quick Print', 'printer'],
                ];
                if (($bt['manual_user']['count'] ?? 0) > 0) {
                    $typeRows[] = ['manual_user', 'Manual User', 'user-plus'];
                }
                foreach ($typeRows as [$key, $label, $icon]):
                    $c = $bt[$key]['count'] ?? 0;
                    $rev = $bt[$key]['revenue'] ?? 0; ?>
                <div class="flex items-center justify-between p-3 rounded-xl bg-black/[.03] dark:bg-white/[.04]">
                    <div class="flex items-center gap-3">
                        <i data-lucide="<?= $icon ?>" class="w-4 h-4 opacity-50"></i>
                        <span class="text-sm font-medium"><?= $label ?></span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold tabular-nums"><?= number_format($c) ?></p>
                        <p class="text-[11px] opacity-50 tabular-nums"><?= salesFmtRp($rev) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (count($daily) > 1): ?>
    <!-- ===== Daily Breakdown ===== -->
    <div class="card mt-6 overflow-hidden">
        <div class="p-5 pb-3">
            <h3 class="font-bold tracking-tight">Daily Breakdown</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table-glass">
                <thead><tr><th>Date</th><th class="text-right">Vouchers</th><th class="text-right">Revenue</th></tr></thead>
                <tbody>
                    <?php foreach ($daily as $d): ?>
                    <tr>
                        <td><?= date('D, M j', strtotime($d['date'])) ?></td>
                        <td class="text-right tabular-nums"><?= $d['vouchers'] ?></td>
                        <td class="text-right tabular-nums"><?= salesFmtRp((int) $d['revenue']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== Sales Detail Table ===== -->
    <div class="card mt-6 overflow-hidden">
        <div class="p-5 pb-4 flex flex-wrap items-center gap-3 justify-between">
            <h3 class="font-bold tracking-tight">Sales</h3>
            <div class="flex items-center gap-2">
                <input type="search" id="sales-search" placeholder="Search…"
                    class="h-9 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3 pr-3 text-[13px] outline-none focus:border-[#5f7f67] w-44 transition">
                <a href="/<?= htmlspecialchars($session) ?>/reports/sales/export/csv?<?= htmlspecialchars(http_build_query(array_filter($filters ?? [], fn ($v) => $v !== null && $v !== ''))) ?>" id="export-csv" data-no-spa download
                    class="h-9 px-3 inline-flex items-center gap-1.5 rounded-xl border border-black/10 dark:border-white/10 text-[12px] font-semibold hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i> CSV
                </a>
                <button type="button" onclick="window.print()"
                    class="h-9 px-3 inline-flex items-center gap-1.5 rounded-xl border border-black/10 dark:border-white/10 text-[12px] font-semibold hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
                    <i data-lucide="printer" class="w-3.5 h-3.5"></i> Print
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="table-glass" id="sales-table">
                <thead>
                    <tr>
                        <th class="cursor-pointer" data-sort="datetime">Generated ▲▼</th>
                        <th class="cursor-pointer" data-sort="code">Code ▲▼</th>
                        <th class="cursor-pointer" data-sort="package">Package ▲▼</th>
                        <th class="cursor-pointer" data-sort="server">Server ▲▼</th>
                        <th class="cursor-pointer" data-sort="sale_type">Sale Type ▲▼</th>
                        <th class="text-right cursor-pointer" data-sort="price">Price ▲▼</th>
                    </tr>
                </thead>
                <tbody id="sales-tbody">
                    <?php foreach ($report['list'] as $row):
                        $dtLabel = date('d M Y', strtotime($row['date'])) . (! empty($row['time']) ? ' · '.substr($row['time'], 0, 5) : ''); ?>
                    <tr data-datetime="<?= htmlspecialchars($row['date'].(! empty($row['time']) ? ' '.$row['time'] : '')) ?>"
                        data-code="<?= htmlspecialchars($row['code']) ?>"
                        data-package="<?= htmlspecialchars($row['package']) ?>"
                        data-server="<?= htmlspecialchars($row['server']) ?>"
                        data-sale_type="<?= htmlspecialchars($row['sale_type']) ?>"
                        data-price="<?= (int) $row['price'] ?>">
                        <td class="whitespace-nowrap"><?= $dtLabel ?></td>
                        <td class="font-mono font-medium"><?= htmlspecialchars($row['code']) ?></td>
                        <td class="font-medium"><?= htmlspecialchars($row['package']) ?></td>
                        <td><span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-black/[.04] dark:bg-white/[.05]"><?= htmlspecialchars($row['server'] ?: '—') ?></span></td>
                        <td>
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold <?= $row['sale_type'] === 'quick_print' ? 'bg-sky-500/10 text-sky-600' : 'bg-purple-500/10 text-purple-600' ?>">
                                <?= $row['sale_type'] === 'quick_print' ? 'Quick Print' : 'Bulk Generate' ?>
                            </span>
                        </td>
                        <td class="text-right tabular-nums font-semibold"><?= salesFmtRp((int) $row['price']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between px-5 py-3 border-t border-black/[.06] dark:border-white/[.06] text-xs opacity-60">
            <span id="pagination-info">—</span>
            <div id="pagination" class="flex items-center gap-1"></div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script src="/assets/js/vendor/apexcharts.min.js" defer></script>
<script>
window.whenReady(function () {
    'use strict';
    var trackChart = (typeof window.kaiTrackChart === 'function') ? window.kaiTrackChart : function (i) { return i; };

    // Bereskan instance ApexCharts saat meninggalkan halaman via SPA
    // (mencegah timer chart yatim -> error console "width NaN").
    // Sweep registry ditangani cleanupSession() di layout sidebar.
    window.__kaiarasaSessionCleanup = null;

    var chartData = <?= json_encode([
        'trend' => array_map(fn ($d) => ['date' => $d['date'], 'revenue' => (int) $d['revenue'], 'sold' => (int) $d['sold']], $trend),
        'byPackage' => array_map(fn ($p) => ['name' => $p['name'], 'count' => (int) $p['count']], $report['by_package'] ?? []),
    ]) ?>;

    var isDark = function () { return document.documentElement.classList.contains('dark'); };
    var gridColor = function () { return isDark() ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.06)'; };
    var fmtRp = function (v) { return 'Rp'+Number(v).toLocaleString('id-ID'); };

    function baseChart(extra) {
        var base = {
            chart: Object.assign({ width: '100%', animations: { enabled: false }, fontFamily: 'inherit', foreColor: isDark() ? '#e9ebe7' : '#1c2420', toolbar: { show: false } }, extra.chart || {}),
            grid: { borderColor: gridColor() },
            tooltip: { theme: isDark() ? 'dark' : 'light' },
        };
        return Object.assign(base, extra);
    }

    // Revenue Trend (area)
    var trendEl = document.getElementById('chart-trend');
    if (trendEl && chartData.trend.length) {
        trackChart(new ApexCharts(trendEl, baseChart({
            series: [{ name: 'Revenue', data: chartData.trend.map(function (d) { return [d.date, d.revenue]; }) }],
            chart: Object.assign({ type: 'area', height: 240 }, {}),
            colors: ['#5f7f67'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .02 } },
            xaxis: { type: 'datetime', labels: { datetimeUTC: false }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { formatter: fmtRp } },
            tooltip: { y: { formatter: function (v) { return fmtRp(v); } } },
        }))).render();
    }

    // Vouchers Sold (bar)
    var volEl = document.getElementById('chart-volume');
    if (volEl && chartData.trend.length) {
        trackChart(new ApexCharts(volEl, baseChart({
            series: [{ name: 'Vouchers', data: chartData.trend.map(function (d) { return d.sold; }) }],
            chart: Object.assign({ type: 'bar', height: 240 }, {}),
            colors: ['#92aa96'],
            plotOptions: { bar: { borderRadius: 6, columnWidth: '55%' } },
            xaxis: { categories: chartData.trend.map(function (d) { return d.date; }) },
            yaxis: { labels: { formatter: function (v) { return v; } } },
            dataLabels: { enabled: true },
        }))).render();
    }

    // Top Packages (donut)
    var pkgEl = document.getElementById('chart-package');
    if (pkgEl && chartData.byPackage.length) {
        trackChart(new ApexCharts(pkgEl, baseChart({
            series: chartData.byPackage.map(function (p) { return p.count; }),
            labels: chartData.byPackage.map(function (p) { return p.name; }),
            chart: Object.assign({ type: 'donut', height: 260 }, {}),
            colors: ['#5f7f67', '#92aa96', '#6b8b73', '#47614d', '#c9d6cc'],
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: { size: '72%', labels: {
                show: true,
                name: { fontSize: '11px' },
                value: { fontSize: '20px', fontWeight: 700 },
                total: { show: true, label: 'Vouchers', fontSize: '11px',
                    formatter: function (w) { return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0); } },
            } } } },
            dataLabels: { enabled: false },
        }))).render();
    }

    // Filter interactions
    var rangeSel = document.getElementById('f-range');
    var customWrap = document.getElementById('custom-range');
    if (rangeSel) {
        rangeSel.addEventListener('change', function () {
            var isCustom = this.value === 'custom';
            var startInput = document.querySelector('input[name="start"]');
            if (startInput) startInput.closest('div').classList.toggle('hidden', !isCustom);
            if (! isCustom) {
                var opt = this.selectedOptions[0];
                var s = document.querySelector('input[name="start"]');
                var e = document.querySelector('input[name="end"]');
                if (s) s.value = opt.getAttribute('data-s');
                if (e) e.value = opt.getAttribute('data-e');
            }
        });
    }
    var form = document.getElementById('sales-filter-form');
    if (form) {
        form.addEventListener('submit', function () {
            var b = document.getElementById('apply-filter');
            var l = document.getElementById('apply-label');
            var sp = document.getElementById('apply-spinner');
            if (b && !b.disabled) {
                b.disabled = true;
                l.classList.add('hidden');
                sp.classList.remove('hidden');
                sp.classList.add('flex');
            }
        });
    }

    // Table search + sort + pagination (client-side, vanilla)
    var tbody = document.getElementById('sales-tbody');
    if (tbody) {
        var allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        var pageSize = 15; var currentPage = 1; var sortKey = null; var sortDir = 1;

        function applyTable() {
            var q = (document.getElementById('sales-search').value || '').toLowerCase();
            var rows = allRows.filter(function (r) { return r.textContent.toLowerCase().indexOf(q) > -1; });
            if (sortKey) {
                rows.sort(function (a, b) {
                    var av = a.getAttribute('data-' + sortKey); var bv = b.getAttribute('data-' + sortKey);
                    av = isNaN(av) ? String(av).toLowerCase() : +av;
                    bv = isNaN(bv) ? String(bv).toLowerCase() : +bv;
                    return (av > bv ? 1 : av < bv ? -1 : 0) * sortDir;
                });
            }
            var pages = Math.max(1, Math.ceil(rows.length / pageSize));
            if (currentPage > pages) currentPage = pages;
            var slice = rows.slice((currentPage - 1) * pageSize, currentPage * pageSize);
            tbody.innerHTML = '';
            slice.forEach(function (r) { tbody.appendChild(r); });
            document.getElementById('pagination-info').textContent =
                'Showing ' + (rows.length ? ((currentPage-1)*pageSize+1) : 0) + '–' + Math.min(currentPage*pageSize, rows.length) + ' of ' + rows.length;
            var pg = document.getElementById('pagination'); pg.innerHTML = '';
            for (var p = 1; p <= pages; p++) {
                (function (pn) {
                    var btn = document.createElement('button');
                    btn.type = 'button'; btn.textContent = pn;
                    btn.className = 'min-w-[26px] h-7 px-1.5 rounded-md text-xs font-semibold transition-colors ' +
                        (pn === currentPage ? 'bg-[#5f7f67] text-white' : 'hover:bg-black/[.05] dark:hover:bg-white/[.07]');
                    btn.onclick = function () { currentPage = pn; applyTable(); };
                    pg.appendChild(btn);
                })(p);
            }
        }

        document.getElementById('sales-search').addEventListener('input', function () { currentPage = 1; applyTable(); });
        document.querySelectorAll('#sales-table th[data-sort]').forEach(function (th) {
            th.addEventListener('click', function () {
                var k = th.getAttribute('data-sort');
                sortDir = (sortKey === k) ? -sortDir : 1;
                sortKey = k; applyTable();
            });
        });

        applyTable();
    }

    if (window.lucide) lucide.createIcons();
});
</script>
<?php require_once ROOT.'/app/Views/layouts/footer_public.php'; ?>
