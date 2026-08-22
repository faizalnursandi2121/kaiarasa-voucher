<?php
use App\Config\SiteConfig;

require_once ROOT.'/app/Views/layouts/header_main.php';
?>

<div class="w-full max-w-[1200px] mx-auto py-8 px-4 sm:px-6">

    <!-- Page header -->
    <div class="flex items-end justify-between mb-8">
        <div>
            <h1 class="text-[22px] font-bold tracking-tight">Routers</h1>
            <p class="text-sm opacity-50 mt-1">
                Monitor kondisi semua router · <span id="last-updated">memuat…</span>
            </p>
        </div>
        <button type="button" id="btn-refresh"
            class="inline-flex items-center gap-2 rounded-xl border border-black/10 dark:border-white/10 h-10 px-4 text-[13px] font-semibold hover:bg-black/[.03] dark:hover:bg-white/[.05] transition-colors">
            <i data-lucide="refresh-cw" class="w-4 h-4"></i> Refresh
        </button>
    </div>

    <!-- Fleet health strip -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.07] bg-white dark:bg-[#1a1c19] p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2">Total Router</p>
            <p class="text-2xl font-bold tabular-nums" id="stat-total">—</p>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.07] bg-white dark:bg-[#1a1c19] p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2">Online</p>
            <p class="text-2xl font-bold tabular-nums text-emerald-600 dark:text-emerald-400" id="stat-online">—</p>
        </div>
        <div class="rounded-2xl border border-black/[.07] dark:border-white/[.07] bg-white dark:bg-[#1a1c19] p-5">
            <p class="text-[11px] font-semibold uppercase tracking-wider opacity-50 mb-2">Offline</p>
            <p class="text-2xl font-bold tabular-nums text-red-600 dark:text-red-400" id="stat-offline">—</p>
        </div>
    </div>

    <!-- Grid kartu router -->
    <div id="router-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?= str_repeat(
            '<div class="rounded-2xl border border-black/[.07] dark:border-white/[.07] bg-white dark:bg-[#1a1c19] p-5 animate-pulse">'
            .'  <div class="h-4 w-1/3 rounded bg-black/10 dark:bg-white/10 mb-4"></div>'
            .'  <div class="h-3 w-2/3 rounded bg-black/10 dark:bg-white/10 mb-2"></div>'
            .'  <div class="h-3 w-1/2 rounded bg-black/10 dark:bg-white/10 mb-6"></div>'
            .'  <div class="h-9 w-full rounded-xl bg-black/10 dark:bg-white/10"></div>'
            .'</div>',
            3
        ) ?>
    </div>

    <!-- CTA Tambah Router -->
    <a href="/settings/add"
       class="mt-4 flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-black/15 dark:border-white/15 py-10 text-sm font-semibold opacity-60 hover:opacity-100 hover:border-[#92aa96] transition-all">
        <i data-lucide="plus" class="w-5 h-5 mb-2"></i>
        Tambah Router
    </a>
</div>

<script>
(function () {
    var grid = document.getElementById('router-grid');
    var refreshBtn = document.getElementById('btn-refresh');
    var REFRESH_MS = 60000;

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    function badge(status, error) {
        if (status === 'online') return '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Online</span>';
        if (status === 'error')  return '<span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-[11px] font-semibold text-amber-600 dark:text-amber-400"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>API Error</span>';
        return '<span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/10 px-2.5 py-1 text-[11px] font-semibold text-red-600 dark:text-red-400"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>Offline</span>';
    }

    function card(r) {
        var metrics = r.status === 'online'
            ? '<span>CPU '+esc(r.cpu_load ?? '-')+'%</span><span class="opacity-30">·</span><span>Uptime '+esc(r.uptime ?? '-')+'</span><span class="opacity-30">·</span><span>'+esc(r.active_users ?? 0)+' users</span>'
            : '<span class="opacity-60">'+esc(r.error || 'Tidak dapat dihubungi')+'</span>';

        return ''
        +'<div class="rounded-2xl border border-black/[.07] dark:border-white/[.07] bg-white dark:bg-[#1a1c19] p-5 flex flex-col">'
        +'  <div class="flex items-start justify-between mb-3">'+badge(r.status, r.error)
        +'    <span class="font-semibold text-sm">'+esc(r.session_name)+'</span></div>'
        +'  <p class="text-xs opacity-50 mb-4 font-mono">'+esc(r.ip_address)+(r.hotspot_name ? ' · '+esc(r.hotspot_name) : '')+'</p>'
        +'  <div class="flex items-center gap-2 text-xs opacity-80 min-h-[16px] mb-4">'+metrics+'</div>'
        +'  <a href="/'+esc(r.session_name)+'/dashboard"'
        +'     class="mt-auto inline-flex items-center justify-center rounded-xl h-9 text-[13px] font-semibold '
        +(r.status === 'online'
            ? 'bg-[#5f7f67] hover:bg-[#6b8b73] text-white'
            : 'border border-black/10 dark:border-white/10 opacity-60 pointer-events-none')
        +' transition-colors">Buka Dashboard</a>'
        +'</div>';
    }

    function render(data) {
        var routers = data.routers || [];
        var online = routers.filter(function (r) { return r.status === 'online'; }).length;
        var offline = routers.length - online;

        document.getElementById('stat-total').textContent = routers.length;
        document.getElementById('stat-online').textContent = online;
        document.getElementById('stat-offline').textContent = offline;

        grid.innerHTML = routers.length
            ? routers.map(card).join('')
            : '<div class="col-span-full text-center py-12 opacity-50 text-sm">Belum ada router. Tambahkan router pertama Anda di bawah.</div>';

        if (window.lucide) lucide.createIcons();

        var t = new Date((data.checked_at || Math.floor(Date.now()/1000)) * 1000);
        document.getElementById('last-updated').textContent =
            'terakhir diperbarui ' + t.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
    }

    function load(force) {
        refreshBtn.disabled = true;
        fetch('/api/routers/health' + (force ? '?refresh=1' : ''), { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.json(); })
            .then(render)
            .catch(function () {
                document.getElementById('last-updated').textContent = 'gagal memuat — coba Refresh';
            })
            .finally(function () { refreshBtn.disabled = false; });
    }

    refreshBtn.addEventListener('click', function () { load(true); });
    load(false);
    setInterval(function () { load(false); }, REFRESH_MS);
})();
</script>

<?php require_once ROOT.'/app/Views/layouts/footer_main.php'; ?>
