/**
 * Kaiarasa JS Core "The Kernel"
 * Central management for Modules (Services) and Components (UI).
 */
class KaiarasaCore {
    constructor() {
        this.modules = {};
        this.components = {};
        this.events = new EventTarget();
        this.isReady = false;

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.init());
        } else {
            this.init();
        }
    }

    /**
     * Register a Global Module (Service)
     * @param {string} name 
     * @param {Object} instance 
     */
    registerModule(name, instance) {
        this.modules[name] = instance;
        console.debug(`[Kaiarasa] Module '${name}' registered.`);
    }

    /**
     * Register a UI Component definition
     * @param {string} name 
     * @param {Class} classRef 
     */
    registerComponent(name, classRef) {
        this.components[name] = classRef;
        console.debug(`[Kaiarasa] Component '${name}' registered.`);
    }

    /**
     * Listen to global events
     * @param {string} eventName 
     * @param {function} callback 
     */
    on(eventName, callback) {
        const wrapper = (e) => callback(e.detail);
        this.events.addEventListener(eventName, wrapper);
        // Track wrappers so off() can remove them.
        if (!this._listeners) this._listeners = new Map();
        if (!this._listeners.has(eventName)) this._listeners.set(eventName, new Map());
        this._listeners.get(eventName).set(callback, wrapper);
        return wrapper;
    }

    /**
     * Remove a global event listener previously added with on().
     * @param {string} eventName
     * @param {function} callback
     */
    off(eventName, callback) {
        if (!this._listeners?.has(eventName)) return;
        const map = this._listeners.get(eventName);
        const wrapper = map.get(callback);
        if (wrapper) {
            this.events.removeEventListener(eventName, wrapper);
            map.delete(callback);
        }
    }

    /**
     * Emit global events
     * @param {string} eventName 
     * @param {any} data 
     */
    emit(eventName, data) {
        this.events.dispatchEvent(new CustomEvent(eventName, { detail: data }));
        console.debug(`[Kaiarasa] Event emitted: ${eventName}`, data);
    }

    init() {
        if (this.isReady) return;
        this.isReady = true;
        console.log('[Kaiarasa] Framework initialized.');
        
        // Dispatch ready event for external scripts
        this.emit('ready', { timestamp: Date.now() });
    }

    /**
     * Debounce helper — tunda eksekusi fn hingga `wait` ms berlalu
     * tanpa pemanggilan ulang. Dipakai untuk search input.
     */
    debounce(fn, wait) {
        var t;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait || 300);
        };
    }
}

/**
 * Filter Chip Manager — tampilkan badge untuk setiap filter aktif,
 * dengan tombol clear per-filter dan clear-all.
 * Dipanggil dari view: Kaiarasa.initFilterChips({ 'filter-profile': 'All', ... })
 */
KaiarasaCore.prototype.initFilterChips = function (selectIds, searchInputId) {
    var container = document.querySelector('.filter-chips');
    if (!container) return;

    var selects = selectIds.map(function (id) { return document.getElementById(id); }).filter(Boolean);
    var searchInput = searchInputId ? document.getElementById(searchInputId) : null;
    var clearAllBtn = container.parentElement.querySelector('.filter-clear');

    function renderChips() {
        container.innerHTML = '';
        var hasActive = false;

        selects.forEach(function (sel) {
            if (!sel) return;
            var opt = sel.options[sel.selectedIndex];
            if (!opt || opt.value === '') return;
            hasActive = true;
            var chip = document.createElement('span');
            chip.className = 'filter-chip';
            chip.innerHTML = '<span>' + opt.text + '</span>';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', 'Clear filter');
            btn.innerHTML = '<i data-lucide="x" class="w-3 h-3"></i>';
            btn.addEventListener('click', function () {
                sel.selectedIndex = 0;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            });
            chip.appendChild(btn);
            container.appendChild(chip);
        });

        if (searchInput && searchInput.value.trim()) {
            hasActive = true;
            var chip = document.createElement('span');
            chip.className = 'filter-chip';
            chip.innerHTML = '<span>"' + searchInput.value.trim() + '"</span>';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', 'Clear search');
            btn.innerHTML = '<i data-lucide="x" class="w-3 h-3"></i>';
            btn.addEventListener('click', function () {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
            });
            chip.appendChild(btn);
            container.appendChild(chip);
        }

        if (clearAllBtn) {
            clearAllBtn.classList.toggle('hidden', !hasActive);
        }
        if (typeof lucide !== 'undefined') lucide.createIcons({ root: container });
    }

    selects.forEach(function (sel) {
        if (sel) sel.addEventListener('change', renderChips);
    });
    if (searchInput) searchInput.addEventListener('input', KaiarasaCore.prototype.debounce ? window.Kaiarasa.debounce(renderChips, 200) : renderChips);

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function () {
            selects.forEach(function (sel) {
                if (sel) { sel.selectedIndex = 0; sel.dispatchEvent(new Event('change', { bubbles: true })); }
            });
            if (searchInput) { searchInput.value = ''; searchInput.dispatchEvent(new Event('input', { bubbles: true })); }
        });
    }

    renderChips();
};

// Global Singleton
window.Kaiarasa = new KaiarasaCore();
