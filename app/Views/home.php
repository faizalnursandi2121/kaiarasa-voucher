<?php
require_once ROOT.'/app/Views/layouts/header_main.php';
?>

<!-- =============================================================
     HOME v2 — NOC Dashboard (spec H1–H6, mockup ui home.png)
============================================================== -->
<div class="w-full max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

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
        <a href="/settings" class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5 flex flex-col items-center justify-center gap-2 min-h-[110px] transition-colors group hover:bg-[#5f7f67]/[.08] hover:border-[#5f7f67]/40">
            <i data-lucide="settings" class="w-7 h-7 opacity-60 group-hover:opacity-100 group-hover:text-[#47614d] dark:group-hover:text-[#92aa96] transition-colors"></i>
            <span class="font-semibold text-sm group-hover:text-[#47614d] dark:group-hover:text-[#92aa96] transition-colors">Settings</span>
            <span class="text-[11px] opacity-50">System settings</span>
        </a>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5 flex flex-col items-center justify-center gap-2 min-h-[110px] opacity-50 cursor-not-allowed relative">
            <span class="absolute top-2 right-2 text-[9px] font-bold uppercase tracking-wider bg-amber-500/15 text-amber-600 rounded-full px-2 py-0.5">Soon</span>
            <i data-lucide="bar-chart-3" class="w-7 h-7"></i>
            <span class="font-semibold text-sm">Reports</span>
            <span class="text-[11px] opacity-50">Coming soon</span>
        </div>
    </div>

    <!-- ===== 2. MAIN GRID ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">

        <!-- LEFT: Router Status Table -->
        <div class="lg:col-span-2 rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] overflow-hidden">
            <div class="p-5 pb-4 flex flex-wrap items-center gap-3 justify-between">
                <div>
                    <h2 class="text-lg font-bold tracking-tight">Router Status</h2>
                    <p class="text-xs opacity-50 mt-0.5">Real-time status of all connected routers</p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="search" id="table-search" placeholder="Search routers…"
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
                <div id="chart-availability"><div class="rounded-xl bg-black/[.04] dark:bg-white/[.04] animate-pulse" style="height:190px"></div></div>
                <div class="grid grid-cols-3 gap-2 pt-4 mt-2 border-t border-black/[.05] dark:border-white/[.05] text-center">
                    <div><p class="text-[10px] uppercase tracking-wide opacity-50 mb-1">Avg Uptime</p><p class="font-bold text-emerald-600 dark:text-emerald-400 text-sm tabular-nums animate-pulse" id="avg-uptime">—</p></div>
                    <div><p class="text-[10px] uppercase tracking-wide opacity-50 mb-1">Downtime</p><p class="font-bold text-red-600 dark:text-red-400 text-sm tabular-nums animate-pulse" id="downtime">—</p></div>
                    <div><p class="text-[10px] uppercase tracking-wide opacity-50 mb-1">Incidents</p><p class="font-bold text-sm tabular-nums animate-pulse" id="incidents">—</p></div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5 flex-grow">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold tracking-tight">Recent Activity</h3>
                    <span class="text-[11px] opacity-50">live</span>
                </div>
                <ul id="activity-list" class="space-y-3 max-h-[280px] overflow-y-auto pr-1">
                    <li class="text-xs opacity-50">Loading…</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== 4. BOTTOM WIDGETS ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <h3 class="font-bold tracking-tight mb-3">Status Distribution</h3>
            <div id="chart-distribution"><div class="rounded-xl bg-black/[.04] dark:bg-white/[.04] animate-pulse" style="height:230px"></div></div>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-bold tracking-tight">Top Router by CPU</h3>
                <span class="text-[11px] opacity-50">top 5</span>
            </div>
            <div id="chart-topcpu"><div class="rounded-xl bg-black/[.04] dark:bg-white/[.04] animate-pulse" style="height:230px"></div></div>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold tracking-tight">System Health</h3>
                <span class="text-[11px] opacity-50" title="MIVO application component health">info</span>
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
                    <span class="text-sm font-semibold opacity-40" title="Not available — run backup from Settings">—</span>
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
                <h3 id="router-modal-title" class="text-2xl font-bold tracking-tight">Add Router</h3>
                <p class="text-xs opacity-50 mt-1">RouterOS API Connection</p>
            </div>
            <button type="button" data-close-modal aria-label="Close"
                class="w-8 h-8 rounded-lg inline-flex items-center justify-center hover:bg-black/[.05] dark:hover:bg-white/[.06] transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <form id="router-form" class="space-y-4" novalidate>
            <input type="hidden" name="id" id="rf-id">

            <div class="flex items-center gap-3 pt-1">
                <span class="text-[13px] font-bold uppercase tracking-[0.14em] opacity-90">Connection</span>
                <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
            </div>

            <div>
                <label for="rf-sessname" class="block text-[13px] font-semibold mb-2">Session name *</label>
                <div class="relative">
                    <i data-lucide="tag" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                    <input name="sessname" id="rf-sessname" required placeholder="e.g. router-jakarta-1"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <p class="hidden text-xs text-red-600 mt-1.5" data-err="sessname"></p>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <div class="flex-1 min-w-0">
                    <label for="rf-ipmik" class="block text-[13px] font-semibold mb-2">IP address *</label>
                    <div class="relative">
                        <i data-lucide="globe" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <input name="ipmik" id="rf-ipmik" required placeholder="192.168.88.1"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    </div>
                    <p class="hidden text-xs text-red-600 mt-1.5" data-err="ipmik"></p>
                </div>
                <div class="w-full sm:w-36 shrink-0">
                    <label for="rf-port" class="block text-[13px] font-semibold mb-2">API port *</label>
                    <input type="number" name="port" id="rf-port" min="1" max="65535" value="8728"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <div class="w-full sm:w-auto shrink-0">
                    <label for="rf-ssl" class="flex items-center gap-1 text-[13px] font-semibold mb-2">
                        <span>SSL</span>
                        <span class="relative group/tip inline-flex items-center cursor-help" tabindex="0">
                            <i data-lucide="help-circle" class="w-3.5 h-3.5 opacity-60 hover:opacity-100 transition-opacity"></i>
                            <span class="pointer-events-none absolute right-0 top-full mt-2 w-64 normal-case tracking-normal rounded-lg bg-[#1a1c19] dark:bg-black text-left text-white text-[11px] leading-relaxed p-3 opacity-0 group-hover/tip:opacity-100 group-focus-within/tip:opacity-100 transition-opacity z-30 shadow-xl">
                                <p class="font-bold mb-1.5">API service &amp; port</p>
                                <p class="opacity-80 mb-2">Match this switch with the API service listed under <span class="font-semibold">IP &rarr; Services</span> on your router:</p>
                                <div class="space-y-1">
                                    <p><span class="font-semibold">api-ssl</span> enabled &rarr; set <span class="font-semibold">ON</span> <span class="opacity-60">(port 8729)</span></p>
                                    <p>plain <span class="font-semibold">api</span> &rarr; keep <span class="font-semibold">OFF</span> <span class="opacity-60">(port 8728)</span></p>
                                </div>
                                <span class="absolute -top-2 right-1 border-4 border-transparent border-b-[#1a1c19] dark:border-b-black"></span>
                            </span>
                        </span>
                    </label>
                    <div class="h-11 flex items-center">
                        <label class="relative inline-flex items-center cursor-pointer select-none">
                            <input type="checkbox" name="ssl" id="rf-ssl" value="1" class="sr-only peer">
                            <span class="block w-10 h-[22px] rounded-full bg-black/[.15] dark:bg-white/[.15] peer-checked:bg-[#5f7f67] relative transition-colors after:content-[''] after:absolute after:top-[3px] after:left-[3px] after:w-4 after:h-4 after:rounded-full after:bg-white after:shadow-sm after:transition-transform peer-checked:after:translate-x-[18px]"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="rf-usermik" class="block text-[13px] font-semibold mb-2">Username *</label>
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <input name="usermik" id="rf-usermik" required placeholder="admin"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    </div>
                </div>
                <div>
                    <label for="rf-passmik" class="block text-[13px] font-semibold mb-2">Password <span class="normal-case font-normal" id="rf-pass-hint"></span></label>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <input type="password" name="passmik" id="rf-passmik" autocomplete="new-password" placeholder="••••••••"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-11 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                        <button type="button" id="rf-pass-eye" aria-label="Toggle password visibility"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 w-7 h-7 rounded-md inline-flex items-center justify-center opacity-40 hover:opacity-80 hover:bg-black/[.05] dark:hover:bg-white/[.06] transition">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <span class="text-[13px] font-bold uppercase tracking-[0.14em] opacity-90">Hotspot</span>
                <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="rf-hotspotname" class="block text-[13px] font-semibold mb-2">Hotspot name *</label>
                    <div class="relative">
                        <i data-lucide="share-2" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <input name="hotspotname" id="rf-hotspotname" required placeholder="My Hotspot ID"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    </div>
                </div>
                <div>
                    <label for="rf-dnsname" class="block text-[13px] font-semibold mb-2">DNS name *</label>
                    <div class="relative">
                        <i data-lucide="globe" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                        <input name="dnsname" id="rf-dnsname" required placeholder="hotspot.net"
                            class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <span class="text-[13px] font-bold uppercase tracking-[0.14em] opacity-90">Connection Test</span>
                <span class="h-px flex-1 bg-black/[.06] dark:bg-white/[.06]"></span>
            </div>

            <div class="flex items-center">
                <button type="button" id="rf-test-btn"
                    class="h-11 px-4 rounded-xl border border-[#5f7f67]/40 text-[#47614d] dark:text-[#92aa96] text-xs font-bold uppercase tracking-tight hover:bg-[#5f7f67]/10 transition-colors inline-flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap shrink-0">
                    <i data-lucide="zap" class="w-3.5 h-3.5"></i><span id="rf-test-label">Test Connection</span>
                </button>
            </div>

            <div id="rf-test-status" class="hidden"></div>

            <div>
                <label for="rf-iface" class="block text-[13px] font-semibold mb-2">Traffic interface *</label>
                <div class="relative">
                    <i data-lucide="network" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
                    <select name="iface" id="rf-iface" required disabled
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 pl-10 pr-9 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition disabled:opacity-60 appearance-none">
                        <option value="">— Run Test Connection —</option>
                    </select>
                    <i data-lucide="chevron-down" class="absolute right-3.5 top-1/2 -translate-y-1/2 w-4 h-4 opacity-40 pointer-events-none"></i>
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

<script src="/assets/js/vendor/apexcharts.min.js" defer></script>
<script>
window.whenReady(function () {
    'use strict';

    // Bereskan seluruh instance ApexCharts saat meninggalkan halaman via SPA
    // (tanpa ini, timer chart yatim terus mengukur DOM yang sudah lepas —
    //  sumber error console "width: NaN" / "translate(NaN, 0)").
    // Sweep registry ditangani cleanupSession() di layout sidebar.
    window.__kaiarasaSessionCleanup = null;
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
        opts.chart = Object.assign({ width: '100%', animations: { enabled: false }, fontFamily: 'inherit', foreColor: labelColor(),
            toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent' }, opts.chart || {});
        return opts;
    };

    /* ================= router table ================= */
    var PAGE_SIZE = 25;
    var state = { routers: [], page: 1, query: '', filter: '', checkedAt: null, loading: true };
    var grid = document.getElementById('table-scroll');

    // "3w1d02:11:45" / "22d3h14m" / "5h12m30s" -> "22d 3h" / "5h 12m" / "43m"
    function fmtUptime(u) {
        if (!u) return '-';
        var s = String(u), w = 0, d = 0, h = 0, m = 0, mt;
        var mw = s.match(/(\d+)w/); if (mw) w = +mw[1];
        var md = s.match(/(\d+)d/); if (md) d = +md[1];
        var mh = s.match(/(\d+)h/); if (mh) h = +mh[1];
        var mm = s.match(/(\d+)m/); if (mm) m = +mm[1];
        mt = s.match(/(\d{1,4}):(\d{2})(?::\d{2})?$/); if (!mh && mt) { h = +mt[1]; m = +mt[2]; }
        var totalH = w * 168 + d * 24 + h;
        if (totalH >= 24) {
            var days = Math.floor(totalH / 24);
            return days + 'd' + (totalH % 24 ? ' ' + (totalH % 24) + 'h' : '');
        }
        if (totalH >= 1) return totalH + 'h' + (m ? ' ' + m + 'm' : '');
        return m + 'm';
    }

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
    // Bar resource mini berlabel: CPU & MEM ditumpuk dalam satu sel
    function resBar(label, v) {
        var color = v > 80 ? 'bg-red-500' : (v > 50 ? 'bg-amber-500' : 'bg-emerald-500');
        return '<div class="flex items-center gap-1.5">'
             + '<span class="text-[10px] font-semibold opacity-50 w-7 shrink-0">'+label+'</span>'
             + '<span class="tabular-nums text-[11px] w-8 text-right">'+v+'%</span>'
             + '<div class="w-20 h-2 rounded-full bg-black/[.15] dark:bg-white/[.18] overflow-hidden">'
             + '<div class="h-full rounded-full '+color+'" style="width:'+Math.max(v > 0 ? 8 : 2, Math.min(100, v))+'%"></div></div></div>';
    }
    function rowHTML(r) {
        var metrics = r.status === 'online'
            ? {
                cpu: '<div class="space-y-1 py-0.5">'
                    + resBar('CPU', r.cpu_load ?? 0)
                    + resBar('MEM', r.mem_load ?? 0)
                    + '</div>',
                up: fmtUptime(r.uptime), seen: relTime(r.last_seen)
            }
            : { cpu: '<span class="opacity-40">-</span>', up: '<span class="opacity-40">-</span>',
                seen: r.last_seen ? relTime(r.last_seen) : '<span class="opacity-40">'+esc(r.error || '-')+'</span>' };
        var initial = esc((r.session_name || '?').slice(0, 2).toUpperCase());
        // Baris 2: tipe board (fallback hotspot/lokasi), baris 3: versi RouterOS
        var typeLine = r.board_name ? esc(r.board_name) : esc(r.hotspot_name || r.location || '');
        var osLine = r.os_version
            ? '<p class="text-[11px] font-medium text-[#47614d] dark:text-[#92aa96]">RouterOS '+esc(r.os_version)+'</p>'
            : '';
        return '<tr class="border-t border-black/[.05] dark:border-white/[.05] hover:bg-[#5f7f67]/[.04] transition-colors" data-session="'+esc(r.session_name)+'">'
            + '<td class="px-4 py-3">'+badge(r.status)+'</td>'
            + '<td class="px-4 py-3"><div class="flex items-center gap-3">'
            +   '<span class="w-8 h-8 rounded-lg bg-[#92aa96]/20 text-[#47614d] dark:text-[#92aa96] text-[11px] font-bold flex items-center justify-center shrink-0">'+initial+'</span>'
            +   '<div><p class="font-semibold text-[13px] leading-tight">'+esc(r.session_name)+'</p>'
            +   '<p class="text-[11px] opacity-50 truncate max-w-[220px]">'+typeLine+'</p>'
            +   osLine
            +   '</div></div></td>'
            + '<td class="px-4 py-3 font-mono text-xs">'+esc(r.ip_address)+'</td>'
            + '<td class="px-4 py-3">'+metrics.cpu+'</td>'
            + '<td class="px-4 py-3 text-xs tabular-nums whitespace-nowrap">'+metrics.up+'</td>'
            + '<td class="px-4 py-3 text-xs whitespace-nowrap">'+metrics.seen+'</td>'
            + '<td class="px-4 py-3 text-right relative">'
            +   '<button type="button" class="w-8 h-8 inline-flex items-center justify-center rounded-lg hover:bg-black/[.06] dark:hover:bg-white/[.08] transition-colors"'
            +   ' data-actions-menu data-session="'+esc(r.session_name)+'" aria-haspopup="true">'
            +   '<i data-lucide="more-vertical" class="w-4 h-4"></i></button>'
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
        if (state.loading) {
            grid.innerHTML = '<div class="py-8 space-y-2">'
                + Array.from({ length: 4 }, function () {
                    return '<div class="h-10 rounded-xl bg-black/[.04] dark:bg-white/[.05] animate-pulse"></div>';
                }).join('') + '</div>';
            return;
        }
        var list = filtered();
        var pages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
        if (state.page > pages) state.page = pages;
        var start = (state.page - 1) * PAGE_SIZE;
        var slice = list.slice(start, start + PAGE_SIZE);

        if (!state.routers.length) {
            grid.innerHTML = '<div class="py-12 text-center">'
                + '<p class="text-sm opacity-50 mb-4">No routers yet. Add your first router to start monitoring.</p>'
                + '<a href="/settings/add" class="inline-flex items-center gap-2 h-9 px-4 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[13px] font-semibold transition-colors"><i data-lucide="plus" class="w-4 h-4"></i>Add Router</a></div>';
        } else if (!list.length) {
            grid.innerHTML = '<div class="py-14 text-center text-sm opacity-50">No routers match your search or filter.</div>';
        } else {
            grid.innerHTML = '<table class="w-full text-sm"><thead><tr class="text-left text-[11px] uppercase tracking-wider text-[#47614d] dark:text-[#92aa96] bg-[#5f7f67]/[.06] border-b border-[#5f7f67]/20">'
                + '<th class="px-4 py-2.5 font-semibold">Status</th><th class="px-4 py-2.5 font-semibold">Router Name</th>'
                + '<th class="px-4 py-2.5 font-semibold">IP Address</th><th class="px-4 py-2.5 font-semibold">CPU / MEM</th>'
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
        // Hanya penghitung untuk donut Status Distribution (KPI cards dihapus)
        var total = routers.length;
        var online = routers.filter(function (r) { return r.status === 'online'; }).length;
        var offline = routers.filter(function (r) { return r.status === 'offline'; }).length;
        var connecting = routers.filter(function (r) { return r.status === 'connecting'; }).length;
        var error = routers.filter(function (r) { return r.status === 'error'; }).length;
        return { total: total, online: online, offline: offline, connecting: connecting, error: error };
    }

    /* ================= charts ================= */
    function chartAvail(series, agg) {
        ['avg-uptime','downtime','incidents'].forEach(function(i){var q=document.getElementById(i); if(q) q.classList.remove('animate-pulse');});
        document.getElementById('avg-uptime').textContent =
            (agg.avg_uptime_pct !== undefined ? Math.round(agg.avg_uptime_pct*10)/10 : '-') + '%';
        document.getElementById('downtime').textContent = fmtDown(agg.downtime_seconds);
        document.getElementById('incidents').textContent = agg.incidents ?? '-';
        var el = document.getElementById('chart-availability');
        if (charts.avail) charts.avail.destroy();
        if (!series.length) { el.innerHTML = '<p class="text-xs opacity-50 py-10 text-center">No history yet.</p>'; return; }
        el.innerHTML = '';
        el.innerHTML = '';
        charts.avail = window.kaiTrackChart(new ApexCharts(el, baseChart({
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
        el.innerHTML = '';
        charts.donut = window.kaiTrackChart(new ApexCharts(el, baseChart({
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
        if (!top.length) { el.innerHTML = '<p class="text-xs opacity-50 py-10 text-center">No CPU data available.</p>'; return; }
        el.innerHTML = '';
        charts.topcpu = window.kaiTrackChart(new ApexCharts(el, baseChart({
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
        if (!events.length) { ul.innerHTML = '<li class="text-xs opacity-50">No activity yet.</li>'; return; }
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
                var apply = function () {
                    state.routers = data.routers || [];
                    state.checkedAt = data.checked_at;
                    state.page = 1;
                    state.loading = false;
                    renderTable();
                    var counts = renderStats(state.routers);
                    chartDonut(counts);
                    chartTopCPU(state.routers);
                    grid.classList.remove('kai-pop');
                    void grid.offsetWidth; // restart animasi masuk
                    grid.classList.add('kai-pop');
                    grid.style.opacity = '1';
                    renderSysHealth(true);
                };
                grid.style.opacity = '0';
                setTimeout(apply, 160); // fade halus, tanpa pop-in mendadak
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
                state.loading = false;
                renderTable();
                grid.style.opacity = '1';
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

    var connOk = false;
    var testBtn = document.getElementById('rf-test-btn');
    var testStatus = document.getElementById('rf-test-status');
    function setTestStatus(kind, detail) {
        if (kind === 'idle') { testStatus.classList.add('hidden'); testStatus.innerHTML = ''; return; }
        var ok = kind === 'ok';
        testStatus.classList.remove('hidden');
        testStatus.className = 'flex items-start gap-3 rounded-xl border p-3.5 text-[13px] leading-relaxed ' + (ok
            ? 'border-[#5f7f67]/30 bg-[#5f7f67]/[.07] text-[#3c5443] dark:text-[#b7cbb9]'
            : 'border-red-500/30 bg-red-500/[.06] text-red-700 dark:text-red-400');
        testStatus.innerHTML =
            '<i data-lucide="' + (ok ? 'check-circle-2' : 'x-circle') + '" class="w-5 h-5 shrink-0"></i>' +
            '<div class="min-w-0"><p class="font-semibold">' + (ok ? 'Connection successful' : 'Connection failed') + '</p>' +
            (detail ? '<p class="opacity-70 mt-0.5">' + esc(detail) + '</p>' : '') + '</div>';
        if (window.lucide) lucide.createIcons();
    }
    function markUntested() {
        connOk = false;
        submitBtn.disabled = true;
        var sel = document.getElementById('rf-iface');
        sel.innerHTML = '<option value="">— Run Test Connection —</option>';
        sel.disabled = true;
        setTestStatus('idle');
    }
    ['rf-ipmik','rf-usermik','rf-passmik'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', markUntested);
    });
    testBtn.addEventListener('click', runConnectionTest);

    // SSL toggle → sinkronkan port default 8728 ⇄ 8729 (hanya jika port masih default)
    document.getElementById('rf-ssl').addEventListener('change', function () {
        var portEl = document.getElementById('rf-port');
        var p = parseInt(portEl.value, 10);
        if (this.checked && p === 8728) portEl.value = 8729;
        else if (!this.checked && p === 8729) portEl.value = 8728;
    });

    // toggle tampil/sembunyi password
    document.getElementById('rf-pass-eye').addEventListener('click', function () {
        var inp = document.getElementById('rf-passmik');
        var show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        this.innerHTML = '<i data-lucide="' + (show ? 'eye-off' : 'eye') + '" class="w-4 h-4"></i>';
        if (window.lucide) lucide.createIcons();
    });
    function runConnectionTest() {
        var ip = document.getElementById('rf-ipmik').value.trim();
        var user = document.getElementById('rf-usermik').value.trim();
        var pass = document.getElementById('rf-passmik').value;
        var id = document.getElementById('rf-id').value || null; // edit: fallback password tersimpan
        if (!ip || !user) {
            setTestStatus('err', 'Enter IP Address and Username first.');
            return;
        }
        testBtn.disabled = true;
        document.getElementById('rf-test-label').textContent = 'Testing…';
        var portVal = parseInt(document.getElementById('rf-port').value, 10) || 8728;
        var sslVal = document.getElementById('rf-ssl').checked;
        fetch('/api/router/interfaces', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ ip: ip, user: user, password: pass, id: id, port: portVal, ssl: sslVal }),
        })
        .then(function (res) { return res.json().then(function (j) { return { ok: res.ok, j: j }; }); })
        .then(function (out) {
            if (out.ok && Array.isArray(out.j.interfaces) && out.j.interfaces.length) {
                var sel = document.getElementById('rf-iface');
                sel.innerHTML = '';
                out.j.interfaces.forEach(function (nm) {
                    var o = document.createElement('option');
                    o.value = nm; o.textContent = nm;
                    sel.appendChild(o);
                });
                sel.disabled = false;
                connOk = true;
                submitBtn.disabled = false;
                setTestStatus('ok', sel.options.length + ' interfaces detected \u2022 API port ' + portVal + (sslVal ? ' (SSL)' : '') + '. Choose a Traffic Interface, then save.');
                testBtn.disabled = false;
                document.getElementById('rf-test-label').textContent = 'Re-test';
            } else {
                failTest(out.j && out.j.error);
            }
        })
        .catch(function () { failTest(); });
    }
    function failTest(detail) {
        testBtn.disabled = false;
        document.getElementById('rf-test-label').textContent = 'Test Connection';
        setTestStatus('err', 'Connection failed. ' + (detail || 'Check IP, user, password, API Port, or SSL.'));
    }

    function openModal(mode, r) {
        form.reset();
        document.getElementById('rf-passmik').type = 'password';
        document.getElementById('rf-pass-eye').innerHTML = '<i data-lucide="eye" class="w-4 h-4"></i>';
        markUntested();
        ['sessname','ipmik'].forEach(function (n) {
            var err = form.querySelector('[data-err="'+n+'"]');
            if (err) err.classList.add('hidden');
        });
        if (mode === 'edit') {
            document.getElementById('router-modal-title').textContent = 'Edit Router';
            document.getElementById('rf-submit-label').textContent = 'Save Changes';
            document.getElementById('rf-pass-hint').textContent = '(leave blank to keep current)';
            document.getElementById('rf-id').value = r.id;
            document.getElementById('rf-sessname').value = r.session_name;
            document.getElementById('rf-ipmik').value = r.ip_address;
            document.getElementById('rf-usermik').value = '';
            document.getElementById('rf-passmik').value = '';
            document.getElementById('rf-usermik').placeholder = r.username || 'admin';
            document.getElementById('rf-hotspotname').value = r.hotspot_name || '';
            document.getElementById('rf-dnsname').value = '';
            if (r.port) document.getElementById('rf-port').value = r.port;
            document.getElementById('rf-ssl').checked = !!parseInt(r.ssl, 10);
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

    /* ---- row actions: floating menu (portal ke body, anti terpotong overflow) ---- */
    var floatMenu = null;
    function closeFloatMenu() {
        if (!floatMenu) return;
        floatMenu.remove();
        floatMenu = null;
        document.removeEventListener('click', floatOutside, true);
        window.removeEventListener('scroll', closeFloatMenu, true);
        window.removeEventListener('resize', closeFloatMenu);
    }
    function floatOutside(e) {
        if (floatMenu && !floatMenu.contains(e.target) && !e.target.closest('[data-actions-menu]')) closeFloatMenu();
    }
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-actions-menu]');
        if (!btn) return;
        e.stopPropagation();
        closeFloatMenu();
        var sess = btn.getAttribute('data-session');
        var r = state.routers.find(function (x) { return x.session_name === sess; });
        if (!r) return;
        var rect = btn.getBoundingClientRect();
        var menu = document.createElement('div');
        menu.style.cssText = 'position:fixed;z-index:400;width:176px;border-radius:12px;overflow:hidden;text-align:left;'
            + 'border:1px solid ' + (isDark() ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.08)') + ';'
            + 'background:' + (isDark() ? '#1a1c19' : '#ffffff') + ';box-shadow:0 10px 30px rgba(0,0,0,.18);';
        menu.innerHTML =
            '<button type="button" class="w-full px-4 py-2.5 text-xs font-medium text-left hover:bg-black/[.04] dark:hover:bg-white/[.05] flex items-center gap-2.5" data-act="open"><i data-lucide="external-link" class="w-3.5 h-3.5 opacity-60"></i>Open Dashboard</button>'
          + '<button type="button" class="w-full px-4 py-2.5 text-xs font-medium text-left hover:bg-black/[.04] dark:hover:bg-white/[.05] flex items-center gap-2.5" data-act="edit"><i data-lucide="pencil" class="w-3.5 h-3.5 opacity-60"></i>Edit Router</button>'
          + '<div style="border-top:1px solid rgba(128,128,128,.15)"></div>'
          + '<button type="button" class="w-full px-4 py-2.5 text-xs font-semibold text-left text-red-600 hover:bg-red-500/[.07] flex items-center gap-2.5" data-act="delete"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i>Delete</button>';
        menu.addEventListener('click', function (ev) {
            var actBtn = ev.target.closest('[data-act]');
            if (!actBtn) return;
            ev.stopPropagation();
            var act = actBtn.getAttribute('data-act');
            var snapshot = JSON.parse(JSON.stringify(r));
            closeFloatMenu();
            if (act === 'open') {
                if (window.kaiRouteLoading) window.kaiRouteLoading(true, 'Opening ' + (r.hotspot_name || r.session_name) + '\u2026');
                location.href = '/' + r.session_name + '/dashboard';
            }
            if (act === 'edit') openModal('edit', snapshot);
            if (act === 'delete') deleteRouter(snapshot);
        });
        document.body.appendChild(menu);
        // posisi: bawah tombol, rata kanan dgn tombol; jepit agar tak keluar viewport
        var mw = menu.offsetWidth || 176;
        var left = Math.min(rect.right - mw, window.innerWidth - mw - 8);
        menu.style.top = Math.min(rect.bottom + 6, window.innerHeight - menu.offsetHeight - 8) + 'px';
        menu.style.left = Math.max(8, left) + 'px';
        if (window.lucide) lucide.createIcons();
        document.addEventListener('click', floatOutside, true);
        window.addEventListener('scroll', closeFloatMenu, true);
        window.addEventListener('resize', closeFloatMenu);
        floatMenu = menu;
    });

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
            Kaiarasa.confirm('Delete Router?', 'Router "'+r.session_name+'" and its session will be permanently deleted.', 'Delete', 'Cancel')
                .then(function (ok) { if (!ok) return; doDelete().then(function (j) {
                    if (window.Kaiarasa) Kaiarasa.toast(j.success ? 'success' : 'error', j.message);
                    load(true);
                }); });
        } else if (window.Swal) {
            Swal.fire({ title: 'Delete Router?', text: r.session_name, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc2626', confirmButtonText: 'Delete',
                customClass: { popup: 'swal2-premium-card swal2-confirm-sm' } })
                .then(function (res) { if (res.isConfirmed) doDelete().then(function (j) {
                    if (window.Kaiarasa) Kaiarasa.toast(j.success ? 'success' : 'error', j.message);
                    load(true);
                }); });
        } else {
            if (confirm('Delete router '+r.session_name+'?')) doDelete().then(function () { load(true); });
        }
    }

    /* ---- add / edit submit via fetch JSON ---- */
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!connOk) {
            setTestStatus('err', 'A successful Test Connection is required before saving.');
            return;
        }
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
                if (window.Kaiarasa) Kaiarasa.toast('success', out.json.message);
                load(true);
            } else {
                if (window.Kaiarasa) Kaiarasa.toast('error', out.json.message || 'Please check your data.');
            }
        })
        .catch(function () {
            if (window.Kaiarasa) Kaiarasa.toast('error', 'Network error');
        })
        .finally(function () {
            submitBtn.disabled = false;
            document.getElementById('rf-submit-label').textContent =
                form.dataset.mode === 'edit' ? 'Save Changes' : 'Save Router';
        });
    });

    // Expose untuk handler global navbar (tombol Add Router dari halaman mana pun)
    window.__openRouterModal = openModal;
    // Auto-open bila diminta dari halaman lain (flag oleh navbar global handler)
    if (sessionStorage.getItem('mivoOpenAddRouter') === '1') {
        sessionStorage.removeItem('mivoOpenAddRouter');
        openModal('add', null);
    }

    /* ================= wiring ================= */
    document.getElementById('btn-refresh').addEventListener('click', function () { load(true); });
    document.getElementById('table-search').addEventListener('input', function () {
        state.query = this.value; state.page = 1; renderTable();
    });
    document.getElementById('status-filter').addEventListener('change', function () {
        state.filter = this.value; state.page = 1; renderTable();
    });
    // Catatan: klik baris TIDAK membuka dashboard — hanya lewat aksi ⋮ → Open Dashboard

    var REFETCH_MS = 60000;
    var pollTimer = setInterval(function () { if (!document.hidden) load(false); }, REFETCH_MS);
    window.addEventListener('pagehide', function () { if (pollTimer) clearInterval(pollTimer); });

    renderTable();          // skeleton awal (server-side str_repeat sudah diganti)
    load(false);
});
</script>

<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
