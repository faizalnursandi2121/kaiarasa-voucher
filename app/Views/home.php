<?php
require_once ROOT.'/app/Views/layouts/header_main.php';
?>

<!-- =============================================================
     HOME v2 — NOC Dashboard (spec H1–H6, mockup ui home.png)
============================================================== -->
<div class="w-full max-w-[1400px] mx-auto py-6 px-4 sm:px-6">

    <!-- ===== 1. NAV TILES ===== -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
        <div class="rounded-2xl bg-[#5f7f67] text-white p-5 flex flex-col items-center justify-center gap-2 min-h-[110px] cursor-default">
            <i data-lucide="network" class="w-7 h-7"></i>
            <span class="font-semibold text-sm">Network Overview</span>
            <span class="text-[11px] text-white/70">All routers status</span>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5 flex flex-col items-center justify-center gap-2 min-h-[110px] opacity-50 cursor-not-allowed relative">
            <span class="absolute top-2 right-2 text-[9px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-600 rounded-full px-2 py-0.5">Soon</span>
            <i data-lucide="file-text" class="w-7 h-7"></i>
            <span class="font-semibold text-sm">Logs</span>
            <span class="text-[11px] opacity-50">Coming soon</span>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5 flex flex-col items-center justify-center gap-2 min-h-[110px] opacity-50 cursor-not-allowed relative">
            <span class="absolute top-2 right-2 text-[9px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-600 rounded-full px-2 py-0.5">Soon</span>
            <i data-lucide="bell" class="w-7 h-7"></i>
            <span class="font-semibold text-sm">Alerts</span>
            <span class="text-[11px] opacity-50">Coming soon</span>
        </div>
        <a href="/settings" class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5 flex flex-col items-center justify-center gap-2 min-h-[110px] hover:border-[#92aa96] transition-colors group">
            <i data-lucide="settings" class="w-7 h-7 opacity-60 group-hover:opacity-100 transition-opacity"></i>
            <span class="font-semibold text-sm">Settings</span>
            <span class="text-[11px] opacity-50">System settings</span>
        </a>
    </div>

    <!-- ===== 2. STAT CARDS ===== -->
    <div id="stat-cards" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?php foreach ([
            ['total', 'Total Routers', 'router', ''],
            ['online', 'Online', 'check-circle', 'text-emerald-600 bg-emerald-500/10'],
            ['offline', 'Offline', 'x-circle', 'text-red-600 bg-red-500/10'],
            ['connecting', 'Connecting', 'loader', 'text-amber-600 bg-amber-500/10'],
        ] as [$key, $label, $icon, $chip]): ?>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-full flex items-center justify-center <?= $chip ?: 'bg-black/[.04] dark:bg-white/[.06]' ?>">
                        <i data-lucide="<?= $icon ?>" class="w-5 h-5"></i>
                    </span>
                    <span class="text-sm font-semibold"><?= $label ?></span>
                </div>
                <button type="button" class="opacity-30 hover:opacity-60 transition-opacity" aria-label="Options">
                    <i data-lucide="more-vertical" class="w-4 h-4"></i>
                </button>
            </div>
            <p class="text-3xl font-bold tabular-nums leading-none" id="stat-<?= $key ?>">—</p>
            <p class="text-xs mt-2" id="stat-<?= $key ?>-sub">&nbsp;</p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ===== 3. MAIN GRID ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        <!-- LEFT: Router Status Table -->
        <div class="lg:col-span-2 rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] overflow-hidden">
            <div class="p-5 pb-4 flex flex-wrap items-center gap-3 justify-between">
                <div>
                    <h2 class="text-lg font-bold tracking-tight">Router Status</h2>
                    <p class="text-xs opacity-50 mt-0.5">Real-time status of all connected routers · <span id="last-updated">…</span></p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="search" id="table-search" placeholder="Cari router…"
                        class="h-9 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-3 pr-3 text-[13px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 w-40 sm:w-48 transition">
                    <select id="status-filter"
                        class="h-9 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-2 text-[13px] outline-none cursor-pointer">
                        <option value="">All Status</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="error">Error</option>
                        <option value="connecting">Connecting</option>
                    </select>
                    <button type="button" id="btn-refresh" title="Force refresh"
                        class="h-9 w-9 inline-flex items-center justify-center rounded-xl border border-black/10 dark:border-white/10 hover:bg-black/[.03] dark:hover:bg-white/[.05] disabled:opacity-50 disabled:cursor-wait transition-all">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto" id="table-scroll"></div>
            <div class="flex items-center justify-between px-5 py-3 border-t border-black/[.06] dark:border-white/[.06] text-xs opacity-60">
                <span id="pagination-info">—</span>
                <div id="pagination" class="flex items-center gap-1"></div>
            </div>
        </div>

        <!-- RIGHT column -->
        <div class="flex flex-col gap-4">
            <!-- Network Availability -->
            <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold tracking-tight">Network Availability</h3>
                    <span class="text-[11px] opacity-50">Last 24 hours</span>
                </div>
                <div id="chart-availability"></div>
                <div class="grid grid-cols-3 gap-2 pt-4 mt-2 border-t border-black/[.05] dark:border-white/[.05] text-center">
                    <div><p class="text-[10px] uppercase tracking-wide opacity-50 mb-1">Avg Uptime</p><p class="font-bold text-emerald-600 dark:text-emerald-400 text-sm tabular-nums" id="avg-uptime">—</p></div>
                    <div><p class="text-[10px] uppercase tracking-wide opacity-50 mb-1">Downtime</p><p class="font-bold text-red-600 dark:text-red-400 text-sm tabular-nums" id="downtime">—</p></div>
                    <div><p class="text-[10px] uppercase tracking-wide opacity-50 mb-1">Incidents</p><p class="font-bold text-sm tabular-nums" id="incidents">—</p></div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5 flex-grow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold tracking-tight">Recent Activity</h3>
                    <span class="text-[11px] opacity-50">live</span>
                </div>
                <ul id="activity-list" class="space-y-3 max-h-[280px] overflow-y-auto pr-1">
                    <li class="text-xs opacity-50">memuat…</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== 4. BOTTOM WIDGETS ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <h3 class="font-bold tracking-tight mb-3">Status Distribution</h3>
            <div id="chart-distribution"></div>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold tracking-tight">Top Router by CPU</h3>
                <span class="text-[11px] opacity-50">top 5</span>
            </div>
            <div id="chart-topcpu"></div>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold tracking-tight">System Health</h3>
                <span class="text-[11px] opacity-50" title="Kesehatan komponen aplikasi MIVO">info</span>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3"><i data-lucide="plug-zap" class="w-5 h-5 opacity-60"></i><div><p class="text-sm font-semibold">API Status</p><p class="text-[11px] opacity-50">Health endpoint</p></div></div>
                    <span id="health-api" class="text-sm font-semibold opacity-40">—</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3"><i data-lucide="database" class="w-5 h-5 opacity-60"></i><div><p class="text-sm font-semibold">Database</p><p class="text-[11px] opacity-50">SQLite lokal</p></div></div>
                    <span id="health-db" class="text-sm font-semibold opacity-40">—</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3"><i data-lucide="cloud-upload" class="w-5 h-5 opacity-60"></i><div><p class="text-sm font-semibold">Last Backup</p><p class="text-[11px] opacity-50">via Settings → System</p></div></div>
                    <span class="text-sm font-semibold opacity-40" title="Belum tersedia — jalankan backup dari Settings">—</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== Router Modal (Add / Edit) ===== -->
<div id="router-modal" class="hidden fixed inset-0 z-[250] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-close-modal></div>
    <div class="relative w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-2xl border border-black/[.08] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-6 sm:p-8 shadow-2xl">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h3 id="router-modal-title" class="text-lg font-bold tracking-tight">Add Router</h3>
                <p class="text-xs opacity-50 mt-0.5">Konfigurasi koneksi RouterOS API</p>
            </div>
            <button type="button" data-close-modal aria-label="Close"
                class="w-8 h-8 rounded-lg inline-flex items-center justify-center hover:bg-black/[.05] dark:hover:bg-white/[.06] transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="router-form" class="space-y-4" novalidate>
            <input type="hidden" name="id" id="rf-id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">Session Name *</label>
                    <input name="sessname" id="rf-sessname" required placeholder="e.g. router-jakarta-1"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    <p class="hidden text-xs text-red-600 mt-1.5" data-err="sessname"></p>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">IP Address *</label>
                    <input name="ipmik" id="rf-ipmik" required placeholder="192.168.88.1"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    <p class="hidden text-xs text-red-600 mt-1.5" data-err="ipmik"></p>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">Username *</label>
                    <input name="usermik" id="rf-usermik" required placeholder="admin"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">Password <span class="normal-case font-normal" id="rf-pass-hint"></span></label>
                    <input type="password" name="passmik" id="rf-passmik" autocomplete="new-password" placeholder="••••••••"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">Hotspot Name *</label>
                    <input name="hotspotname" id="rf-hotspotname" required placeholder="My Hotspot ID"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">DNS Name *</label>
                    <input name="dnsname" id="rf-dnsname" required placeholder="hotspot.net"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">API Port</label>
                    <input type="number" name="port" id="rf-port" min="1" max="65535" value="8728"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <div class="flex flex-col gap-3 pt-7">
                    <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                        <input type="checkbox" name="quick_access" id="rf-quick" value="1" class="w-4 h-4 accent-[#5f7f67]"> Quick Access
                    </label>
                    <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                        <input type="checkbox" name="ssl" id="rf-ssl" value="1" class="w-4 h-4 accent-[#5f7f67]"> Gunakan SSL (api-ssl)
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-5 border-t border-black/[.06] dark:border-white/[.06]">
                <button type="button" data-close-modal
                    class="h-10 px-4 rounded-xl border border-black/10 dark:border-white/10 text-[13px] font-semibold hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">Cancel</button>
                <button type="submit" id="rf-submit"
                    class="h-10 px-5 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[13px] font-semibold transition-colors disabled:opacity-60 disabled:cursor-wait inline-flex items-center gap-2">
                    <span id="rf-submit-label">Save Router</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/assets/js/vendor/apexcharts.min.js"></script>
<script>
(function () {
    'use strict';

    /* ================= helpers ================= */
    var esc = function (s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    };
    var relTime = function (iso) {
        if (!iso || String(iso).indexOf('$') === 0) return '-';
        var d = new Date(iso), s = Math.floor((Date.now() - d.getTime()) / 1000);
        if (isNaN(s)) return '-';
        if (s < 5)   return 'just now';
        if (s < 60)  return s + 's ago';
        if (s < 3600)  return Math.floor(s/60) + 'm ago';
        if (s < 86400) return Math.floor(s/3600) + 'h ago';
        return Math.floor(s/86400) + 'd ago';
    };
    var fmtDown = function (sec) {
        sec = parseInt(sec, 10) || 0;
        var h = Math.floor(sec/3600), m = Math.floor((sec%3600)/60);
        return h > 0 ? h+'h '+m+'m' : m+'m';
    };

    var charts = {};
    var isDark = function () { return document.documentElement.classList.contains('dark'); };
    var gridColor = function () { return isDark() ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)'; };
    var labelColor = function () { return isDark() ? '#9aa39b' : '#6b7280'; };
    var baseChart = function (opts) {
        opts.chart = Object.assign({ fontFamily: 'inherit', foreColor: labelColor(),
            toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' }, opts.chart || {});
        return opts;
    };

    /* ================= router table ================= */
    var PAGE_SIZE = 25;
    var state = { routers: [], page: 1, query: '', filter: '', checkedAt: null };
    var grid = document.getElementById('table-scroll');

    function badge(status) {
        var map = {
            online:     ['Online',     'emerald', '●'],
            offline:    ['Offline',    'red',     '●'],
            error:      ['API Error',  'amber',   '●'],
            connecting: ['Connecting', 'amber',   '◌']
        };
        var m = map[status] || map.offline;
        return '<span class="inline-flex items-center gap-1.5 rounded-full bg-'+m[1]+'-500/10 px-2.5 py-1 text-[11px] font-semibold text-'+m[1]+'-600 dark:text-'+m[1]+'-400">'
             + '<span class="w-1.5 h-1.5 rounded-full bg-'+m[1]+'-500"></span>'+m[0]+'</span>';
    }
    function cpuBar(v) {
        if (v === null || v === undefined) return '<span class="opacity-40">-</span>';
        var color = v > 80 ? 'bg-red-500' : (v > 50 ? 'bg-amber-500' : 'bg-emerald-500');
        return '<div class="flex items-center gap-2"><span class="tabular-nums text-xs">'+v+'%</span>'
             + '<span class="w-16 h-1 rounded-full bg-black/10 dark:bg-white/10 overflow-hidden inline-block">'
             + '<span class="block h-full '+color+'" style="width:'+Math.min(100,v)+'%"></span></span></div>';
    }
    function rowHTML(r) {
        var metrics = r.status === 'online'
            ? { cpu: cpuBar(r.cpu_load), up: esc(r.uptime ?? '-'), seen: relTime(r.last_seen) }
            : { cpu: '<span class="opacity-40">-</span>', up: '<span class="opacity-40">-</span>',
                seen: r.last_seen ? relTime(r.last_seen) : '<span class="opacity-40">'+esc(r.error || '-')+'</span>' };
        var initial = esc((r.session_name || '?').slice(0, 2).toUpperCase());
        return '<tr class="border-t border-black/[.05] dark:border-white/[.05] hover:bg-black/[.02] dark:hover:bg-white/[.03] cursor-pointer" data-session="'+esc(r.session_name)+'">'
            + '<td class="px-4 py-3">'+badge(r.status)+'</td>'
            + '<td class="px-4 py-3"><div class="flex items-center gap-3">'
            +   '<span class="w-8 h-8 rounded-lg bg-[#92aa96]/20 text-[#47614d] dark:text-[#92aa96] text-[11px] font-bold flex items-center justify-center shrink-0">'+initial+'</span>'
            +   '<div><p class="font-semibold text-[13px] leading-tight">'+esc(r.session_name)+'</p>'
            +   '<p class="text-[11px] opacity-50">'+esc(r.location || r.hotspot_name || '')+'</p></div></div></td>'
            + '<td class="px-4 py-3 font-mono text-xs">'+esc(r.ip_address)+'</td>'
            + '<td class="px-4 py-3">'+metrics.cpu+'</td>'
            + '<td class="px-4 py-3 text-xs tabular-nums">'+metrics.up+'</td>'
            + '<td class="px-4 py-3 text-xs whitespace-nowrap">'+metrics.seen+'</td>'
            + '<td class="px-4 py-3 text-right relative">'
            +   '<button type="button" class="w-8 h-8 inline-flex items-center justify-center rounded-lg hover:bg-black/[.06] dark:hover:bg-white/[.08] transition-colors" data-actions-menu aria-haspopup="true">'
            +   '<i data-lucide="more-vertical" class="w-4 h-4"></i></button>'
            +   '<div class="hidden absolute right-4 top-full mt-0 z-30 w-44 rounded-xl border border-black/[.08] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] shadow-xl overflow-hidden text-left" data-menu>'
            +     '<button type="button" class="w-full px-4 py-2.5 text-xs font-medium text-left hover:bg-black/[.04] dark:hover:bg-white/[.05] flex items-center gap-2.5" data-act="open" data-s="'+esc(r.session_name)+'"><i data-lucide="external-link" class="w-3.5 h-3.5 opacity-60"></i>Open Dashboard</button>'
            +     '<button type="button" class="w-full px-4 py-2.5 text-xs font-medium text-left hover:bg-black/[.04] dark:hover:bg-white/[.05] flex items-center gap-2.5" data-act="edit"><i data-lucide="pencil" class="w-3.5 h-3.5 opacity-60"></i>Edit Router</button>'
            +     '<button type="button" class="w-full px-4 py-2.5 text-xs font-medium text-left hover:bg-black/[.04] dark:hover:bg-white/[.05] flex items-center gap-2.5" data-act="test"><i data-lucide="plug-zap" class="w-3.5 h-3.5 opacity-60"></i>Test Connection</button>'
            +     '<div class="border-t border-black/[.05] dark:border-white/[.05]"></div>'
            +     '<button type="button" class="w-full px-4 py-2.5 text-xs font-semibold text-left text-red-600 hover:bg-red-500/[.07] flex items-center gap-2.5" data-act="delete"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Delete</button>'
            +   '</div>'
            + '</td>'
            + '</tr>';
    }

    function filtered() {
        var q = state.query.toLowerCase();
        return state.routers.filter(function (r) {
            if (state.filter && r.status !== state.filter) return false;
            if (!q) return true;
            return ['session_name','hotspot_name','ip_address','location'].some(function (k) {
                return String(r[k] || '').toLowerCase().indexOf(q) > -1;
            });
        });
    }

    function renderTable() {
        var list = filtered();
        var pages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
        if (state.page > pages) state.page = pages;
        var start = (state.page - 1) * PAGE_SIZE;
        var slice = list.slice(start, start + PAGE_SIZE);

        if (!state.routers.length) {
            grid.innerHTML = '<div class="py-14 text-center text-sm opacity-50">Belum ada router.'
                + ' Klik <a href="/settings/add" class="underline font-semibold">Add Router</a> untuk menambahkan pertama Anda.</div>';
        } else if (!list.length) {
            grid.innerHTML = '<div class="py-14 text-center text-sm opacity-50">Tidak ada router yang cocok dengan pencarian/filter.</div>';
        } else {
            grid.innerHTML = '<table class="w-full text-sm"><thead><tr class="text-left text-[11px] uppercase tracking-wider opacity-50 border-b border-black/[.05] dark:border-white/[.05]">'
                + '<th class="px-4 py-2.5 font-semibold">Status</th><th class="px-4 py-2.5 font-semibold">Router Name</th>'
                + '<th class="px-4 py-2.5 font-semibold">IP Address</th><th class="px-4 py-2.5 font-semibold">CPU</th>'
                + '<th class="px-4 py-2.5 font-semibold">Uptime</th><th class="px-4 py-2.5 font-semibold">Last Seen</th>'
                + '<th class="px-4 py-2.5 font-semibold text-right">Actions</th></tr></thead><tbody>'
                + slice.map(rowHTML).join('') + '</tbody></table>';
        }

        // pagination
        var pg = document.getElementById('pagination');
        pg.innerHTML = '';
        if (pages > 1) {
            for (var p = 1; p <= pages; p++) {
                (function (p) {
                    var b = document.createElement('button');
                    b.type = 'button'; b.textContent = p;
                    b.className = 'min-w-[26px] h-7 px-1.5 rounded-md text-xs font-semibold transition-colors '
                        + (p === state.page ? 'bg-[#5f7f67] text-white' : 'hover:bg-black/[.05] dark:hover:bg-white/[.07]');
                    b.onclick = function () { state.page = p; renderTable(); };
                    pg.appendChild(b);
                })(p);
            }
        }
        document.getElementById('pagination-info').textContent =
            list.length ? ('Showing '+Math.min(start+1, list.length)+'–'+Math.min(start+PAGE_SIZE, list.length)+' of '+list.length+' routers') : '0 routers';
        if (window.lucide) lucide.createIcons();
    }

    function renderStats(routers) {
        var total = routers.length;
        var online = routers.filter(function (r) { return r.status === 'online'; }).length;
        var offline = routers.filter(function (r) { return r.status === 'offline'; }).length;
        var connecting = routers.filter(function (r) { return r.status === 'connecting'; }).length;
        var pct = function (n) { return total ? Math.round(n/total*1000)/10 + '%' : '-'; };
        var set = function (id, num, sub) {
            document.getElementById('stat-'+id).textContent = num;
            document.getElementById('stat-'+id+'-sub').textContent = sub;
        };
        set('total', total, total ? 'All locations' : 'belum ada router');
        set('online', online, online+' dari '+total);
        set('offline', offline, pct(offline));
        set('connecting', connecting, pct(connecting));
        return { total: total, online: online, offline: offline, connecting: connecting };
    }

    /* ================= charts ================= */
    function chartAvail(series, agg) {
        document.getElementById('avg-uptime').textContent =
            (agg.avg_uptime_pct !== undefined ? Math.round(agg.avg_uptime_pct*10)/10 : '-') + '%';
        document.getElementById('downtime').textContent = fmtDown(agg.downtime_seconds);
        document.getElementById('incidents').textContent = agg.incidents ?? '-';
        var el = document.getElementById('chart-availability');
        if (charts.avail) charts.avail.destroy();
        if (!series.length) { el.innerHTML = '<p class="text-xs opacity-50 py-10 text-center">Belum ada riwayat.</p>'; return; }
        el.innerHTML = '';
        charts.avail = new ApexCharts(el, baseChart({
            series: [{ name: 'Availability', data: series.map(function (s) { return [s.ts * 1000, s.availability_pct]; }) }],
            chart: { type: 'area', height: 180, animations: { speed: 300 } },
            colors: ['#5f7f67'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .02 } },
            xaxis: { type: 'datetime', labels: { datetimeUTC: false }, axisBorder: { show: false }, axisTicks: { show: false }, lines: { show: false } },
            yaxis: { min: 0, max: 100, tickAmount: 4, labels: { formatter: function (v) { return v + '%'; } } },
            grid: { borderColor: gridColor() },
            tooltip: { theme: isDark() ? 'dark' : 'light', y: { formatter: function (v) { return v + '%'; } } },
        }));
        charts.avail.render();
    }

    function chartDonut(counts) {
        var el = document.getElementById('chart-distribution');
        if (charts.donut) charts.donut.destroy();
        charts.donut = new ApexCharts(el, baseChart({
            series: [counts.online, counts.offline, counts.error, counts.connecting],
            labels: ['Online', 'Offline', 'Error', 'Connecting'],
            colors: ['#10b981', '#ef4444', '#f59e0b', '#92aa96'],
            chart: { type: 'donut', height: 220 },
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: { pie: { donut: { size: '72%', labels: {
                show: true,
                name: { fontSize: '11px' },
                value: { fontSize: '22px', fontWeight: 700 },
                total: { show: true, label: 'Total', fontSize: '11px',
                         formatter: function (w) { return w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0); } }
            } } } },
            dataLabels: { enabled: false },
        }));
        charts.donut.render();
    }

    function chartTopCPU(routers) {
        var top = routers.filter(function (r) { return r.status === 'online' && r.cpu_load !== null && r.cpu_load !== undefined; })
            .sort(function (a, b) { return b.cpu_load - a.cpu_load; }).slice(0, 5);
        var el = document.getElementById('chart-topcpu');
        if (charts.topcpu) charts.topcpu.destroy();
        if (!top.length) { el.innerHTML = '<p class="text-xs opacity-50 py-10 text-center">Tidak ada data CPU.</p>'; return; }
        charts.topcpu = new ApexCharts(el, baseChart({
            series: [{ name: 'CPU %', data: top.map(function (r) { return r.cpu_load; }) }],
            chart: { type: 'bar', height: 220 },
            colors: top.map(function (r) { return r.cpu_load > 80 ? '#ef4444' : (r.cpu_load > 50 ? '#f59e0b' : '#5f7f67'); }),
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '45%' } },
            xaxis: { categories: top.map(function (r) { return r.session_name; }), max: 100, labels: { formatter: function (v) { return v + '%'; } } },
            grid: { borderColor: gridColor() },
            dataLabels: { enabled: true, formatter: function (v) { return v + '%'; }, style: { fontSize: '11px' } },
        }));
        charts.topcpu.render();
    }

    /* ================= recent activity ================= */
    function renderActivity(events) {
        var ul = document.getElementById('activity-list');
        if (!events.length) { ul.innerHTML = '<li class="text-xs opacity-50">Belum ada aktivitas.</li>'; return; }
        var icons = {
            connected:    ['arrow-up-circle', 'text-emerald-600', 'connected'],
            went_offline: ['arrow-down-circle', 'text-red-600', 'went offline'],
            high_cpu:     ['alert-triangle', 'text-amber-600', 'high CPU usage']
        };
        ul.innerHTML = events.map(function (ev) {
            var ic = icons[ev.event_type] || icons.connected;
            return '<li class="flex items-start gap-3 text-xs">'
                + '<i data-lucide="'+ic[0]+'" class="w-4 h-4 mt-0.5 shrink-0 '+ic[1]+'"></i>'
                + '<div class="flex-grow"><p class="font-medium">Router '+esc(ev.router_name)+' '+ic[2]+'</p>'
                + '<p class="opacity-50 mt-0.5">'+esc(ev.hotspot_name || '')+'</p></div>'
                + '<span class="opacity-40 whitespace-nowrap">'+relTime(ev.created_at.replace(' ', 'T')+'Z')+'</span></li>';
        }).join('');
        if (window.lucide) lucide.createIcons();
    }

    /* ================= system health ================= */
    function renderSysHealth(ok) {
        var api = document.getElementById('health-api');
        var db = document.getElementById('health-db');
        api.textContent = ok ? 'Healthy' : 'Degraded';
        api.className = 'text-sm font-semibold ' + (ok ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600');
        db.textContent = ok ? 'Healthy' : 'Unknown';
        db.className = 'text-sm font-semibold ' + (ok ? 'text-emerald-600 dark:text-emerald-400' : 'opacity-40');
    }

    /* ================= data loading ================= */
    var inFlight = false;
    function load(force) {
        if (inFlight) return;
        inFlight = true;
        document.getElementById('btn-refresh').disabled = true;
        fetch('/api/routers/health' + (force ? '?refresh=1' : ''), { headers: { Accept: 'application/json' } })
            .then(function (res) { if (!res.ok) throw new Error(res.status); renderSysHealth(true); return res.json(); })
            .then(function (data) {
                state.routers = data.routers || [];
                state.checkedAt = data.checked_at;
                state.page = 1;
                renderTable(); renderStats(state.routers); renderSysHealth(true);
                var d = new Date(data.checked_at * 1000);
                document.getElementById('last-updated').textContent = 'terakhir diperbarui ' +
                    d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                // history & events hanya perlu sesekali
                return Promise.all([
                    fetch('/api/routers/history?hours=24').then(function (r) { return r.json(); }),
                    fetch('/api/routers/events?limit=10').then(function (r) { return r.json(); }),
                ]).then(function (out) {
                    chartAvail(out[0].series || [], out[0]);
                    document.getElementById('activity-list').innerHTML = '';
                    renderActivity(out[1] || []);
                });
            })
            .catch(function () {
                document.getElementById('last-updated').textContent = 'gagal memuat — coba Refresh';
                renderSysHealth(false);
            })
            .finally(function () {
                inFlight = false;
                document.getElementById('btn-refresh').disabled = false;
            });
    }


    /* ================= router CRUD modal & row actions ================= */
    var modal = document.getElementById('router-modal');
    var form = document.getElementById('router-form');
    var submitBtn = document.getElementById('rf-submit');

    function openModal(mode, r) {
        form.reset();
        ['sessname','ipmik'].forEach(function (n) {
            var err = form.querySelector('[data-err="'+n+'"]');
            if (err) err.classList.add('hidden');
        });
        if (mode === 'edit') {
            document.getElementById('router-modal-title').textContent = 'Edit Router';
            document.getElementById('rf-submit-label').textContent = 'Save Changes';
            document.getElementById('rf-pass-hint').textContent = '(kosongkan jika tidak diubah)';
            document.getElementById('rf-id').value = r.id;
            document.getElementById('rf-sessname').value = r.session_name;
            document.getElementById('rf-ipmik').value = r.ip_address;
            document.getElementById('rf-usermik').value = '';
            document.getElementById('rf-passmik').value = '';
            document.getElementById('rf-usermik').placeholder = r.username || 'admin';
            document.getElementById('rf-hotspotname').value = r.hotspot_name || '';
            document.getElementById('rf-dnsname').value = '';
            document.getElementById('rf-port').value = 8728;
            form.dataset.mode = 'edit';
        } else {
            document.getElementById('router-modal-title').textContent = 'Add Router';
            document.getElementById('rf-submit-label').textContent = 'Save Router';
            document.getElementById('rf-pass-hint').textContent = '';
            document.getElementById('rf-id').value = '';
            document.getElementById('rf-usermik').placeholder = 'admin';
            delete form.dataset.mode;
        }
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        if (window.lucide) lucide.createIcons();
        setTimeout(function () { document.getElementById('rf-sessname').focus(); }, 50);
    }
    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    modal.querySelectorAll('[data-close-modal]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    /* ---- row actions menu (delegasi) ---- */
    grid.addEventListener('click', function (e) {
        var menuBtn = e.target.closest('[data-actions-menu]');
        if (menuBtn) {
            var menu = menuBtn.parentElement.querySelector('[data-menu]');
            var wasOpen = !menu.classList.contains('hidden');
            grid.querySelectorAll('[data-menu]').forEach(function (m) { m.classList.add('hidden'); });
            if (!wasOpen) menu.classList.remove('hidden');
            e.stopPropagation();
            return;
        }
        // tutup menu bila klik area lain
        if (!e.target.closest('[data-menu]')) {
            grid.querySelectorAll('[data-menu]').forEach(function (m) { m.classList.add('hidden'); });
        }
        var actBtn = e.target.closest('[data-act]');
        if (actBtn) {
            var act = actBtn.getAttribute('data-act');
            var tr = actBtn.closest('tr[data-session]');
            var name = tr ? tr.getAttribute('data-session') : null;
            var r = state.routers.find(function (x) { return x.session_name === name; });
            if (!r) return;
            if (act === 'open') location.href = '/' + name + '/dashboard';
            if (act === 'edit') openModal('edit', r);
            if (act === 'test') testConnection(r);
            if (act === 'delete') deleteRouter(r);
        }
    });

    /* ---- test connection (#UI States #11/#12) ---- */
    function testConnection(r) {
        window.KaiarasaAlert = window.KaiarasaAlert || (window.Kaiarasa && window.Kaiarasa.modules && window.Kaiarasa.modules.Alert);
        fetch('/api/router/interfaces', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ session: r.session_name }),
        }).then(function (res) { return res.json().then(function (j) { return { ok: res.ok, j: j }; }); })
          .then(function (out) {
              if (window.Swal) Swal.fire({ icon: out.ok ? 'success' : 'error',
                  title: out.ok ? 'Connection OK' : 'Connection Failed',
                  text: out.ok ? ((out.j.interfaces ? out.j.interfaces.length : 0) + ' interfaces terdeteksi')
                               : (out.j.error || 'Tidak dapat terhubung'), timer: 3500, showConfirmButton: false });
          }).catch(function () {
              if (window.Swal) Swal.fire({ icon: 'error', title: 'Connection Failed', text: 'Network error', timer: 3000, showConfirmButton: false });
          });
    }

    /* ---- delete (#26 destructive confirm) ---- */
    function deleteRouter(r) {
        var doDelete = function () {
            return fetch('/settings/delete', {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new URLSearchParams({ id: String(r.id) }),
            }).then(function (res) { return res.json(); });
        };
        if (window.Kaiarasa && Kaiarasa.confirm) {
            Kaiarasa.confirm('Delete Router?', 'Router "'+r.session_name+'" dan sesinya akan dihapus permanen.', 'Delete', 'Cancel')
                .then(function (ok) { if (!ok) return; doDelete().then(function (j) {
                    if (window.Swal) Swal.fire({ icon: j.success ? 'success' : 'error', title: j.message, timer: 2500, showConfirmButton: false });
                    load(true);
                }); });
        } else if (window.Swal) {
            Swal.fire({ title: 'Delete Router?', text: r.session_name, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc2626', confirmButtonText: 'Delete' })
                .then(function (res) { if (res.isConfirmed) doDelete().then(function (j) {
                    if (window.Swal) Swal.fire({ icon: j.success ? 'success' : 'error', title: j.message, timer: 2500, showConfirmButton: false });
                    load(true);
                }); });
        } else {
            if (confirm('Delete router '+r.session_name+'?')) doDelete().then(function () { load(true); });
        }
    }

    /* ---- add / edit submit via fetch JSON ---- */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        form.querySelectorAll('[data-err]').forEach(function (p) { p.classList.add('hidden'); });

        var isEdit = form.dataset.mode === 'edit';
        var payload = new URLSearchParams(new FormData(form));
        if (isEdit && !payload.get('passmik')) payload.delete('passmik'); // kosong = tetap

        submitBtn.disabled = true;
        document.getElementById('rf-submit-label').textContent = isEdit ? 'Saving…' : 'Saving…';

        fetch(isEdit ? '/settings/update' : '/settings/store', {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: payload,
        })
        .then(function (res) {
            return res.json().then(function (j) { return { status: res.status, json: j }; });
        })
        .then(function (out) {
            if (out.status === 200 && out.json.success) {
                closeModal();
                if (window.Swal) Swal.fire({ icon: 'success', title: out.json.message, timer: 2200, showConfirmButton: false });
                load(true);
            } else {
                // tampilkan pesan error umum
                if (window.Swal) Swal.fire({ icon: 'error', title: 'Gagal', text: out.json.message || 'Periksa kembali data.', });
            }
        })
        .catch(function () {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Network error' });
        })
        .finally(function () {
            submitBtn.disabled = false;
            document.getElementById('rf-submit-label').textContent =
                form.dataset.mode === 'edit' ? 'Save Changes' : 'Save Router';
        });
    });

    // tombol Add Router pada navbar → buka modal (bukan redirect)
    // stopPropagation: cegah handler SPA navbar menukar #app-dynamic
    document.addEventListener('click', function (e) {
        var a = e.target.closest('a[href="/settings/add"]');
        if (!a) return;
        e.preventDefault();
        e.stopPropagation();
        if (window.__kaiarasaAppSpaNavBlocked !== undefined) { /* noop */ }
        openModal('add', null);
    }, true);

    /* ================= wiring ================= */
    document.getElementById('btn-refresh').addEventListener('click', function () { load(true); });
    document.getElementById('table-search').addEventListener('input', function () {
        state.query = this.value; state.page = 1; renderTable();
    });
    document.getElementById('status-filter').addEventListener('change', function () {
        state.filter = this.value; state.page = 1; renderTable();
    });
    grid.addEventListener('click', function (e) {
        // klik di dalam menu aksi / tombol titik tiga tidak menavigasi
        if (e.target.closest('[data-actions-menu]') || e.target.closest('[data-menu]')) return;
        var tr = e.target.closest('tr[data-session]');
        if (tr && !e.target.closest('a')) location.href = '/' + tr.getAttribute('data-session') + '/dashboard';
    });

    var REFETCH_MS = 60000;
    setInterval(function () { if (!document.hidden) load(false); }, REFETCH_MS);

    renderTable();          // skeleton awal (server-side str_repeat sudah diganti)
    load(false);
})();
</script>

<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
