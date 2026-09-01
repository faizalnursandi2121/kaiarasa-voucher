/**
 * Kaiarasa Component: Select
 * Standardized Custom Select for Forms, Filters, and Navigation.
 *
 * Portal Pattern: saat open(), menu di-append ke document.body dengan
 * position:fixed. Ini menghindari clipping oleh ancestor overflow
 * (scroll container <main>, .table-container overflow-x-auto, dll).
 * Posisi dihitung dari getBoundingClientRect() trigger.
 */
class CustomSelect {
    static instances = [];

    static get(elementOrId) {
        if (typeof elementOrId === 'string') {
            return CustomSelect.instances.find(i => i.originalSelect.id === elementOrId);
        }
        return CustomSelect.instances.find(i => i.originalSelect === elementOrId);
    }

    constructor(selectElement) {
        if (selectElement.dataset.customSelectInitialized === 'true') return;
        selectElement.dataset.customSelectInitialized = 'true';

        this.originalSelect = selectElement;
        this.originalSelect.style.display = 'none';
        this.options = Array.from(this.originalSelect.options);
        
        // Determine Variant
        this.variant = this.originalSelect.dataset.variant || 'default';
        if (this.originalSelect.classList.contains('form-filter')) this.variant = 'filter';
        if (this.originalSelect.classList.contains('nav-select')) this.variant = 'nav';

        this.wrapper = document.createElement('div');
        this.buildWrapperClasses();
        
        this.init();
        CustomSelect.instances.push(this);
    }

    buildWrapperClasses() {
        let base = 'custom-select-wrapper relative active-select';
        
        // Copy width classes
        const widthClass = Array.from(this.originalSelect.classList).find(c => c.startsWith('w-') && c !== 'w-full');
        const isFullWidth = this.originalSelect.classList.contains('w-full') || 
                           this.originalSelect.classList.contains('form-control') || 
                           this.originalSelect.classList.contains('form-input');

        if (widthClass) base += ' ' + widthClass;
        else if (isFullWidth) base += ' w-full';
        else base += ' w-fit';
        
        this.wrapper.className = base;
    }

    init() {
        this.trigger = document.createElement('div');
        
        // Variant Styling
        let triggerClass = 'flex items-center justify-between cursor-pointer pr-3 transition-all duration-200';
        
        if (this.variant === 'filter') {
            triggerClass += ' form-filter'; 
        } else if (this.variant === 'nav') {
            // New Nav variant for transparent/header usage
            triggerClass += ' text-sm font-medium hover:bg-accents-2/50 rounded-lg px-2 py-1.5 border border-transparent hover:border-accents-2';
        } else {
            triggerClass += ' form-input';
        }

        // Inherit non-structural classes
        const inherited = Array.from(this.originalSelect.classList)
            .filter(c => !['custom-select', 'hidden', 'form-filter', 'form-input', 'w-full'].includes(c))
            .join(' ');
        if (inherited) triggerClass += ' ' + inherited;

        this.trigger.className = triggerClass;
        this.trigger.setAttribute('role', 'combobox');
        this.trigger.setAttribute('aria-expanded', 'false');
        this.trigger.setAttribute('tabindex', '0');
        this.renderTrigger();
        
        // Dropdown Menu — created but NOT inserted into wrapper.
        // Di-portal ke document.body saat open() untuk menghindari clipping.
        this.menu = document.createElement('div');
        this.menu.className = 'custom-select-dropdown dropdown-bridge';
        this.menu.setAttribute('role', 'listbox');
        
        this.listContainer = document.createElement('div');
        this.listContainer.className = 'overflow-y-auto flex-1 py-1 custom-scrollbar';

        if (this.originalSelect.dataset.search === 'true') {
            this.buildSearch();
        }

        this.buildOptions();

        this.menu.appendChild(this.listContainer);
        this.wrapper.appendChild(this.trigger);
        // NOTE: menu tidak di-append ke wrapper — di-portal ke body saat open()
        this.originalSelect.parentNode.insertBefore(this.wrapper, this.originalSelect);

        this.bindEvents();
        
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: this.wrapper });
    }

    renderTrigger() {
        const option = this.originalSelect.options[this.originalSelect.selectedIndex];
        const text = option ? option.text : '';
        const icon = option?.dataset.icon;
        const image = option?.dataset.image;
        const flag = option?.dataset.flag;

        let html = '';
        if (image) html += `<img src="${image}" class="w-5 h-5 mr-2 rounded-full object-cover">`;
        else if (flag) html += `<span class="fi fi-${flag} mr-2 rounded-sm shadow-sm"></span>`;
        else if (icon) html += `<i data-lucide="${icon}" class="w-4 h-4 mr-2 opacity-70"></i>`;

        html += `<span class="truncate flex-1 text-left select-none">${text}</span>`;
        html += `<i data-lucide="chevron-down" class="custom-select-icon w-4 h-4 ml-2 opacity-70 transition-transform duration-200"></i>`;
        
        this.trigger.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: this.trigger });
    }

    buildSearch() {
        const div = document.createElement('div');
        div.className = 'p-2 bg-background z-10 border-b border-accents-2 rounded-t-xl sticky top-0';
        
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'w-full px-2 py-1.5 text-xs bg-accents-1 border border-accents-2 rounded-md focus:outline-none focus:ring-1 focus:ring-foreground transition-all';
        input.placeholder = 'Search...';
        input.setAttribute('role', 'searchbox');
        
        input.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            Array.from(this.listContainer.children).forEach(el => {
                el.style.display = el.textContent.toLowerCase().includes(term) ? 'flex' : 'none';
            });
        });
        
        input.addEventListener('click', e => e.stopPropagation());
        
        div.appendChild(input);
        this.menu.appendChild(div);
        this.searchInput = input;
    }

    buildOptions() {
        this.listContainer.innerHTML = '';
        this.options.forEach((opt, idx) => {
            const el = document.createElement('div');
            el.className = 'px-3 py-2 text-sm cursor-pointer hover:bg-accents-1 transition-colors flex items-center gap-2 relative';
            el.setAttribute('role', 'option');
            el.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
            if (opt.selected) el.classList.add('bg-accents-1', 'font-medium');

            // Icon/Image Logic
            if (opt.dataset.image) el.innerHTML += `<img src="${opt.dataset.image}" class="w-5 h-5 rounded-full object-cover">`;
            else if (opt.dataset.flag) el.innerHTML += `<span class="fi fi-${opt.dataset.flag} rounded-sm shadow-sm"></span>`;
            else if (opt.dataset.icon) el.innerHTML += `<i data-lucide="${opt.dataset.icon}" class="w-4 h-4 opacity-70"></i>`;
            
            el.innerHTML += `<span class="truncate">${opt.text}</span>`;
            
            // Selected Checkmark
            if (opt.selected) {
                el.innerHTML += `<i data-lucide="check" class="w-3 h-3 ml-auto text-foreground absolute right-3"></i>`;
            }

            el.addEventListener('click', () => this.select(idx));
            this.listContainer.appendChild(el);
        });
    }

    bindEvents() {
        this.trigger.addEventListener('click', e => {
            e.stopPropagation();
            this.toggle();
        });

        // Keyboard support on trigger
        this.trigger.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                this.open();
            }
        });

        // Click-outside detection untuk portal menu
        this._outsideHandler = e => {
            if (this.isOpen && !this.menu.contains(e.target) && !this.trigger.contains(e.target)) {
                this.close();
            }
        };

        // Reposition/close saat scroll atau resize
        this._scrollHandler = () => {
            if (this.isOpen) this._positionMenu();
        };
        this._resizeHandler = () => {
            if (this.isOpen) this._positionMenu();
        };
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        // Close others
        CustomSelect.instances.forEach(i => i !== this && i.close());

        // Portal: append menu ke body agar tidak ter-clip oleh ancestor overflow
        if (this.menu.parentNode !== document.body) {
            document.body.appendChild(this.menu);
        }

        this.isOpen = true;

        // Tambah class open DULU agar menu ter-layout dengan benar (visibility:visible),
        // baru ukur dan posisi. Menu sementara di off-screen saat diukur.
        this.menu.style.left = '-9999px';
        this.menu.style.top = '0px';
        this.menu.classList.add('open');
        this.trigger.classList.add('ring-1', 'ring-foreground');
        this.trigger.setAttribute('aria-expanded', 'true');
        this.trigger.querySelector('.custom-select-icon')?.classList.add('rotate-180');

        // Posisi menu tepat di bawah trigger
        this._positionMenu();

        // Pasang listener setelah menu terlihat (delay 0 untuk avoid race condition)
        setTimeout(() => {
            document.addEventListener('click', this._outsideHandler, true);
            window.addEventListener('scroll', this._scrollHandler, true);
            window.addEventListener('resize', this._resizeHandler);
        }, 0);

        // Keyboard navigation
        this._keyHandler = e => {
            if (!this.isOpen) return;
            if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
                this.trigger.focus();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this._focusOption(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this._focusOption(-1);
            }
        };
        document.addEventListener('keydown', this._keyHandler);

        if (this.searchInput) setTimeout(() => this.searchInput.focus(), 50);
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: this.menu });
    }

    _positionMenu() {
        var rect = this.trigger.getBoundingClientRect();
        var triggerWidth = rect.width;

        // Reset positioning classes dan inline properti residual.
        this.menu.classList.remove(
            'right-0', 'left-0',
            'origin-top-right', 'origin-top-left',
            'origin-bottom-right', 'origin-bottom-left',
            'dropdown-up'
        );
        this.menu.style.right = '';

        // Set width menu = lebar trigger (bukan min-width, agar tidak membengkak
        // oleh konten option yang panjang). Menu selalu selebar trigger.
        this.menu.style.width = triggerWidth + 'px';
        this.menu.style.minWidth = triggerWidth + 'px';

        // Ukur tinggi menu (menu sudah visible karena open class ditambahkan di open())
        var menuH = this.menu.offsetHeight || 240;
        var spaceBelow = window.innerHeight - rect.bottom;
        var spaceAbove = rect.top;

        // Vertical: flip ke atas jika tidak cukup ruang di bawah
        var goUp = spaceBelow < menuH && spaceAbove > spaceBelow;
        if (goUp) {
            this.menu.classList.add('dropdown-up');
        }

        // Origin animasi
        var originY = goUp ? 'bottom' : 'top';
        this.menu.classList.add('origin-' + originY + '-left');

        // Hitung top: tepat di bawah trigger (atau di atas jika goUp)
        var top;
        if (goUp) {
            top = rect.top - menuH - 4;
        } else {
            top = rect.bottom + 4;
        }

        // Hitung left: selalu sejajar dengan sisi kiri trigger.
        var left = rect.left;

        // Clamp agar tidak keluar viewport
        var mw = this.menu.offsetWidth || triggerWidth;
        left = Math.min(left, window.innerWidth - mw - 8);
        left = Math.max(8, left);
        top = Math.min(top, window.innerHeight - menuH - 8);
        top = Math.max(8, top);

        this.menu.style.top = top + 'px';
        this.menu.style.left = left + 'px';
    }

    _focusOption(dir) {
        const opts = Array.from(this.listContainer.querySelectorAll('[role="option"]'))
            .filter(el => el.style.display !== 'none');
        if (!opts.length) return;
        const current = opts.findIndex(el => el === document.activeElement);
        const next = current === -1 ? (dir > 0 ? 0 : opts.length - 1) : Math.max(0, Math.min(opts.length - 1, current + dir));
        opts[next].focus();
        opts[next].setAttribute('tabindex', '0');
    }

    close() {
        if (!this.isOpen) return;
        this.isOpen = false;
        this.menu.classList.remove('open');
        this.trigger.classList.remove('ring-1', 'ring-foreground');
        this.trigger.setAttribute('aria-expanded', 'false');
        this.trigger.querySelector('.custom-select-icon')?.classList.remove('rotate-180');

        // Cleanup listener
        document.removeEventListener('click', this._outsideHandler, true);
        window.removeEventListener('scroll', this._scrollHandler, true);
        window.removeEventListener('resize', this._resizeHandler);
        if (this._keyHandler) {
            document.removeEventListener('keydown', this._keyHandler);
            this._keyHandler = null;
        }

        // Pindahkan menu kembali ke wrapper (off-DOM tapi tetap terhubung)
        // agar tidak meninggalkan orphan node di body.
        if (this.menu.parentNode === document.body) {
            // Simpan reference; tidak perlu re-append ke wrapper karena menu
            // akan di-portal lagi saat open(). Cukup detach.
            this.menu.remove();
        }
    }

    select(index) {
        this.originalSelect.selectedIndex = index;
        this.renderTrigger();
        this.buildOptions(); // Rebuild to move checkmark
        this.close();
        this.originalSelect.dispatchEvent(new Event('change', { bubbles: true }));
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: this.wrapper });
    }

    refresh() {
        this.options = Array.from(this.originalSelect.options);
        this.buildOptions();
        this.renderTrigger();
    }
}

// Register to Kaiarasa Framework
if (window.Kaiarasa) {
    window.Kaiarasa.registerComponent('Select', CustomSelect);
    
    // Auto-init on load
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('select.custom-select').forEach(el => new CustomSelect(el));
    });
} else {
    window.CustomSelect = CustomSelect;
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('select.custom-select').forEach(el => new CustomSelect(el));
    });
}
