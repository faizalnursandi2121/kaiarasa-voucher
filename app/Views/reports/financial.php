<?php
use App\Helpers\FormatHelper;
use App\Helpers\LanguageHelper;

$title = 'Financial Report';
require_once ROOT.'/app/Views/layouts/header_main.php';
?>

<?php
$page_title_key = 'reports.financial_title';
$page_title = 'Financial Report';
$page_desc_key = 'reports.financial_subtitle';
$page_desc = 'Comprehensive financial overview: Quick Print income, inventory value, and usage realization.';
$breadcrumbs = [
    ['label' => LanguageHelper::t('common.dashboard', 'Dashboard'), 'href' => "/" . htmlspecialchars($session) . "/dashboard"],
    ['label' => LanguageHelper::t('sidebar.reports', 'Reports'), 'href' => null],
    ['label' => LanguageHelper::t('reports_menu.financial', 'Financial Report'), 'href' => null],
];
require_once ROOT.'/app/Views/layouts/page_header.php';
?>

<div class="page-toolbar">
    <div></div>
    <div class="page-toolbar-right">
        <div class="dropdown dropdown-end relative" id="export-dropdown">
            <button class="btn btn-secondary dropdown-toggle" onclick="document.getElementById('export-menu').classList.toggle('hidden')">
                <i data-lucide="download" class="w-4 h-4 mr-2"></i> <span data-i18n="reports.export">Export</span>
                <i data-lucide="chevron-down" class="w-4 h-4 ml-1"></i>
            </button>
            <div id="export-menu" class="dropdown-menu hidden absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-black border border-accents-2 z-50 p-1">
                <button onclick="exportReport('csv')" class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-accents-1 rounded flex items-center">
                    <i data-lucide="file-text" class="w-4 h-4 mr-2 text-green-600"></i> Export CSV
                </button>
                <button onclick="exportReport('xlsx')" class="block w-full text-left px-4 py-2 text-sm text-foreground hover:bg-accents-1 rounded flex items-center">
                    <i data-lucide="sheet" class="w-4 h-4 mr-2 text-green-600"></i> Export Excel
                </button>
            </div>
        </div>
        <button onclick="location.reload()" class="btn btn-secondary">
            <i data-lucide="refresh-cw" class="w-4 h-4 mr-2"></i> <span data-i18n="reports.refresh">Refresh</span>
        </button>
        <button onclick="window.print()" class="btn btn-primary">
            <i data-lucide="printer" class="w-4 h-4 mr-2"></i> <span data-i18n="reports.print_report">Print Report</span>
        </button>
    </div>
</div>
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <!-- Total Income -->
    <div class="card">
        <div class="text-sm text-accents-5 uppercase font-bold tracking-wide" data-i18n="reports.total_income">Total Potential</div>
        <div class="text-3xl font-bold text-accents-6 mt-2">
            <?= FormatHelper::formatCurrency($totalIncome ?? 0, $currency) ?>
        </div>
        <div class="text-xs text-accents-5 mt-1">
            <?= number_format($totalVouchers ?? 0) ?> vouchers
        </div>
    </div>
    
    <!-- Realized Income -->
    <div class="card !bg-green-500/10 !border-green-500/20">
        <div class="text-sm text-green-600 dark:text-green-400 uppercase font-bold tracking-wide" data-i18n="reports.realized_income">Realized Income</div>
        <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
            <?= FormatHelper::formatCurrency($realizedIncome ?? 0, $currency) ?>
        </div>
        <div class="text-xs text-green-600/70 dark:text-green-400/70 mt-1">
            <?= number_format($realizedVouchers ?? 0) ?> vouchers (Quick Print + Used)
        </div>
    </div>
    
    <!-- Inventory Income (Pending) -->
    <div class="card !bg-yellow-500/10 !border-yellow-500/20">
        <div class="text-sm text-yellow-600 dark:text-yellow-400 uppercase font-bold tracking-wide" data-i18n="reports.inventory_value">Inventory Value</div>
        <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">
            <?= FormatHelper::formatCurrency($inventoryIncome ?? 0, $currency) ?>
        </div>
        <div class="text-xs text-yellow-600/70 dark:text-yellow-400/70 mt-1">
            <?= number_format($inventoryVouchers ?? 0) ?> unused vouchers
        </div>
    </div>
    
    <!-- Avg Price -->
    <div class="card">
        <div class="text-sm text-accents-5 uppercase font-bold tracking-wide" data-i18n="reports.avg_price">Avg Price</div>
        <div class="text-3xl font-bold text-accents-6 mt-2">
            <?= $currency ?> <?= $totalVouchers > 0 ? number_format(($totalIncome / $totalVouchers), 0, ',', '.') : '0' ?>
        </div>
        <div class="text-xs text-accents-5 mt-1" data-i18n="reports.per_voucher">per voucher</div>
    </div>
</div>

<!-- Tabs -->
<div class="mb-6 border-b border-accents-2">
    <nav class="flex space-x-4 overflow-x-auto no-scrollbar" aria-label="Tabs">
        <button onclick="switchTab('batch')" id="tab-batch" class="px-3 py-2 text-sm font-medium border-b-2 border-primary text-primary active-tab whitespace-nowrap" data-i18n="reports.by_batch">By Batch (Audit)</button>
        <button onclick="switchTab('time')" id="tab-time" class="px-3 py-2 text-sm font-medium border-b-2 border-transparent text-accents-5 hover:text-foreground whitespace-nowrap" data-i18n="reports.by_time">By Time (Trend)</button>
    </nav>
</div>

<!-- Batch Tab (Expandable Table) -->
<div id="content-batch" class="tab-content">
    <div class="space-y-4">
        <table class="table-glass" id="report-table-batch">
            <thead>
                <tr>
                    <th class="w-8"></th>
                    <th data-i18n="reports.date">Date</th>
                    <th data-i18n="reports.reference">Batch / Reference</th>
                    <th data-i18n="reports.status">Status</th>
                    <th class="text-right" data-i18n="reports.qty">Qty</th>
                    <th class="text-right text-green-500" data-i18n="reports.realized_short">Revenue</th>
                    <th class="text-right" data-i18n="reports.total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($report)) { ?>
                    <tr><td colspan="7" class="p-8 text-center text-accents-5" data-i18n="reports.no_data">No data found.</td></tr>
                <?php } else { ?>
                    <?php foreach ($report as $row) { ?>
                        <tr class="batch-row" onclick="toggleBatch('batch-<?= htmlspecialchars($row['date'].$row['reference']) ?>')" style="cursor: pointer;">
                            <td class="text-center">
                                <i data-lucide="chevron-right" class="w-4 h-4 batch-toggle transition-transform"></i>
                            </td>
                            <td class="font-medium"><?= htmlspecialchars($row['date']) ?></td>
                            <td class="text-xs text-accents-5"><?= htmlspecialchars($row['reference']) ?></td>
                            <td>
                                <?php if ($row['status'] === 'New') { ?>
                                    <span class="px-2 py-1 text-xs font-bold rounded-md bg-accents-2 text-accents-6">NEW</span>
                                <?php } elseif ($row['status'] === 'Selling') { ?>
                                    <span class="px-2 py-1 text-xs font-bold rounded-md bg-blue-500/10 text-blue-500 border border-blue-500/20">SELLING</span>
                                <?php } elseif ($row['status'] === 'Sold Out') { ?>
                                    <span class="px-2 py-1 text-xs font-bold rounded-md bg-green-500/10 text-green-500 border border-green-500/20">SOLD OUT</span>
                                <?php } ?>
                            </td>
                            <td class="text-right font-mono"><?= number_format($row['count']) ?></td>
                            <td class="text-right font-mono text-green-500">
                                <?= number_format($row['realized_count']) ?>
                                <span class="text-xs opacity-70 block"><?= FormatHelper::formatCurrency($row['realized_total'], $currency) ?></span>
                            </td>
                            <td class="text-right font-mono font-bold"><?= FormatHelper::formatCurrency($row['total'], $currency) ?></td>
                        </tr>
                        <tr id="batch-<?= htmlspecialchars($row['date'].$row['reference']) ?>" class="hidden">
                            <td></td>
                            <td colspan="6" class="p-0">
                                <div class="p-4 bg-accents-1 border-t border-accents-2">
                                    <table class="table-glass">
                                        <thead>
                                            <tr>
                                                <th data-i18n="reports.username">Voucher</th>
                                                <th data-i18n="reports.type">Type</th>
                                                <th data-i18n="reports.profile">Profile</th>
                                                <th class="text-right" data-i18n="reports.price">Price</th>
                                                <th data-i18n="reports.status">Status</th>
                                                <th data-i18n="reports.comment">Comment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($row['vouchers'] ?? [] as $v) { ?>
                                            <tr>
                                                <td class="font-mono font-medium"><?= htmlspecialchars($v['username']) ?></td>
                                                <td>
                                                    <?php if ($v['is_quickprint']) { ?>
                                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-500/10 text-purple-500">QP</span>
                                                    <?php } else { ?>
                                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-accents-2 text-accents-6">GEN</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-xs"><?= htmlspecialchars($v['profile']) ?></td>
                                                <td class="text-right font-mono"><?= $currency ?> <?= number_format($v['price'], 0, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($v['status'] === 'Sold (Quick Print)') { ?>
                                                        <span class="px-2 py-1 text-xs font-bold rounded-md bg-green-500/10 text-green-500">SOLD</span>
                                                    <?php } elseif ($v['status'] === 'Used (Inventory)') { ?>
                                                        <span class="px-2 py-1 text-xs font-bold rounded-md bg-blue-500/10 text-blue-500">USED</span>
                                                    <?php } else { ?>
                                                        <span class="px-2 py-1 text-xs font-bold rounded-md bg-accents-2 text-accents-5">STOCK</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-xs text-accents-5"><?= htmlspecialchars($v['comment']) ?></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Time Tab (Daily/Monthly/Yearly) -->
<div id="content-time" class="tab-content hidden">
    <div class="mb-6 border-b border-accents-2">
        <nav class="flex space-x-4 overflow-x-auto no-scrollbar" aria-label="SubTabs">
            <button onclick="switchTimeTab('daily')" id="tab-time-daily" class="px-3 py-2 text-sm font-medium border-b-2 border-primary text-primary active-tab whitespace-nowrap" data-i18n="reports.daily">Daily</button>
            <button onclick="switchTimeTab('monthly')" id="tab-time-monthly" class="px-3 py-2 text-sm font-medium border-b-2 border-transparent text-accents-5 hover:text-foreground whitespace-nowrap" data-i18n="reports.monthly">Monthly</button>
            <button onclick="switchTimeTab('yearly')" id="tab-time-yearly" class="px-3 py-2 text-sm font-medium border-b-2 border-transparent text-accents-5 hover:text-foreground whitespace-nowrap" data-i18n="reports.yearly">Yearly</button>
        </nav>
    </div>

    <div id="content-time-daily" class="time-tab-content">
        <table class="table-glass" id="table-daily">
            <thead>
                <tr>
                    <th data-i18n="reports.date">Date</th>
                    <th class="text-right" data-i18n="reports.qty">Qty</th>
                    <th class="text-right text-green-500" data-i18n="reports.realized_short">Realized</th>
                    <th class="text-right text-yellow-500" data-i18n="reports.pending_income_short">Inventory</th>
                    <th class="text-right text-green-500" data-i18n="reports.realized_income">Realized Income</th>
                    <th class="text-right" data-i18n="reports.total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daily as $date => $row) { ?>
                <tr>
                    <td class="font-medium"><?= $date ?></td>
                    <td class="text-right font-mono"><?= number_format($row['count']) ?></td>
                    <td class="text-right font-mono text-green-500"><?= number_format($row['realized']) ?></td>
                    <td class="text-right font-mono text-yellow-500"><?= number_format($row['inventory']) ?></td>
                    <td class="text-right font-mono text-green-500"><?= $currency ?> <?= number_format($row['realized_income'], 0, ',', '.') ?></td>
                    <td class="text-right font-mono font-bold"><?= $currency ?> <?= number_format($row['total'], 0, ',', '.') ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div id="content-time-monthly" class="time-tab-content hidden">
        <table class="table-glass" id="table-monthly">
            <thead>
                <tr>
                    <th data-i18n="reports.month">Month</th>
                    <th class="text-right" data-i18n="reports.qty">Qty</th>
                    <th class="text-right text-green-500" data-i18n="reports.realized_short">Realized</th>
                    <th class="text-right text-yellow-500" data-i18n="reports.pending_income_short">Inventory</th>
                    <th class="text-right text-green-500" data-i18n="reports.realized_income">Realized Income</th>
                    <th class="text-right" data-i18n="reports.total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthly as $date => $row) { ?>
                <tr>
                    <td class="font-medium"><?= $date ?></td>
                    <td class="text-right font-mono"><?= number_format($row['count']) ?></td>
                    <td class="text-right font-mono text-green-500"><?= number_format($row['realized']) ?></td>
                    <td class="text-right font-mono text-yellow-500"><?= number_format($row['inventory']) ?></td>
                    <td class="text-right font-mono text-green-500"><?= $currency ?> <?= number_format($row['realized_income'], 0, ',', '.') ?></td>
                    <td class="text-right font-mono font-bold"><?= $currency ?> <?= number_format($row['total'], 0, ',', '.') ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div id="content-time-yearly" class="time-tab-content hidden">
        <table class="table-glass" id="table-yearly">
            <thead>
                <tr>
                    <th data-i18n="reports.year">Year</th>
                    <th class="text-right" data-i18n="reports.qty">Qty</th>
                    <th class="text-right text-green-500" data-i18n="reports.realized_short">Realized</th>
                    <th class="text-right text-yellow-500" data-i18n="reports.pending_income_short">Inventory</th>
                    <th class="text-right text-green-500" data-i18n="reports.realized_income">Realized Income</th>
                    <th class="text-right" data-i18n="reports.total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($yearly as $date => $row) { ?>
                <tr>
                    <td class="font-medium"><?= $date ?></td>
                    <td class="text-right font-mono"><?= number_format($row['count']) ?></td>
                    <td class="text-right font-mono text-green-500"><?= number_format($row['realized']) ?></td>
                    <td class="text-right font-mono text-yellow-500"><?= number_format($row['inventory']) ?></td>
                    <td class="text-right font-mono text-green-500"><?= $currency ?> <?= number_format($row['realized_income'], 0, ',', '.') ?></td>
                    <td class="text-right font-mono font-bold"><?= $currency ?> <?= number_format($row['total'], 0, ',', '.') ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="/assets/vendor/xlsx/xlsx.full.min.js"></script>

<script>
    window.whenReady(() => {
        if (typeof SimpleDataTable !== 'undefined') {
            // Only init SimpleDataTable on Time tab tables (no expandable rows)
            new SimpleDataTable('#table-daily', { itemsPerPage: 10, searchable: true });
            new SimpleDataTable('#table-monthly', { itemsPerPage: 10, searchable: true });
            new SimpleDataTable('#table-yearly', { itemsPerPage: 10, searchable: true });
        }
    });

    window.toggleBatch = function(id) {
        const row = document.getElementById(id);
        if (!row) return;
        row.classList.toggle('hidden');
        const mainRow = row.previousElementSibling;
        const icon = mainRow.querySelector('.batch-toggle');
        if (icon) icon.classList.toggle('rotate-90');
    }

    window.switchTab = function(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('content-' + tabName).classList.remove('hidden');

        document.querySelectorAll('nav[aria-label="Tabs"] button').forEach(el => {
            el.classList.remove('border-primary', 'text-primary');
            el.classList.add('border-transparent', 'text-accents-5');
        });
        const btn = document.getElementById('tab-' + tabName);
        btn.classList.remove('border-transparent', 'text-accents-5');
        btn.classList.add('border-primary', 'text-primary');
    }

    window.switchTimeTab = function(tabName) {
        document.querySelectorAll('.time-tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('content-time-' + tabName).classList.remove('hidden');

        document.querySelectorAll('nav[aria-label="SubTabs"] button').forEach(el => {
            el.classList.remove('border-primary', 'text-primary');
            el.classList.add('border-transparent', 'text-accents-5');
        });
        const btn = document.getElementById('tab-time-' + tabName);
        btn.classList.remove('border-transparent', 'text-accents-5');
        btn.classList.add('border-primary', 'text-primary');
    }

    window.exportReport = async function(type) {
        const url = '/<?= $session ?>/reports/financial/export/' + type;
        const btn = document.querySelector('.dropdown-toggle');
        const originalText = btn.innerHTML;
        btn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 mr-2 animate-spin"></i> Processing...`;
        lucide.createIcons();

        try {
            const response = await fetch(url);
            const data = await response.json();
            if (data.error) { alert('Export Failed: ' + data.error); return; }
            const filename = `financial-report-<?= date('Y-m-d') ?>-${type}.` + (type === 'csv' ? 'csv' : 'xlsx');

            if (type === 'csv') {
                const header = Object.keys(data[0]);
                const csv = [header.join(','), ...data.map(row => header.map(f => JSON.stringify(row[f])).join(','))].join('\r\n');
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.setAttribute('hidden', ''); a.setAttribute('href', url); a.setAttribute('download', filename);
                document.body.appendChild(a); a.click(); document.body.removeChild(a);
            } else if (type === 'xlsx') {
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Financial Report");
                XLSX.writeFile(wb, filename);
            }
        } catch (error) {
            console.error('Export Error:', error); alert('Failed to export data.');
        } finally {
            btn.innerHTML = originalText; lucide.createIcons();
            document.getElementById('export-menu').classList.add('hidden');
        }
    }
</script>

<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
