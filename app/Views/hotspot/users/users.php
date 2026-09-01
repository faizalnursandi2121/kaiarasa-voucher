<?php

use App\Helpers\FormatHelper;
use App\Helpers\HotspotHelper;
use App\Helpers\ViewHelper;
use App\Helpers\LanguageHelper;

$title = 'Vouchers';
require_once ROOT.'/app/Views/layouts/header_main.php';

// Prepare Filters Data
$uniqueProfiles = [];
$uniqueComments = [];
if (! empty($users)) {
    foreach ($users as $u) {
        $p = $u['profile'] ?? 'default';
        $c = $u['comment'] ?? '';
        $uniqueProfiles[$p] = $p; // Key-Value distinct
        if (! empty($c)) {
            $uniqueComments[$c] = $c;
        }
    }
}
// Tambahkan semua profile valid dari MikroTik (termasuk yang
// tidak digunakan voucher manapun) — supaya filter dropdown
// bisa filter by profile valid manapun
if (! empty($allProfiles ?? [])) {
    foreach ($allProfiles as $ap) {
        $uniqueProfiles[$ap] = $ap;
    }
}
sort($uniqueProfiles);


// $servers is passed from controller
if (! isset($servers)) {
    $servers = [];
}

sort($uniqueComments);
?>

<?php
$page_title_key = 'hotspot_users.title';
$page_title = 'Vouchers';
$page_desc_key = 'hotspot_users.subtitle';
$page_desc = 'Manage vouchers and user accounts for session: ' . htmlspecialchars($session);
$breadcrumbs = [
    ['label' => LanguageHelper::t('common.dashboard', 'Dashboard'), 'href' => "/" . htmlspecialchars($session) . "/dashboard"],
    ['label' => LanguageHelper::t('hotspot_users.title', 'Vouchers'), 'href' => null],
];
require_once ROOT.'/app/Views/layouts/page_header.php';
?>

<?php if ($error) { ?>
    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 flex items-center dark:bg-red-900/20 dark:text-red-400 dark:border dark:border-red-500/20">
        <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i>
        <?= htmlspecialchars($error) ?>
    </div>
<?php } ?>

<!-- Batch Action Toolbar -->
<div id="batch-toolbar" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-foreground text-background px-6 py-3 rounded-full shadow-lg z-50 flex items-center gap-4 transition-all duration-300 translate-y-20 opacity-0">
    <span class="text-sm font-medium"><span id="selected-count">0</span> <span data-i18n="common.selected">Selected</span></span>
    <div class="h-4 w-px bg-background/20"></div>
    <button onclick="printSelected()" class="flex items-center gap-2 hover:text-accents-2 transition-colors font-bold text-sm">
        <i data-lucide="printer" class="w-4 h-4"></i> <span data-i18n="common.print">Print</span>
    </button>
    <button onclick="deleteSelected()" class="flex items-center gap-2 text-red-400 hover:text-red-300 transition-colors font-bold text-sm">
        <i data-lucide="trash-2" class="w-4 h-4"></i> <span data-i18n="common.delete">Delete</span>
    </button>
</div>

<?php
$toolbar_html = '
    <div class="input-group md:w-64 z-10">
        <div class="input-icon">
            <i data-lucide="search" class="h-4 w-4"></i>
        </div>
        <input type="text" id="global-search" class="form-input-search w-full" placeholder="Search user..." data-i18n="common.table.search_placeholder">
    </div>
    <div class="page-toolbar-right">
        <div class="w-40">
            <select id="filter-profile" class="custom-select form-filter" data-search="true">
                <option value="" data-i18n="common.all_profiles">All Data Plans</option>';
foreach ($uniqueProfiles as $p) {
    $toolbar_html .= '<option value="' . htmlspecialchars($p) . '">' . htmlspecialchars($p) . '</option>';
}
$toolbar_html .= '</select>
        </div>
        <div class="w-40">
            <select id="filter-comment" class="custom-select form-filter" data-search="true">
                <option value="" data-i18n="common.all_comments">All Comments</option>';
foreach ($uniqueComments as $c) {
    $toolbar_html .= '<option value="' . htmlspecialchars($c) . '">' . htmlspecialchars($c) . '</option>';
}
$toolbar_html .= '</select>
        </div>
        <button onclick="openUserModal(\'add\')" class="btn btn-primary">' .
    '<i data-lucide="plus" class="w-4 h-4 mr-2"></i> <span data-i18n="hotspot_users.add_user">Add Voucher</span>' .
    '</button>';
$toolbar_html .= '
    </div>
';
?>
<div class="page-toolbar">
<?php echo $toolbar_html; ?>
</div>
<!-- Active Filter Chips -->
<div class="filter-chips-bar">
    <div class="filter-chips"></div>
    <button type="button" class="filter-clear hidden" data-i18n="common.clear_all">Clear all</button>
</div>

    <?php if (empty($users)) { ?>
    <!-- Empty State -->
    <div class="rounded-2xl border border-dashed border-black/[.10] dark:border-white/[.08] p-12 text-center">
        <i data-lucide="inbox" class="w-8 h-8 mx-auto opacity-30 mb-3"></i>
        <p class="text-sm opacity-60">No vouchers yet.</p>
        <a href="/<?php echo htmlspecialchars($session) ?>/hotspot/generate" class="inline-block mt-3 text-[13px] font-semibold text-[#47614d] dark:text-[#92aa96] hover:underline">+ Generate your first batch</a>
    </div>
    <?php } else {
    // Hitung voucher orphan: profile reference sudah dihapus dari Data Plans
    $orphanCount = 0;
    foreach ($users as $u) {
        $p = (string) ($u['profile'] ?? '');
        if ($p !== '' && ! isset($validProfiles[$p])) {
            $orphanCount++;
        }
    }
    ?>
    <?php if ($orphanCount > 0): ?>
    <!-- Orphan Warning Banner -->
    <div class="mb-4 rounded-2xl border border-red-500/30 bg-red-500/[.07] p-4 flex items-start gap-3">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-red-600 dark:text-red-400">
                <strong><?= (int) $orphanCount ?></strong>
                <span data-i18n="hotspot_users.orphan_banner_text">voucher(s) reference a data plan that no longer exists.</span>
            </p>
            <p class="text-xs text-red-600/80 dark:text-red-400/80 mt-1">
                <span data-i18n="hotspot_users.orphan_banner_help">Select broken rows and use the toolbar to re-assign or delete them.</span>
            </p>
        </div>
        <button type="button" onclick="selectAllBroken()" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-500 text-white text-xs font-semibold hover:bg-red-600 transition-colors flex-shrink-0">
            <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
            <span data-i18n="hotspot_users.orphan_select_all">Select All Broken</span>
        </button>
    </div>
    <?php endif; ?>
    <!-- Table Container -->
    <div class="table-container">
        <table class="table-glass" id="users-table">
            <thead>
                <tr>
                    <th scope="col" class="px-4 py-3 w-10">
                        <input type="checkbox" id="select-all" class="checkbox">
                    </th>
                    <th data-sort="name" class="sortable cursor-pointer hover:text-foreground select-none" data-i18n="hotspot_users.name">Name</th>
                    <th data-sort="profile" class="sortable cursor-pointer hover:text-foreground select-none" data-i18n="hotspot_users.profile">Data Plans</th>
                    <th data-i18n="hotspot_users.uptime_limit">Uptime / Limit</th>
                    <th data-i18n="hotspot_users.bytes_in_out">Bytes In/Out</th>
                    <th data-sort="comment" class="sortable cursor-pointer hover:text-foreground select-none" data-i18n="hotspot_users.comment">Comment</th>
                    <th class="relative text-right" data-i18n="common.actions">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody id="table-body">
                <?php foreach ($users as $user) {
                    // Helper to split time limit for editing (Simple parsing or raw passing)
                    // Assuming time limit format from router is like 1d2h3m or just 1h
                    // We will pass the raw string if we can't easily split, OR rely on a JS parser.
                    // For now let's pass raw limit-uptime.

                    // Just prepare some safe values
                    $id = $user['.id'];
                    $name = $user['name'] ?? '';
                    $profile = $user['profile'] ?? 'default';
                    $profileMissing = ! isset($validProfiles[$profile]);
                    $comment = $user['comment'] ?? '';
                    $server = $user['server'] ?? 'all';
                    $password = $user['password'] ?? '';

                    // Limits
                    $limitUptime = $user['limit-uptime'] ?? '';
                    $limitBytes = $user['limit-bytes-total'] ?? '';
                        ?>
                    <tr class="table-row-item" 
                        data-id="<?= htmlspecialchars($id) ?>"
                        data-name="<?= strtolower($name) ?>" 
                        data-rawname="<?= htmlspecialchars($name) ?>"
                        data-profile="<?= htmlspecialchars($profile) ?>" 
                        data-comment="<?= htmlspecialchars($comment) ?>"
                        data-comment-raw="<?= htmlspecialchars($comment) ?>"
                        data-password="<?= htmlspecialchars($password) ?>"
                        data-server="<?= htmlspecialchars($server) ?>"
                        data-limit-uptime="<?= htmlspecialchars($limitUptime) ?>"
                        data-limit-bytes-total="<?= htmlspecialchars($limitBytes) ?>"
                        data-profile-missing="<?= $profileMissing ? '1' : '0' ?>">

                        
                        <td class="px-4 py-4">
                            <input type="checkbox" name="selected_users[]" value="<?= htmlspecialchars($id) ?>" class="user-checkbox checkbox">
                        </td>
                        <td>
                            <div class="flex items-center w-full">
                                <div class="h-8 w-8 rounded bg-accents-2 flex items-center justify-center text-xs font-bold mr-3 text-accents-6 flex-shrink-0">
                                    <i data-lucide="user" class="w-4 h-4"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="text-sm font-medium text-foreground truncate"><?= htmlspecialchars($name) ?></div>
                                        <?php
                                                $status = HotspotHelper::getUserStatus($user);
                        echo ViewHelper::badge($status);
                        ?>
                                    </div>
                                    <div class="text-xs text-accents-5"><?= htmlspecialchars($password) ?></div>
                                </div>
                            </div>
                        </td>
                        <?php
                            $profileMissing = ! isset($validProfiles[$profile]);
                        ?>
                        <td>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $profileMissing ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' ?>">
                                    <?= htmlspecialchars($profile) ?>
                                </span>
                                <?php if ($profileMissing): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-500/15 text-red-600 dark:text-red-400" title="Data plan ini sudah dihapus dari Data Plans. Edit voucher untuk assign data plan baru, atau hapus voucher ini.">
                                    <i data-lucide="alert-triangle" class="w-3 h-3 mr-1"></i> DATA PLAN MISSING
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm text-foreground"><?= FormatHelper::elapsedTime($user['uptime'] ?? '0s') ?></div>
                            <div class="text-xs text-accents-5">Limit: <?= FormatHelper::elapsedTime($user['limit-uptime'] ?? 'unlimited') ?></div>
                        </td>
                        <td>
                            <div class="text-xs text-accents-5 flex flex-col gap-1">
                                <span class="flex items-center"><i data-lucide="arrow-down" class="w-3 h-3 mr-1 text-green-500"></i> <?= FormatHelper::formatBytes($user['bytes-in'] ?? 0) ?></span>
                                <span class="flex items-center"><i data-lucide="arrow-up" class="w-3 h-3 mr-1 text-blue-500"></i> <?= FormatHelper::formatBytes($user['bytes-out'] ?? 0) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm text-accents-5 italic"><?= htmlspecialchars($comment) ?></div>
                        </td>
                        <td class="text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2 table-actions-reveal">
                                <button onclick="printUser('<?= htmlspecialchars($id) ?>')" class="btn-icon" title="Print">
                                    <i data-lucide="printer" class="w-4 h-4"></i>
                                </button>
                                <button onclick="openUserModal('edit', this)" class="btn-icon inline-flex items-center justify-center" title="Edit">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                                <form action="/<?= htmlspecialchars($session) ?>/hotspot/delete" method="POST" onsubmit="event.preventDefault(); Kaiarasa.confirm('Delete User?', 'Are you sure you want to delete user <?= htmlspecialchars($name) ?>?', 'Delete', 'Cancel').then(res => { if(res) this.submit(); });" class="inline">
                                    <input type="hidden" name="session" value="<?= htmlspecialchars($session) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="btn-icon-danger" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
                <?php } ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-accents-2 dark:border-white/10 flex items-center justify-between" id="pagination-controls">
            <div class="text-sm text-accents-5">
                 <span id="pagination-text">Showing <span id="start-idx" class="font-medium text-foreground">0</span> to <span id="end-idx" class="font-medium text-foreground">0</span> of <span id="total-count" class="font-medium text-foreground">0</span> users</span>
            
    </div>
            <div class="flex gap-2">
                <button id="prev-btn" class="btn btn-sm btn-secondary" disabled data-i18n="common.previous">Previous</button>
                <div id="page-numbers" class="flex gap-1"></div>
                <button id="next-btn" class="btn btn-sm btn-secondary" disabled data-i18n="common.next">Next</button>
            </div>
        </div>
    </div>

<!-- Add/Edit User Template -->
<template id="user-form-template">
    <!-- Modal Head (Sage Tint — pattern Generate Vouchers) -->
    <div class="bg-[#5f7f67]/[.07] dark:bg-[#5f7f67]/[.12] border-b border-[#5f7f67]/20 px-6 py-4 flex items-start justify-between gap-4 text-left">
        <div>
            <h3 class="text-base font-bold tracking-tight" data-modal-title>Add Voucher</h3>
            <p class="text-xs opacity-60 mt-1" data-i18n="common.users_form_subtitle">Voucher login credentials &amp; limits</p>
        </div>
        <button type="button" onclick="Swal.close()" aria-label="Close"
            class="w-8 h-8 rounded-lg inline-flex items-center justify-center hover:bg-black/[.05] dark:hover:bg-white/[.06] transition-colors shrink-0">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    <div class="p-6 text-left">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form Column -->
        <div class="lg:col-span-2 min-w-0">
            <form id="user-form" action="/<?= htmlspecialchars($session) ?>/hotspot/store" method="POST" class="space-y-4">
                <input type="hidden" name="session" value="<?= htmlspecialchars($session) ?>">
                <input type="hidden" name="id" id="form-id" disabled> <!-- Disabled for Add -->

                                <div class="flex items-center gap-3 pt-2">
                    <span class="text-[13px] font-bold uppercase tracking-[0.14em]" data-i18n="common.account">Account</span>
                    <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_users.form.username">Username</label>
                        <div class="relative">
                            <i data-lucide="user" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                            <input type="text" name="name" required class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition" data-i18n-placeholder="hotspot_users.form.username_placeholder" placeholder="e.g. voucher123">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_users.form.password">Password</label>
                        <div class="relative">
                            <i data-lucide="key" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                            <input type="text" name="password" required class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition" data-i18n-placeholder="hotspot_users.form.password_placeholder" placeholder="e.g. 123456">
                        </div>
                    </div>
                </div>

                                <div class="flex items-center gap-3 pt-2">
                    <span class="text-[13px] font-bold uppercase tracking-[0.14em]" data-i18n="common.access">Access</span>
                    <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
                </div>

                <div>
                    <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_users.form.profile">Data plan</label>
                    <div class="relative">
                        <select name="profile" data-native required class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-9 text-[14px] appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                            <?php foreach (($allProfiles ?? []) as $p): ?>
                            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_users.form.server">Server</label>
                        <div class="relative">
                            <i data-lucide="server" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                            <select name="server" data-native class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-9 text-[14px] appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                                <option value="all">all</option>
                                <?php
                                if (! empty($servers)) {
                                    foreach ($servers as $s) {
                                        $sName = $s['name'] ?? '';
                                        if ($sName === 'all' || empty($sName)) {
                                            continue;
                                        }
                                        ?>
                                <option value="<?= htmlspecialchars($sName) ?>"><?= htmlspecialchars($sName) ?></option>
                            <?php
                                    }
                                }
?>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_users.form.comment">Comment</label>
                        <div class="relative">
                            <i data-lucide="message-square" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                            <input type="text" name="comment" class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition" placeholder="Optional note">
                        </div>
                    </div>
                </div>

                                <div class="flex items-center gap-3 pt-2">
                    <span class="text-[13px] font-bold uppercase tracking-[0.14em]" data-i18n="common.limits">Limits</span>
                    <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
                </div>

                <div>
                    <span class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_users.form.time_limit">Validity</span>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="relative">
                            <input type="number" name="timelimit_d" min="0" placeholder="0" aria-label="Days"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-12 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                            <span class="absolute inset-y-0 right-3.5 flex items-center text-[12px] font-semibold text-accents-5 pointer-events-none">Days</span>
                        </div>
                        <div class="relative">
                            <input type="number" name="timelimit_h" min="0" max="23" placeholder="0" aria-label="Hours"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-12 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                            <span class="absolute inset-y-0 right-3.5 flex items-center text-[12px] font-semibold text-accents-5 pointer-events-none">Hours</span>
                        </div>
                        <div class="relative">
                            <input type="number" name="timelimit_m" min="0" max="59" placeholder="0" aria-label="Minutes"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-12 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                            <span class="absolute inset-y-0 right-3.5 flex items-center text-[12px] font-semibold text-accents-5 pointer-events-none">Minutes</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_users.form.data_limit">Data limit</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1 min-w-0">
                            <i data-lucide="database" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                            <input type="number" name="datalimit_val" min="0" placeholder="0"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        </div>
                        <div class="relative w-24 shrink-0">
                            <select name="datalimit_unit" data-native aria-label="Data limit unit"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-8 text-[14px] text-center appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                                <option value="MB" selected>MB</option>
                                <option value="GB">GB</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Quick Tips -->
        <aside class="self-start rounded-2xl border border-dashed border-black/[.08] dark:border-white/[.08] bg-white/50 dark:bg-white/[.03] p-5">
            <h3 class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider mb-3">
                <i data-lucide="lightbulb" class="w-3.5 h-3.5 text-yellow-500"></i>
                <span data-i18n="hotspot_users.form.quick_tips">Quick Tips</span>
            </h3>
            <ul class="space-y-1.5 text-xs leading-relaxed list-none p-0 m-0">
                <li class="flex gap-2">
                    <span class="w-1 h-1 rounded-full bg-[#5f7f67] mt-1.5 flex-shrink-0"></span>
                    <span data-i18n="hotspot_users.form.tip_profiles"><strong>Profiles</strong> define the default speed limits, session timeout, and shared users policy.</span>
                </li>
                <li class="flex gap-2">
                    <span class="w-1 h-1 rounded-full bg-[#5f7f67] mt-1.5 flex-shrink-0"></span>
                    <span data-i18n="hotspot_users.form.tip_time_limit"><strong>Validity</strong> is the total accumulated uptime allowed for this user.</span>
                </li>
                <li class="flex gap-2">
                    <span class="w-1 h-1 rounded-full bg-[#5f7f67] mt-1.5 flex-shrink-0"></span>
                    <span data-i18n="hotspot_users.form.tip_data_limit"><strong>Data Limit</strong> will override the profile's data limit settings if specified here. Set to 0 to use profile default.</span>
                </li>
            </ul>
        </aside>
        </div>
    </div>
</template>

<script>
    window.TableManager = window.TableManager || class TableManager {
        constructor(rows, itemsPerPage = 10) {
            this.allRows = Array.from(rows);
            this.filteredRows = this.allRows;
            this.itemsPerPage = itemsPerPage;
            this.currentPage = 1;

            this.elements = {
                body: document.getElementById('table-body'),
                startIdx: document.getElementById('start-idx'),
                endIdx: document.getElementById('end-idx'),
                totalCount: document.getElementById('total-count'),
                prevBtn: document.getElementById('prev-btn'),
                nextBtn: document.getElementById('next-btn'),
                pageNumbers: document.getElementById('page-numbers')
            };

            this.filters = {
                search: '',
                profile: '',
                comment: ''
            };

            this.init();
        }

        init() {
            this.setupListeners();
            this.update();
        }

        setupListeners() {
            // Search Input
            document.getElementById('global-search').addEventListener('input', window.Kaiarasa.debounce((e) => {
                this.filters.search = e.target.value.toLowerCase();
                this.currentPage = 1;
                this.update();
            }, 300));

            // Prev/Next
            this.elements.prevBtn.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.render();
                }
            });

            this.elements.nextBtn.addEventListener('click', () => {
                const maxPage = Math.ceil(this.filteredRows.length / this.itemsPerPage);
                if (this.currentPage < maxPage) {
                    this.currentPage++;
                    this.render();
                }
            });
            
            // Filters (null-check for SPA cross-page TableManager reuse)
            const filterProfile = document.getElementById('filter-profile');
            if (filterProfile) filterProfile.addEventListener('change', (e) => {
                this.filters.profile = e.target.value;
                this.currentPage = 1;
                this.update();
            });
            
            const filterComment = document.getElementById('filter-comment');
            if (filterComment) filterComment.addEventListener('change', (e) => {
                this.filters.comment = e.target.value;
                this.currentPage = 1;
                this.update();
            });

             // Listen for language change
             window.kaiarasaOnLangChange( () => {
                this.render();
            });
        }

        update() {
            // Apply Filters
            this.filteredRows = this.allRows.filter(row => {
                const name = row.dataset.name || '';
                const comment = (row.dataset.comment || '').toLowerCase();
                const profile = row.dataset.profile || '';
                
                // 1. Search
                if (this.filters.search) {
                     const matchName = name.includes(this.filters.search);
                     const matchComment = comment.includes(this.filters.search);
                     if (!matchName && !matchComment) return false;
                }
                
                // 2. Profile
                if (this.filters.profile && profile !== this.filters.profile) return false;
                
                // 3. Comment
                if (this.filters.comment && row.dataset.comment !== this.filters.comment) return false;
                
                return true;
            });

            this.render();
        }

        render() {
            const total = this.filteredRows.length;
            const maxPage = Math.ceil(total / this.itemsPerPage) || 1;
            
            if (this.currentPage > maxPage) this.currentPage = maxPage;
            
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = Math.min(start + this.itemsPerPage, total);
            
            // Update Text (null-check for SPA cross-page TableManager reuse)
            const paginationText = document.getElementById('pagination-text');
            if (window.i18n && paginationText) {
                const text = window.i18n.t('common.table.showing', {
                    start: total === 0 ? 0 : start + 1,
                    end: end,
                    total: total
                });
                paginationText.textContent = text;
            } else if (paginationText) {
                 // Fallback
                 paginationText.innerHTML = `Showing <span class="font-medium text-foreground">${total === 0 ? 0 : start + 1}</span> to <span class="font-medium text-foreground">${end}</span> of <span class="font-medium text-foreground">${total}</span> users`;
            }
            
            // Clear & Append Rows
            if (this.elements.body) {
                this.elements.body.innerHTML = '';
                
                const pageRows = this.filteredRows.slice(start, end);
                pageRows.forEach(row => this.elements.body.appendChild(row));
            }
            
            // Update Buttons
            if (this.elements.prevBtn) this.elements.prevBtn.disabled = this.currentPage === 1;
            if (this.elements.nextBtn) this.elements.nextBtn.disabled = this.currentPage === maxPage || total === 0;

            if (this.elements.pageNumbers) {
                 const pageText = window.i18n ? window.i18n.t('common.page_of', {current: this.currentPage, total: maxPage}) : `Page ${this.currentPage} of ${maxPage}`;
                this.elements.pageNumbers.innerHTML = `<span class="px-3 py-1 text-sm font-medium bg-accents-2 rounded text-accents-6">${pageText}</span>`;
            }

            // Re-init Icons
            if (typeof lucide !== 'undefined') lucide.createIcons();
            
            // Reset "Select All"
            const selectAllEl = document.getElementById('select-all');
            if (selectAllEl) selectAllEl.checked = false;
        }
    };

    // --- Modal Logic ---
    window.openUserModal = function(mode, btn = null) {
        const tplNode = document.getElementById('user-form-template').content.cloneNode(true);
        
        let title = window.i18n ? window.i18n.t('hotspot_users.add_user') : 'Add Voucher';
        let saveBtn = window.i18n ? window.i18n.t('common.save') : 'Save';

        if (mode === 'edit') {
            title = window.i18n ? window.i18n.t('hotspot_users.edit_user') : 'Edit Voucher';
            saveBtn = window.i18n ? window.i18n.t('common.forms.save_changes') : 'Save Changes';
        }

        const preConfirmFn = () => {
             const form = Swal.getHtmlContainer().querySelector('form');
             if(form.reportValidity()) { var _cb = Swal.getConfirmButton(); if (_cb) { _cb.disabled = true; _cb.style.opacity = '.6'; }
                 form.submit();
                 return true;
             }
             return false;
        };

        const onOpenedFn = (popup) => {
             const form = popup.querySelector('form');
             
             if (mode === 'edit' && btn) {
                 const row = btn.closest('tr');
                 
                 form.action = "/<?= htmlspecialchars($session) ?>/hotspot/update";
                 
                 // Populate Hidden ID
                 const idInput = form.querySelector('#form-id');
                 idInput.disabled = false;
                 idInput.value = row.dataset.id; // Ensure data-id is set on TR!

                 // Populate Fields (Assuming data attributes or simple values)
                 // NOTE: For full data (limits, etc), we might need to fetch OR put all in data attributes
                 // Let's rely on data attributes for speed, but need to add them to TR first
                 form.querySelector('[name="name"]').value = row.dataset.rawname || '';
                 form.querySelector('[name="password"]').value = row.dataset.password || '';
                 form.querySelector('[name="comment"]').value = row.dataset.commentRaw || '';
                 
                 // Selects
                 const profileSel = form.querySelector('[name="profile"]');
                 if(profileSel) profileSel.value = row.dataset.profile;
                 
                 const serverSel = form.querySelector('[name="server"]');
                 if(serverSel) serverSel.value = row.dataset.server || 'all';

                 // Limits (Parsing from data attributes)
                 // Time Limit
                 const tLimit = row.dataset.limitUptime || '';
                 // Simple regex parsing for 1d2h3m (Mikrotik format)
                 // This is complex to parse perfectly from string back to split fields without a helper
                 // For now, let's just leave 0 or try best effort if available
                 // Ideally, we pass split values in data attributes from PHP
                 if (row.dataset.timeD) form.querySelector('[name="timelimit_d"]').value = row.dataset.timeD;
                 if (row.dataset.timeH) form.querySelector('[name="timelimit_h"]').value = row.dataset.timeH;
                 if (row.dataset.timeM) form.querySelector('[name="timelimit_m"]').value = row.dataset.timeM;

                 // Data Limit
                 if (row.dataset.limitBytesTotal) {
                     const bytes = parseInt(row.dataset.limitBytesTotal);
                     if (bytes > 0) {
                         if (bytes >= 1073741824) { // GB
                             form.querySelector('[name="datalimit_val"]').value = (bytes / 1073741824).toFixed(0); // integer prefer
                             form.querySelector('[name="datalimit_unit"]').value = 'GB';
                         } else { // MB
                             form.querySelector('[name="datalimit_val"]').value = (bytes / 1048576).toFixed(0);
                             form.querySelector('[name="datalimit_unit"]').value = 'MB';
                         }
                     }
                 }
             }
        };

        tplNode.querySelector('[data-modal-title]').textContent = title;
        const holder = document.createElement('div');
        holder.appendChild(tplNode);
        const template = holder.innerHTML;

        Kaiarasa.modal.form('', template, saveBtn, preConfirmFn, onOpenedFn, 'swal-wide swal-flush');
    }

    window.whenReady(() => {
        // Init Checkboxes & Table methods
        const selectAll = document.getElementById('select-all');
        const toolbar = document.getElementById('batch-toolbar');
        const countSpan = document.getElementById('selected-count');
        const tableBody = document.getElementById('table-body');
        
        // Init Custom Selects on Filter Bar
        if (typeof CustomSelect !== 'undefined') {
            document.querySelectorAll('.custom-select.form-filter').forEach(s => new CustomSelect(s));
        }

        if (window.Kaiarasa && window.Kaiarasa.initFilterChips) {
            window.Kaiarasa.initFilterChips(['filter-profile', 'filter-comment'], 'global-search');
        }

        // Init Table
        const rows = document.querySelectorAll('.table-row-item');
        const manager = new TableManager(rows, 10);
        
        // Toolbar Logic
        function updateToolbar() {
            const checked = document.querySelectorAll('.user-checkbox:checked');
            countSpan.textContent = checked.length;
            if (checked.length > 0) toolbar.classList.remove('translate-y-20', 'opacity-0');
            else toolbar.classList.add('translate-y-20', 'opacity-0');
        }

        if(selectAll) {
            selectAll.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                // Only select visible rows
                const visibleCheckboxes = tableBody.querySelectorAll('.user-checkbox');
                visibleCheckboxes.forEach(cb => cb.checked = isChecked);
                updateToolbar();
            });
        }

        if(tableBody) {
             tableBody.addEventListener('change', (e) => {
                if (e.target.classList.contains('user-checkbox')) {
                    updateToolbar();
                    if (!e.target.checked) selectAll.checked = false;
                }
            });
        }

        // 'Select All Broken' — centang semua voucher orphan (data plan missing)
        window.selectAllBroken = function() {
            const broken = tableBody.querySelectorAll('tr[data-profile-missing="1"]');
            broken.forEach(row => {
                const cb = row.querySelector('.user-checkbox');
                if (cb) cb.checked = true;
            });
            updateToolbar();
        };
    });
    // Actions
    // Use var (not let) to allow re-declaration during SPA navigation
    var selectedTemplate = '<?= htmlspecialchars($defaultTemplate ?? 'default') ?>';
    // Use whenReady (not DOMContentLoaded) so this also runs during SPA
    // navigation where DOMContentLoaded has already fired.
    window.whenReady(() => {
        const sel = document.getElementById('hu-template-select');
        if (sel) selectedTemplate = sel.value;
    });

    window.onTemplateChange = function(val) {
        selectedTemplate = val;
    }

    window.printUser = function(id) {
        const width = 400; const height = 600;
        const left = (window.innerWidth - width) / 2;
        const top = (window.innerHeight - height) / 2;
        const session = '<?= htmlspecialchars($session) ?>';
        let url = `/${session}/hotspot/print/${encodeURIComponent(id)}`;
        if (selectedTemplate !== 'default') {
            url += `?template=${selectedTemplate}`;
        }
        window.open(url, `PrintUser`, `width=${width},height=${height},top=${top},left=${left},scrollbars=yes`);
    }
    
    window.printSelected = function() {
        const selected = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) return Kaiarasa.alert('info', 'No selection', window.i18n ? window.i18n.t('hotspot_users.no_users_selected') : "No users selected.");
        
        const width = 800; const height = 600;
        const left = (window.innerWidth - width) / 2;
        const top = (window.innerHeight - height) / 2;
        const session = '<?= htmlspecialchars($session) ?>';
        const ids = selected.map(id => encodeURIComponent(id)).join(',');
        let url = `/${session}/hotspot/print-batch?ids=${ids}`;
        if (selectedTemplate !== 'default') {
            url += `&template=${selectedTemplate}`;
        }
        window.open(url, `PrintBatch`, `width=${width},height=${height},top=${top},left=${left},scrollbars=yes`);
    }
    
    window.deleteSelected = function() {
        const selected = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
        if (selected.length === 0) return Kaiarasa.alert('info', 'No selection', window.i18n ? window.i18n.t('hotspot_users.no_users_selected') : "Please select at least one user.");
        
        const title = window.i18n ? window.i18n.t('common.delete') : 'Delete Users?';
        const msg = window.i18n ? window.i18n.t('common.confirm_delete') : `Are you sure you want to delete ${selected.length} users?`;
        
        Kaiarasa.confirm(title, msg, window.i18n.t('common.delete'), window.i18n.t('common.cancel')).then(res => {
            if (!res) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/<?= htmlspecialchars($session) ?>/hotspot/delete';
            const sInput = document.createElement('input');
            sInput.type = 'hidden'; sInput.name = 'session'; sInput.value = '<?= htmlspecialchars($session) ?>';
            form.appendChild(sInput);
            const idInput = document.createElement('input');
            idInput.type = 'hidden'; idInput.name = 'id'; idInput.value = selected.join(',');
            form.appendChild(idInput);
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden'; csrfInput.name = '_csrf'; csrfInput.value = window.CSRF_TOKEN;
            form.appendChild(csrfInput);
            document.body.appendChild(form);
            form.submit();
        });
    }
</script>

<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
