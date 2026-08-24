<?php
$title = 'Access Packages';
require_once ROOT.'/app/Views/layouts/header_main.php';

use App\Helpers\LanguageHelper;

// Prepare Filters Data
$uniqueModes = [];
if (! empty($profiles)) {
    foreach ($profiles as $p) {
        $m = $p['meta']['expired_mode_formatted'] ?? '';
        if (! empty($m)) {
            $uniqueModes[$m] = $m;
        }
    }
}
sort($uniqueModes);
?>

<?php
$page_title_key = 'hotspot_profiles.title';
$page_title = 'Access Packages';
$page_desc_key = 'hotspot_profiles.subtitle';
$page_desc = 'Manage hotspot rate limits and pricing for session: ' . htmlspecialchars($session);
$breadcrumbs = [
    ['label' => LanguageHelper::t('common.dashboard', 'Dashboard'), 'href' => "/"],
    ['label' => 'Access Packages', 'href' => null],
];
require_once ROOT.'/app/Views/layouts/page_header.php';
?>

<?php if (isset($error)) { ?>
    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6 flex items-center dark:bg-red-900/20 dark:text-red-400 dark:border dark:border-red-500/20">
        <i data-lucide="alert-circle" class="w-5 h-5 mr-3"></i>
        <?= htmlspecialchars($error) ?>
    </div>
<?php } ?>

<?php
$toolbar_html = '
    <div class="input-group md:w-64 z-10">
        <div class="input-icon">
            <i data-lucide="search" class="h-4 w-4"></i>
        </div>
        <input type="text" id="global-search" class="form-input-search w-full" placeholder="Search profile...">
    </div>
    <div class="page-toolbar-right">
        <div class="w-48">
            <select id="filter-mode" class="custom-select form-filter" data-search="true">
                <option value="" data-i18n="hotspot_profiles.all_modes">All Expired Modes</option>';
foreach ($uniqueModes as $m) {
    $toolbar_html .= '<option value="' . htmlspecialchars($m) . '">' . htmlspecialchars($m) . '</option>';
}
$toolbar_html .= '</select>
        </div>
        <button onclick="openProfileModal(\'add\')" class="btn btn-primary">' .
    '<i data-lucide="plus" class="w-4 h-4 mr-2"></i> <span data-i18n="hotspot_profiles.add_profile">Add Profile</span>' .
    '</button>';
$toolbar_html .= '
    </div>
';
?>
<div class="page-toolbar">
<?php echo $toolbar_html; ?>
</div>

    <div class="table-container">
        <table class="table-glass" id="profiles-table">
            <thead>
                <tr>
                    <th data-sort="name" class="sortable cursor-pointer hover:text-foreground select-none" data-i18n="hotspot_profiles.name">Name</th>
                    <th data-i18n="hotspot_profiles.shared_users">Shared Users</th>
                    <th data-i18n="hotspot_profiles.rate_limit">Rate Limit</th>
                    <th data-i18n="hotspot_profiles.parent_queue">Parent Queue</th>
                    <th data-sort="mode" class="sortable cursor-pointer hover:text-foreground select-none" data-i18n="hotspot_profiles.expired_mode">Expired Mode</th>
                    <th data-i18n="hotspot_profiles.validity">Validity</th>
                    <th data-i18n="hotspot_profiles.lock_user">Lock User</th>
                    <th class="text-right" data-i18n="common.actions">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <?php if (! empty($profiles)) { ?>
                    <?php foreach ($profiles as $profile) { ?>
                    <tr class="table-row-item group-row" 
                        data-id="<?= $profile['.id'] ?>"
                        data-name="<?= htmlspecialchars($profile['name'] ?? '') ?>"
                        data-shared-users="<?= htmlspecialchars($profile['shared-users'] ?? '1') ?>"
                        data-rate-limit="<?= htmlspecialchars($profile['rate-limit'] ?? '') ?>"
                        data-address-pool="<?= htmlspecialchars($profile['address-pool'] ?? 'none') ?>"
                        data-parent-queue="<?= htmlspecialchars($profile['parent-queue'] ?? 'none') ?>"
                        data-expired-mode="<?= htmlspecialchars($profile['meta']['expired_mode'] ?? 'none') ?>"
                        data-val-d="<?= htmlspecialchars($profile['val_d'] ?? '') ?>"
                        data-val-h="<?= htmlspecialchars($profile['val_h'] ?? '') ?>"
                        data-val-m="<?= htmlspecialchars($profile['val_m'] ?? '') ?>"
                        data-lock-user="<?= htmlspecialchars($profile['meta']['lock_user'] ?? 'Disable') ?>"
                        data-search-name="<?= strtolower($profile['name'] ?? '') ?>"
                        data-mode="<?= htmlspecialchars($profile['meta']['expired_mode_formatted'] ?? '') ?>">
                        
                        <td>
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center text-xs font-bold mr-3">
                                    <i data-lucide="ticket" class="w-4 h-4"></i>
                                </div>
                                <div class="text-sm font-medium text-foreground">
                                    <button onclick="openProfileModal('edit', this)" class="hover:underline hover:text-purple-600 dark:hover:text-purple-400 text-left">
                                        <?= htmlspecialchars($profile['name'] ?? '-') ?>
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-sm font-semibold"><?= htmlspecialchars($profile['shared-users'] ?? '1') ?></span>
                            <span class="text-xs text-accents-5">dev</span>
                        </td>
                         <td>
                            <?php if (! empty($profile['rate-limit'])) { ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                <?= htmlspecialchars($profile['rate-limit']) ?>
                            </span>
                            <?php } else { ?>
                                <span class="text-xs text-accents-4">-</span>
                            <?php } ?>
                        </td>
                        <td class="text-sm text-accents-6">
                           <?= htmlspecialchars($profile['parent-queue'] ?? '-') ?>
                        </td>
                        <td class="text-sm text-accents-6">
                           <?= htmlspecialchars($profile['meta']['expired_mode_formatted'] ?? '') ?>
                        </td>
                        <td class="text-sm text-accents-6">
                           <?= htmlspecialchars($profile['meta']['validity'] ?? '') ?>
                        </td>
                         <td class="text-sm text-accents-6">
                           <?= htmlspecialchars($profile['meta']['lock_user'] ?? '') ?>
                        </td>
                        
                        <td class="text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2 table-actions-reveal">
                                <button onclick="openProfileModal('edit', this)" class="btn bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 border-transparent h-8 px-2 rounded transition-colors" title="Edit">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </button>
                                <form action="/<?= htmlspecialchars($session) ?>/hotspot/profile/delete" method="POST" onsubmit="event.preventDefault(); Kaiarasa.confirm(window.i18n ? window.i18n.t('hotspot_profiles.title') : 'Delete Profile?', window.i18n ? window.i18n.t('common.confirm_delete') : 'Are you sure you want to delete profile <?= $profile['name'] ?>?', window.i18n ? window.i18n.t('common.delete') : 'Delete', window.i18n ? window.i18n.t('common.cancel') : 'Cancel').then(res => { if(res) this.submit(); });" class="inline">
                                    <input type="hidden" name="session" value="<?= htmlspecialchars($session) ?>">
                                    <input type="hidden" name="id" value="<?= $profile['.id'] ?>">
                                    <button type="submit" class="btn bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-900/20 dark:hover:bg-red-900/40 border-transparent h-8 px-2 rounded transition-colors" title="Delete">
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
                Showing <span id="start-idx" class="font-medium text-foreground">0</span> to <span id="end-idx" class="font-medium text-foreground">0</span> of <span id="total-count" class="font-medium text-foreground">0</span> profiles
            </div>
            <div class="flex gap-2">
                <button id="prev-btn" class="btn btn-sm btn-secondary" disabled data-i18n="common.previous">Previous</button>
                <div id="page-numbers" class="flex gap-1"></div>
                <button id="next-btn" class="btn btn-sm btn-secondary" disabled data-i18n="common.next">Next</button>
            </div>
        </div>
    </div>

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
                mode: ''
            };

            this.init();
        }

        init() {
            // Translate placeholder
            const searchInput = document.getElementById('global-search');
            if (searchInput && window.i18n) {
                searchInput.placeholder = window.i18n.t('common.table.search_placeholder');
            }
            this.setupListeners();
            this.update();

            // Listen for language change
            window.addEventListener('languageChanged', () => {
                const searchInput = document.getElementById('global-search');
                if (searchInput && window.i18n) {
                    searchInput.placeholder = window.i18n.t('common.table.search_placeholder');
                }
                this.render();
            });
        }

        setupListeners() {
            const searchInput = document.getElementById('global-search');
            if (searchInput) searchInput.addEventListener('input', (e) => {
                this.filters.search = e.target.value.toLowerCase();
                this.currentPage = 1;
                this.update();
            });

            if (this.elements.prevBtn) this.elements.prevBtn.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.render();
                }
            });

            if (this.elements.nextBtn) this.elements.nextBtn.addEventListener('click', () => {
                const maxPage = Math.ceil(this.filteredRows.length / this.itemsPerPage);
                if (this.currentPage < maxPage) {
                    this.currentPage++;
                    this.render();
                }
            });
            
            const filterMode = document.getElementById('filter-mode');
            if (filterMode) filterMode.addEventListener('change', (e) => {
                this.filters.mode = e.target.value;
                this.currentPage = 1;
                this.update();
            });
        }

        update() {
            this.filteredRows = this.allRows.filter(row => {
                const name = row.dataset.searchName || '';
                const mode = row.dataset.mode || '';
                
                if (this.filters.search && !name.includes(this.filters.search)) return false;
                if (this.filters.mode && mode !== this.filters.mode) return false;
                
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
            
            this.elements.startIdx.textContent = total === 0 ? 0 : start + 1;
            this.elements.endIdx.textContent = end;
            this.elements.totalCount.textContent = total;
            
             // Update Text (Use Translation)
            if (window.i18n && document.getElementById('pagination-controls')) {
                 const text = window.i18n.t('common.table.showing', {
                    start: total === 0 ? 0 : start + 1,
                    end: end,
                    total: total
                });
                // Find and update the text node if possible
                const container = document.getElementById('pagination-controls').querySelector('.text-accents-5');
                 if(container) {
                      container.innerHTML = text.replace('{start}', `<span class="font-medium text-foreground">${total === 0 ? 0 : start + 1}</span>`)
                                                .replace('{end}', `<span class="font-medium text-foreground">${end}</span>`)
                                                .replace('{total}', `<span class="font-medium text-foreground">${total}</span>`);
                 }
            }
            
            this.elements.body.innerHTML = '';
            
            const pageRows = this.filteredRows.slice(start, end);
            pageRows.forEach(row => this.elements.body.appendChild(row));
            
            this.elements.prevBtn.disabled = this.currentPage === 1;
            this.elements.nextBtn.disabled = this.currentPage === maxPage || total === 0;

            if (this.elements.pageNumbers) {
                const pageText = window.i18n ? window.i18n.t('common.page_of', {current: this.currentPage, total: maxPage}) : `Page ${this.currentPage} of ${maxPage}`;
                this.elements.pageNumbers.innerHTML = `<span class="px-3 py-1 text-sm font-medium bg-accents-2 rounded text-accents-6">${pageText}</span>`;
            }

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    };

    // IMPORTANT: Define global onclick handlers (openProfileModal) BEFORE the
    // whenReady() call below. During SPA navigation document.readyState is
    // already 'complete', so whenReady() runs its callback synchronously.
    // If openProfileModal were defined after whenReady(), it would not yet
    // be assigned to window when the user clicks Add -> ReferenceError.
    window.openProfileModal = function(mode, btn = null) {
        const tplNode = document.getElementById('profile-form-template').content.cloneNode(true);
        
        let title = window.i18n ? window.i18n.t('hotspot_profiles.form.add_title') : 'Add Profile';
        let saveBtn = window.i18n ? window.i18n.t('common.save') : 'Save';
        
        if (mode === 'edit') {
            title = window.i18n ? window.i18n.t('hotspot_profiles.form.edit_title') : 'Edit Profile';
            saveBtn = window.i18n ? window.i18n.t('common.forms.save_changes') : 'Save Changes';
        }

        const preConfirmFn = () => {
             const form = Swal.getHtmlContainer().querySelector('form');

             // Compose MikroTik rate-limit "rx/tx" dari field terpisah (mirror jika satu sisi kosong)
             const rxV = form.querySelector('[name="rate-limit-rx"]');
             const txV = form.querySelector('[name="rate-limit-tx"]');
             const rxU = form.querySelector('[name="rate-limit-rx-unit"]');
             const txU = form.querySelector('[name="rate-limit-tx-unit"]');
             const rl  = form.querySelector('[name="rate-limit"]');
             if (rl && rxV && txV) {
                 const hasRx = rxV.value !== '', hasTx = txV.value !== '';
                 if (hasRx || hasTx) {
                     const rv = hasRx ? rxV.value : txV.value;
                     const tv = hasTx ? txV.value : rxV.value;
                     rl.value = rv + rxU.value + '/' + tv + txU.value;
                 } else {
                     rl.value = '';
                 }
             }

             if(form.reportValidity()) {
                 form.submit();
                 return true;
             }
             return false;
        };

        const onOpenedFn = (popup) => {
             const form = popup.querySelector('form');
             
             // Validity Toggle Logic for Modal
             const modeSelect = form.querySelector('#expired-mode');
             const validityGroup = form.querySelector('#validity-group');

             function toggleValidity() {
                 if (!modeSelect || !validityGroup) return;
                 if (modeSelect.value === 'none') {
                     validityGroup.classList.add('hidden');
                 } else {
                     validityGroup.classList.remove('hidden');
                 }
             }

             if (modeSelect) {
                 modeSelect.addEventListener('change', toggleValidity);
             }

             if (mode === 'edit' && btn) {
                 const row = btn.closest('tr');
                 
                 form.action = "/<?= htmlspecialchars($session) ?>/hotspot/profile/update";
                 
                 // Populate Hidden ID
                 const idInput = form.querySelector('#form-id');
                 idInput.disabled = false;
                 idInput.value = row.dataset.id;

                 // Populate Fields
                 form.querySelector('[name="name"]').value = row.dataset.name || '';
                 form.querySelector('[name="shared-users"]').value = row.dataset.sharedUsers || '1';
                                  const rlCombined = row.dataset.rateLimit || '';
                 if (form.querySelector('[name="rate-limit"]')) form.querySelector('[name="rate-limit"]').value = rlCombined;
                 const rlParts = rlCombined.split('/');
                 const parseRl = (s) => { const m = /^\s*(\d+(?:\.\d+)?)\s*([kKmMgG]?)\s*$/.exec(s || ''); return m ? [m[1], (m[2] || 'M')] : null; };
                 if (rlParts.length === 2) {
                     const rxP = parseRl(rlParts[0]), txP = parseRl(rlParts[1]);
                     if (rxP) { form.querySelector('[name="rate-limit-rx"]').value = rxP[0]; form.querySelector('[name="rate-limit-rx-unit"]').value = rxP[1]; }
                     if (txP) { form.querySelector('[name="rate-limit-tx"]').value = txP[0]; form.querySelector('[name="rate-limit-tx-unit"]').value = txP[1]; }
                 }
                 
                 // Selects
                 if(form.querySelector('[name="address-pool"]')) form.querySelector('[name="address-pool"]').value = row.dataset.addressPool;
                 if(form.querySelector('[name="parent-queue"]')) form.querySelector('[name="parent-queue"]').value = row.dataset.parentQueue;
                 if(form.querySelector('[name="expired_mode"]')) form.querySelector('[name="expired_mode"]').value = row.dataset.expiredMode;
                 if(form.querySelector('[name="lock_user"]')) form.querySelector('[name="lock_user"]').value = row.dataset.lockUser;

                 // Validity
                 form.querySelector('[name="validity_d"]').value = row.dataset.valD || '';
                 form.querySelector('[name="validity_h"]').value = row.dataset.valH || '';
                 form.querySelector('[name="validity_m"]').value = row.dataset.valM || '';

                 // Prices

                 // Initial Toggle Check
                 toggleValidity();
             }
        };

        tplNode.querySelector('[data-modal-title]').textContent = title;
        const holder = document.createElement('div');
        holder.appendChild(tplNode);
        const template = holder.innerHTML;

        Kaiarasa.modal.form('', template, saveBtn, preConfirmFn, onOpenedFn, 'swal-wide swal-flush');
    }

    // DOM-dependent initialization (CustomSelect + TableManager).
    // Kept AFTER global handler definitions so onclick handlers are always
    // available, even when whenReady() runs synchronously during SPA navigation.
    window.whenReady(() => {
        if (typeof CustomSelect !== 'undefined') {
            document.querySelectorAll('.custom-select').forEach(select => {
                new CustomSelect(select);
            });
        }
        
        const rows = document.querySelectorAll('.table-row-item');
        new TableManager(rows, 10);
    });
</script>

<template id="profile-form-template">
    <!-- Modal Head (Sage Tint — pattern Generate Vouchers) -->
    <div class="bg-[#5f7f67]/[.07] dark:bg-[#5f7f67]/[.12] border-b border-[#5f7f67]/20 px-6 py-4 flex items-start justify-between gap-4 text-left">
        <div>
            <h3 class="text-base font-bold tracking-tight" data-modal-title>Add Profile</h3>
            <p class="text-xs opacity-60 mt-1" data-i18n="common.profile_form_subtitle">Speed limits, expiry &amp; pricing</p>
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
            <form id="profile-form" action="/<?= htmlspecialchars($session) ?>/hotspot/profile/store" method="POST" class="space-y-4">
                <input type="hidden" name="session" value="<?= htmlspecialchars($session) ?>">
                <input type="hidden" name="id" id="form-id" disabled>

                                <div class="flex items-center gap-3 pt-2">
                    <span class="text-[13px] font-bold uppercase tracking-[0.14em]" data-i18n="common.general">General</span>
                    <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
                </div>

                <div>
                    <label class="block text-[13px] font-semibold mb-2" data-i18n="common.name">Name</label>
                    <input type="text" name="name" required class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition" data-i18n-placeholder="hotspot_profiles.form.name_placeholder" placeholder="e.g. 1Hour-Package">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_profiles.form.address_pool">Address pool</label>
                        <div class="relative">
                            <select name="address-pool" data-native class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                                <option value="none" data-i18n="common.forms.none">none</option>
                                <?php foreach ($pools as $pool) { ?>
                                    <?php if (isset($pool['name'])) { ?>
                                    <option value="<?= htmlspecialchars($pool['name']) ?>"><?= htmlspecialchars($pool['name']) ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_profiles.form.shared_users">Shared users</label>
                        <input type="number" name="shared-users" value="1" min="1" placeholder="1" class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    </div>
                </div>

                                <div class="flex items-center gap-3 pt-2">
                    <span class="text-[13px] font-bold uppercase tracking-[0.14em]" data-i18n="common.bandwidth">Bandwidth</span>
                    <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="common.upload_rx">Upload (Rx)</label>
                        <div class="flex gap-2">
                            <input type="number" step="any" min="0" name="rate-limit-rx" placeholder="e.g. 512"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                            <div class="relative w-24 shrink-0">
                                <select name="rate-limit-rx-unit" data-native aria-label="Upload unit"
                                    class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-8 text-[14px] text-center appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                                    <option value="k">Kbps</option>
                                    <option value="M" selected>Mbps</option>
                                    <option value="G">Gbps</option>
                                </select>
                                <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="common.download_tx">Download (Tx)</label>
                        <div class="flex gap-2">
                            <input type="number" step="any" min="0" name="rate-limit-tx" placeholder="e.g. 1024"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                            <div class="relative w-24 shrink-0">
                                <select name="rate-limit-tx-unit" data-native aria-label="Download unit"
                                    class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-8 text-[14px] text-center appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                                    <option value="k">Kbps</option>
                                    <option value="M" selected>Mbps</option>
                                    <option value="G">Gbps</option>
                                </select>
                                <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_profiles.form.parent_queue">Parent queue</label>
                        <div class="relative">
                            <select name="parent-queue" data-native class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                                <option value="none" data-i18n="common.forms.none">none</option>
                                <?php foreach ($queues as $q) { ?>
                                    <option value="<?= htmlspecialchars($q) ?>"><?= htmlspecialchars($q) ?></option>
                                <?php } ?>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <input type="hidden" name="rate-limit" id="rate-limit-combined">

                <div class="flex items-center gap-3 pt-2">
                    <span class="text-[13px] font-bold uppercase tracking-[0.14em]" data-i18n="common.expiry_pricing">Expiry & Pricing</span>
                    <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_profiles.form.lock_user">Lock user</label>
                        <div class="relative">
                            <select name="lock_user" data-native class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                                <option value="Disable" data-i18n="common.forms.disabled">Disable</option>
                                <option value="Enable" data-i18n="common.forms.enabled">Enable</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_profiles.form.expired_mode">Expired mode</label>
                        <div class="relative">
                            <select name="expired_mode" id="expired-mode" data-native class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                                <option value="none" data-i18n="common.forms.none" selected>none</option>
                                <option value="rem">Remove</option>
                                <option value="ntf">Notice</option>
                                <option value="remc">Remove &amp; Record</option>
                                <option value="ntfc">Notice &amp; Record</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <div id="validity-group" class="hidden space-y-2 transition-all">
                    <span class="block text-[13px] font-semibold mb-2" data-i18n="hotspot_profiles.form.validity">Validity</span>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="relative">
                            <input type="number" name="validity_d" min="0" placeholder="0D" aria-label="Validity days"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 pr-6 text-[14px] text-center outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        </div>
                        <div class="relative">
                            <input type="number" name="validity_h" min="0" placeholder="0H" aria-label="Validity hours"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] text-center outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        </div>
                        <div class="relative">
                            <input type="number" name="validity_m" min="0" placeholder="0M" aria-label="Validity minutes"
                                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] text-center outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <!-- Quick Tips -->
        <aside class="self-start rounded-2xl border border-dashed border-black/[.08] dark:border-white/[.08] bg-white/50 dark:bg-white/[.03] p-5">
            <h3 class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wider mb-3">
                <i data-lucide="lightbulb" class="w-3.5 h-3.5 text-yellow-500"></i>
                <span data-i18n="hotspot_profiles.form.quick_tips">Quick Tips</span>
            </h3>
            <ul class="space-y-1.5 text-xs leading-relaxed list-none p-0 m-0">
                <li class="flex gap-2">
                    <span class="w-1 h-1 rounded-full bg-[#5f7f67] mt-1.5 flex-shrink-0"></span>
                    <span data-i18n="hotspot_profiles.form.tip_rate_limit"><strong>Rate Limit</strong>: Rx/Tx (Upload/Download). Example: <code>512k/1M</code></span>
                </li>
                <li class="flex gap-2">
                    <span class="w-1 h-1 rounded-full bg-[#5f7f67] mt-1.5 flex-shrink-0"></span>
                    <span data-i18n="hotspot_profiles.form.tip_expired_mode"><strong>Expired Mode</strong>: Select 'Remove' or 'Notice' to enable Validity.</span>
                </li>
                <li class="flex gap-2">
                    <span class="w-1 h-1 rounded-full bg-[#5f7f67] mt-1.5 flex-shrink-0"></span>
                    <span data-i18n="hotspot_profiles.form.tip_parent_queue"><strong>Parent Queue</strong>: Assigns users to a specific parent queue for bandwidth management.</span>
                </li>
            </ul>
        </aside>
        </div>
    </div>
</template>

<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
