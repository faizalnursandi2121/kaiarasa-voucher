<?php
// Quick Print Management (List & CRUD)
$title = 'Manage Quick Print';
require_once ROOT.'/app/Views/layouts/header_main.php';
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-foreground" data-i18n="quick_print.manage_title">Manage Packages</h1>
            <p class="text-accents-5"><span data-i18n="quick_print.manage_subtitle">Configure your Quick Print voucher packages for:</span> <span class="text-foreground font-medium"><?= htmlspecialchars($session) ?></span></p>
        </div>
        <div class="flex items-center gap-2">
             <a href="/<?= htmlspecialchars($session) ?>/quick-print" class="btn btn-secondary">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2 inline-block"></i> <span data-i18n="common.back">Back</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="page-toolbar">
        <!-- Search -->
        <div class="input-group md:w-64 z-10">
            <div class="input-icon">
                <i data-lucide="search" class="h-4 w-4"></i>
            </div>
            <input type="text" id="global-search" class="form-input-search w-full" placeholder="Search package name..." data-i18n-placeholder="common.table.search_placeholder">
        </div>
        <div class="page-toolbar-right">
            <button onclick="openModal('add')" class="btn btn-primary">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i>
                <span data-i18n="quick_print.add_package">Add Package</span>
            </button>
        </div>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="table-glass" id="packages-table">
            <thead>
                <tr>
                    <th data-sort="name" class="sortable cursor-pointer hover:text-foreground select-none" data-i18n="quick_print.name">Name</th>
                    <th data-i18n="quick_print.data_plan">Data Plans</th>
                    <th data-i18n="quick_print.prefix">Prefix</th>
                    <th data-sort="price" class="sortable cursor-pointer hover:text-foreground select-none" data-i18n="quick_print.price">Price</th>
                    <th class="text-right" data-i18n="common.actions">Actions</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <?php if (empty($packages)) { ?>
                <tr>
                    <td colspan="6" class="p-8 text-center text-accents-5" data-i18n="quick_print.no_packages_found">No packages found.</td>
                </tr>
                <?php } else { ?>
                    <?php foreach ($packages as $pkg) { ?>
                    <tr class="table-row-item group"
                        data-id="<?= htmlspecialchars($pkg['id']) ?>"
                        data-name="<?= htmlspecialchars($pkg['name']) ?>"
                        data-profile="<?= htmlspecialchars($pkg['profile']) ?>"
                        data-prefix="<?= htmlspecialchars($pkg['prefix']) ?>"
                        data-price="<?= htmlspecialchars($pkg['price']) ?>"
                                                                                                data-char-length="<?= htmlspecialchars($pkg['char_length']) ?>"
                        data-color="<?= htmlspecialchars($pkg['color']) ?>"
                        data-comment="<?= htmlspecialchars($pkg['comment']) ?>">
                        
                        <td class="font-medium text-foreground">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full <?= htmlspecialchars($pkg['color']) ?>"></div>
                                <?= htmlspecialchars($pkg['name']) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($pkg['profile']) ?></td>
                        <td class="font-mono text-xs"><?= htmlspecialchars($pkg['prefix']) ?: '-' ?></td>
                        <td><?= htmlspecialchars($pkg['price'] > 0 ? number_format($pkg['price'], 0, ',', '.') : 'Free') ?></td>
                        <td class="text-right text-sm">
                            <div class="flex items-center justify-end gap-2 table-actions-reveal">
                                <!-- Simple Delete Form -->
                                <form action="/<?= htmlspecialchars($session) ?>/quick-print/delete" method="POST" onsubmit="event.preventDefault(); Kaiarasa.confirm(window.i18n ? window.i18n.t('quick_print.delete_package') : 'Delete Package?', window.i18n ? window.i18n.t('common.confirm_delete') : 'Are you sure you want to delete this Quick Print package?', window.i18n ? window.i18n.t('common.delete') : 'Delete', window.i18n ? window.i18n.t('common.cancel') : 'Cancel').then(res => { if(res) this.submit(); });">
                                    <input type="hidden" name="session" value="<?= htmlspecialchars($session) ?>">
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($pkg['id']) ?>">
                                    <button type="submit" class="btn-icon-danger" title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                 <button type="button" onclick="openModal('edit', this)" class="btn-icon" title="Edit">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
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
                Showing <span id="start-idx" class="font-medium text-foreground">0</span> to <span id="end-idx" class="font-medium text-foreground">0</span> of <span id="total-count" class="font-medium text-foreground">0</span> packages
            </div>
            <div class="flex gap-2">
                <button id="prev-btn" class="btn btn-sm btn-secondary" disabled data-i18n="common.previous">Previous</button>
                <div id="page-numbers" class="flex gap-1"></div>
                <button id="next-btn" class="btn btn-sm btn-secondary" disabled data-i18n="common.next">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Template for Add/Edit Package Form -->
<template id="package-form-template">
    <!-- Modal Head (Sage Tint — pattern Generate Vouchers) -->
    <div class="bg-[#5f7f67]/[.07] dark:bg-[#5f7f67]/[.12] border-b border-[#5f7f67]/20 px-6 py-4 flex items-start justify-between gap-4 text-left">
        <div>
            <h3 class="text-base font-bold tracking-tight" data-modal-title>Add Package</h3>
            <p class="text-xs opacity-60 mt-1" data-i18n="common.package_form_subtitle">Quick print card settings</p>
        </div>
        <button type="button" onclick="Swal.close()" aria-label="Close"
            class="w-8 h-8 rounded-lg inline-flex items-center justify-center hover:bg-black/[.05] dark:hover:bg-white/[.06] transition-colors shrink-0">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <form id="qp-form" action="/<?= htmlspecialchars($session) ?>/quick-print/store" method="POST" class="space-y-4 p-6 text-left">
        <input type="hidden" name="session" value="<?= htmlspecialchars($session) ?>">
        <input type="hidden" name="id" id="form-id" disabled>

                        <div class="flex items-center gap-3 pt-2">
                    <span class="text-[13px] font-bold uppercase tracking-[0.14em]" data-i18n="common.package">Package</span>
                    <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
                </div>

        <div>
            <label class="block text-[13px] font-semibold mb-2" data-i18n="common.name">Name</label>
            <input type="text" name="name" required placeholder="e.g. 3 Hours Voucher"
                class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-[13px] font-semibold mb-2" data-i18n="quick_print.select_profile">Select data plan</label>
                <div class="relative">
                    <select name="profile" data-native class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <?php foreach ($profiles as $p) { ?>
                            <option value="<?= htmlspecialchars($p['name']) ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php } ?>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                </div>
            </div>
            <div>
                <label class="block text-[13px] font-semibold mb-2" data-i18n="quick_print.card_color">Card color</label>
                <div class="relative">
                    <select name="color" data-native class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <option value="bg-blue-500" data-i18n="colors.blue">Blue</option>
                        <option value="bg-red-500" data-i18n="colors.red">Red</option>
                        <option value="bg-green-500" data-i18n="colors.green">Green</option>
                        <option value="bg-yellow-500" data-i18n="colors.yellow">Yellow</option>
                        <option value="bg-purple-500" data-i18n="colors.purple">Purple</option>
                        <option value="bg-pink-500" data-i18n="colors.pink">Pink</option>
                        <option value="bg-indigo-500" data-i18n="colors.indigo">Indigo</option>
                        <option value="bg-gray-800" data-i18n="colors.dark">Dark</option>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                </div>
            </div>
        </div>

                        <div class="flex items-center gap-3 pt-2">
                    <span class="text-[13px] font-bold uppercase tracking-[0.14em]" data-i18n="common.pricing">Pricing</span>
                    <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
                </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-[13px] font-semibold mb-2" data-i18n="quick_print.price">Price (Rp)</label>
                <input type="number" name="price" placeholder="5000" class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
            </div>

        </div>

                        <div class="flex items-center gap-3 pt-2">
                    <span class="text-[13px] font-bold uppercase tracking-[0.14em]" data-i18n="common.voucher_format">Voucher Format</span>
                    <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
                </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-[13px] font-semibold mb-2" data-i18n="quick_print.prefix">Prefix</label>
                <input type="text" name="prefix" placeholder="Example: VIP-" class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
            </div>
            <div>
                <label class="block text-[13px] font-semibold mb-2" data-i18n="quick_print.char_length">Char length</label>
                <div class="relative">
                    <select name="char_length" data-native class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3.5 pr-9 text-[14px] appearance-none outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <option value="4" selected data-i18n="common.char_length" data-i18n-params='{"n": 4}'>4 Characters</option>
                        <option value="6" data-i18n="common.char_length" data-i18n-params='{"n": 6}'>6 Characters</option>
                        <option value="8" data-i18n="common.char_length" data-i18n-params='{"n": 8}'>8 Characters</option>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                </div>
            </div>
        </div>
<div>
            <label class="block text-[13px] font-semibold mb-2" data-i18n="system_tools.comment">Comment</label>
            <input type="text" name="comment" placeholder="Description or Note" class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
        </div>
    </form>
</template>

<script>
    var TableManager = class TableManager {
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

            this.filters = { search: '' };
            this.init();
        }

        init() {
            // Translate placeholder
            const searchInput = document.getElementById('global-search');
            if (searchInput && window.i18n) {
                searchInput.placeholder = window.i18n.t('common.table.search_placeholder');
            }
            document.getElementById('global-search').addEventListener('input', (e) => {
                this.filters.search = e.target.value.toLowerCase();
                this.currentPage = 1;
                this.update();
            });
            
            this.elements.prevBtn.addEventListener('click', () => { if(this.currentPage > 1) { this.currentPage--; this.render(); } });
            this.elements.nextBtn.addEventListener('click', () => { 
                const max = Math.ceil(this.filteredRows.length / this.itemsPerPage);
                if(this.currentPage < max) { this.currentPage++; this.render(); } 
            });

            this.update();

            // Listen for language change
            window.kaiarasaOnLangChange( () => {
                const searchInput = document.getElementById('global-search');
                if (searchInput && window.i18n) {
                    searchInput.placeholder = window.i18n.t('common.table.search_placeholder');
                }
                this.render();
            });
        }

        update() {
            this.filteredRows = this.allRows.filter(row => {
                const name = row.dataset.name || '';
                
                if (this.filters.search && !name.includes(this.filters.search)) return false;
                
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
            this.filteredRows.slice(start, end).forEach(row => this.elements.body.appendChild(row));
            
            this.elements.prevBtn.disabled = this.currentPage === 1;
            this.elements.nextBtn.disabled = this.currentPage === maxPage || total === 0;

            if (this.elements.pageNumbers) {
                 const pageText = window.i18n ? window.i18n.t('common.page_of', {current: this.currentPage, total: maxPage}) : `Page ${this.currentPage} of ${maxPage}`;
                this.elements.pageNumbers.innerHTML = `<span class="px-3 py-1 text-sm font-medium bg-accents-2 rounded text-accents-6">${pageText}</span>`;
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    };

    function openModal(mode, btn = null) {
        const tplNode = document.getElementById('package-form-template').content.cloneNode(true);

        let title = window.i18n ? window.i18n.t('quick_print.add_package') : 'Add Package';
        let saveBtn = window.i18n ? window.i18n.t('quick_print.save_package') : 'Save Package';
        
        // Validation Callback
        const preConfirmFn = () => {
             const form = Swal.getHtmlContainer().querySelector('form');
             if(form.reportValidity()) { var _cb = Swal.getConfirmButton(); if (_cb) { _cb.disabled = true; _cb.style.opacity = '.6'; }
                 form.submit();
             } else {
                 return false;
             }
        };

        // Population Callback (Runs BEFORE CustomSelect init)
        const onOpenedFn = (popup) => {
             if (mode === 'edit' && btn) {
                 const row = btn.closest('tr');
                 const form = popup.querySelector('form');
                 
                 // Update Route Logic Here if needed, or rely on Hidden ID
                 // For now backend handles update if ID is present
                 form.action = "/<?= htmlspecialchars($session) ?>/quick-print/update"; 

                 // Populate inputs
                 form.querySelector('[name="id"]').value = row.dataset.id;
                 form.querySelector('[name="id"]').disabled = false; 
                 
                 form.querySelector('[name="name"]').value = row.dataset.name;
                 form.querySelector('[name="price"]').value = row.dataset.price;
                 form.querySelector('[name="prefix"]').value = row.dataset.prefix;

                 form.querySelector('[name="comment"]').value = row.dataset.comment;
                 
                 // Selects (Just setting value works because CustomSelect hasn't init yet!)
                 const profileSel = form.querySelector('[name="profile"]');
                 if(profileSel) profileSel.value = row.dataset.profile;
                 
                 const colorSel = form.querySelector('[name="color"]');
                 if(colorSel) colorSel.value = row.dataset.color;
                 
                 const charSel = form.querySelector('[name="char_length"]');
                 if(charSel) charSel.value = row.dataset.charLength;
             }
        };

        if (mode === 'edit' && btn) {
            title = window.i18n ? 'Edit Package' : 'Edit Package';
            saveBtn = window.i18n ? 'Update Package' : 'Update Package';
        }
        
        // Pass callbacks to helper
        tplNode.querySelector('[data-modal-title]').textContent = title;
        const holder = document.createElement('div');
        holder.appendChild(tplNode);
        const template = holder.innerHTML;

        Kaiarasa.modal.form('', template, saveBtn, preConfirmFn, onOpenedFn, 'swal-flush');
    }
    
    window.whenReady(() => {
        new TableManager(document.querySelectorAll('.table-row-item'), 10);
    });
</script>

<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
