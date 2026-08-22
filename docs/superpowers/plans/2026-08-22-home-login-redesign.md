# Home & Login Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign halaman Login dan Home MIVO sesuai spec `docs/superpowers/specs/2026-08-22-home-login-redesign-design.md`, termasuk endpoint monitoring `/api/routers/health` dan dokumen design system `docs/design.md`.

**Architecture:** Custom PHP MVC tanpa build step untuk backend. View = PHP template dengan utility classes Tailwind (CDN/build existing). Monitoring home dilakukan dari browser via `fetch` polling ke endpoint health yang di-cache server-side 60 detik (pattern sama dengan captive portal `status.html`). Tidak ada asset Metronic yang disalin — hanya pattern.

**Tech Stack:** PHP 8+, SQLite (existing), RouterOSAPI library existing (`app/Libraries/RouterOSAPI.php`), Tailwind utility classes, vanilla JS + fetch.

**Catatan pengujian:** Project ini BELUM memiliki infrastruktur test otomatis (tidak ada PHPUnit / folder tests). Setiap task menggunakan langkah verifikasi manual eksplisit (curl/browser + output terduga) sebagai ganti TDD. Jangan menghukum pelaksana karena tidak menulis unit test — verifikasi manual adalah standar project ini.

---

## File Structure

| File | Aksi | Tanggung jawab |
|---|---|---|
| `docs/design.md` | Create | Design system: tokens, anti-slop rules, anatomi komponen |
| `app/Services/RouterHealthService.php` | Create | Probe paralel-logis + cache TTL semua router |
| `app/Controllers/RouterHealthController.php` | Create | Endpoint JSON `GET /api/routers/health` |
| `routes/web.php` | Modify | Registrasi route health dalam grup auth |
| `app/Views/home.php` | Rewrite | Fleet monitor view (strip + kartu router + polling JS) |
| `app/Views/login.php` | Rewrite | Split layout sage-deep / card putih |
| `app/Views/layouts/header_main.php` | Modify | Hapus background titik-titik, restyle ringan |

---

### Task 1: docs/design.md — Design System Document

**Files:**
- Create: `docs/design.md`

- [ ] **Step 1: Tulis dokumen design system**

Buat `docs/design.md` dengan konten berikut (lengkap, bukan kerangka):

```markdown
# MIVO Design System

Sumber kebenaran pattern UI MIVO. Setiap halaman baru / komponen baru WAJIB
mengikuti dokumen ini. Referensi proporsi: Metronic demo18 (pola saja, tanpa asset).

## 1. Warna

### Accent (brand)
| Token | Hex | Pemakaian |
|---|---|---|
| accent | #5f7f67 | tombol primer, link aktif, indikator online |
| accent-light | #92aa96 | hover state, highlight sekunder |

### Netral (abu-abu hangat)
| Token | Hex | Pemakaian |
|---|---|---|
| ink | #1c2420 | teks utama |
| ink-75 | rgba(28,36,32,.75) | teks sekunder |
| ink-50 | rgba(28,36,32,.5) | teks tersier/placeholder |
| surface | #ffffff | permukaan kartu (light) |
| bg | #fafaf8 | latar halaman (light) |
| border | rgba(28,36,32,.10) | border kartu/input |

### Semantik
| Makna | Light | Dark |
|---|---|---|
| success/online | #10b981 | #34d399 |
| warning/error-api | #f59e0b | #fbbf24 |
| danger/offline | #dc2626 | #ef4444 |

### Dark mode
Aktif via class `dark` pada `<html>` (mekanisme existing: localStorage.theme).
Semua warna netral dibalik; accent tetap sama.

## 2. Tipografi

| Peran | Font | Aturan |
|---|---|---|
| UI/data | Inter | body, form, tabel, angka, tombol |
| Display | Georgia italic | HANYA tagline/judul hero. DILARANG di komponen data |

Skala: h1 22px/600 · h2 17px/600 · body 13px/400 · label 11px/600 uppercase tracking .06em · micro 10px

## 3. Spasi & Bentuk

- Spacing: kelipatan 4px
- Radius: card 16px · input/button 12px · badge 999px
- Border default 1px solid var(--border)
- Elevation maksimal DUA level: (1) border saja, (2) shadow lembut `0 8px 24px rgba(0,0,0,.14)`

## 4. Aturan Anti-Slop (wajib)

1. Tanpa gradient dekoratif.
2. Tanpa glow/neon shadow.
3. Icon Lucide stroke-width 2px, ukuran 16/18/20px.
4. Serif italic tidak boleh menyentuh komponen data (tabel, form, angka).
5. Satu accent color; success/warning/danger hanya untuk makna fungsional.
6. Tanpa animasi masuk >400ms; transisi interaksi 150–200ms ease.

## 5. Komponen Inti

### Card
```
.card-ds { bg surface; border 1px border; radius 16px; padding 20px; }
padding kartu kecil: 14–16px.
```
Header kartu: title kiri (13px/600), action kanan (icon button ghost).

### Button
| Varian | Style |
|---|---|
| primary | bg accent, teks putih, radius 12px, h-40px |
| secondary | bg transparan, border border-color, teks ink |
| ghost (icon) | tanpa bg, hover bg rgba(ink,.05) |

### Input
```
bg rgba(ink,.04); border 1px border; radius 12px; h-44px; padding-x 14px;
focus: border accent + ring 3px rgba(95,127,103,.12)
label: 11px/600 uppercase di atas input, margin-bottom 8px
```

### Badge Status
```
badge-online   : dot #10b981 + teks, bg rgba(16,185,129,.10), radius 999
badge-offline  : dot #dc2626, bg rgba(220,38,38,.08)
badge-warning  : dot #f59e0b, bg rgba(245,158,11,.10)
ukuran: font 11px/600, padding 4px 10px
```

### Table
Header: 11px/600 uppercase ink-50, border-bottom. Baris: 13px, hover bg rgba(ink,.03).

## 6. Layout Global

- Header app: h-60px, logo kiri, action kanan (theme toggle, user menu)
- Sidebar: w-240px, item menu h-38px radius 10px, item aktif bg accent-light/.15
- Konten max-w-1200px center, padding 24px

## 7. Mapping Tailwind (cheatsheet)

| Kebutuhan | Class |
|---|---|
| Card | `rounded-2xl border border-black/[.07] bg-white dark:bg-[#1a1c19] p-5` |
| Primary btn | `bg-[#5f7f67] hover:bg-[#6b8b73] text-white rounded-xl h-10 px-4 text-[13px] font-semibold` |
| Badge online | `inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-600` |
| Label form | `block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2` |
```

- [ ] **Step 2: Verifikasi**

Run: `ls -la docs/design.md && head -20 docs/design.md`
Expected: file ada, isi terbaca.

- [ ] **Step 3: Commit**

```bash
git add docs/design.md
git commit -m "docs(design): tambah design system MIVO (tokens, komponen, anti-slop)"
```

---

### Task 2: Service + Endpoint Health

**Files:**
- Create: `app/Services/RouterHealthService.php`
- Create: `app/Controllers/RouterHealthController.php`
- Modify: `routes/web.php` (grup `middleware => 'auth'`, dekat route `/`)

- [ ] **Step 1: Buat RouterHealthService**

Buat `app/Services/RouterHealthService.php`:

```php
<?php

namespace App\Services;

use App\Libraries\RouterOSAPI;
use App\Models\Config;

/**
 * Probe kondisi semua router + cache hasil TTL 60 detik.
 * Catatan: probe berurutan per router (PHP sinkron) dengan timeout pendek;
 * cache menjaga agar load home tetap instan.
 */
class RouterHealthService
{
    private const CACHE_TTL = 60; // detik

    public function getHealth(bool $forceRefresh = false): array
    {
        if (! $forceRefresh) {
            $cached = $this->readCache();
            if ($cached !== null) {
                return $cached;
            }
        }

        $results = [];
        foreach ((new Config)->getAllSessions() as $session) {
            $results[] = $this->probeRouter($session);
        }

        $payload = [
            'checked_at' => time(),
            'checked_at_iso' => date('c'),
            'routers' => $results,
        ];

        $this->writeCache($payload);

        return $payload;
    }

    private function probeRouter(array $session): array
    {
        $row = [
            'id' => (int) $session['id'],
            'session_name' => $session['session_name'],
            'hotspot_name' => $session['hotspot_name'] ?? '',
            'ip_address' => $session['ip_address'] ?? '',
            'quick_access' => (int) ($session['quick_access'] ?? 0),
            'status' => 'offline',
            'cpu_load' => null,
            'uptime' => null,
            'active_users' => null,
            'error' => null,
        ];

        try {
            $API = RouterOSAPI::fromSession($session);
            $API->attempts = 1;
            $API->timeout = 3;

            if (! $API->connect($session['ip_address'], $session['username'], $session['password'])) {
                $row['error'] = 'Connection failed';

                return $row;
            }

            $resource = $API->comm('/system/resource/print');
            $activeUsers = $API->comm('/ip/hotspot/active/print');
            $API->disconnect();

            $row['status'] = 'online';
            $row['cpu_load'] = isset($resource[0]['cpu-load']) ? (int) $resource[0]['cpu-load'] : null;
            $row['uptime'] = $resource[0]['uptime'] ?? null;
            $row['active_users'] = is_array($activeUsers) && ! isset($activeUsers['!trap']) ? count($activeUsers) : 0;
        } catch (\Throwable $e) {
            $row['status'] = 'error';
            $row['error'] = $e->getMessage();
        }

        return $row;
    }

    private function cachePath(): string
    {
        return sys_get_temp_dir().'/mivo-router-health.json';
    }

    private function readCache(): ?array
    {
        $file = $this->cachePath();
        if (! file_exists($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (! is_array($data) || empty($data['checked_at'])) {
            return null;
        }

        if (time() - (int) $data['checked_at'] > self::CACHE_TTL) {
            return null;
        }

        return $data;
    }

    private function writeCache(array $payload): void
    {
        @file_put_contents($this->cachePath(), json_encode($payload));
    }
}
```

- [ ] **Step 2: Buat Controller**

Buat `app/Controllers/RouterHealthController.php`:

```php
<?php

namespace App\Controllers;

use App\Services\RouterHealthService;

class RouterHealthController extends Controller
{
    public function index()
    {
        header('Content-Type: application/json');

        // ?refresh=1 memaksa probe ulang, abaikan cache
        $force = isset($_GET['refresh']);

        echo json_encode((new RouterHealthService)->getHealth($force));
    }
}
```

- [ ] **Step 3: Registrasi route**

Di `routes/web.php`, di DALAM grup `$router->group(['middleware' => 'auth'], ...)`,
tepat setelah baris `$router->get('/', [HomeController::class, 'index']);` tambahkan:

```php
    $router->get('/api/routers/health', [\App\Controllers\RouterHealthController::class, 'index']);
```

- [ ] **Step 4: Verifikasi manual**

```bash
# login dulu di browser untuk dapat session cookie, lalu dari terminal:
curl -s http://localhost:8000/api/routers/health | python3 -m json.tool
```

Expected: JSON dengan struktur `{"checked_at":..., "checked_at_iso":..., "routers":[{..."status":"offline"|"online"|"error"...}]}`.
Panggil 2x berturut-turut — panggilan kedua harus instan (<100ms, cache hit).
Tambahkan `?refresh=1` — harus probe ulang (lebih lambat).

- [ ] **Step 5: Commit**

```bash
git add app/Services/RouterHealthService.php app/Controllers/RouterHealthController.php routes/web.php
git commit -m "feat(home): endpoint /api/routers/health dengan cache TTL 60 detik"
```

---

### Task 3: Rewrite View Home (Fleet Monitor)

**Files:**
- Rewrite: `app/Views/home.php`

- [ ] **Step 1: Tulis view baru**

Ganti seluruh isi `app/Views/home.php` dengan:

```php
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
```

- [ ] **Step 2: Verifikasi manual**

Login ke app, buka `/`.
Expected: 3 kartu statistik terisi sesuai jumlah router di DB; kartu router tampil dengan
badge status benar (router mati → merah "Offline"); tombol "Buka Dashboard" nonaktif untuk
router offline; auto-refresh terjadi tiap 60 detik (lihat teks "terakhir diperbarui" berubah).

- [ ] **Step 3: Commit**

```bash
git add app/Views/home.php
git commit -m "feat(home): redesign home jadi pusat monitoring router"
```

---

### Task 4: Restyle Header Layout (hapus titik-titik)

**Files:**
- Modify: `app/Views/layouts/header_main.php` (baris ±89)

- [ ] **Step 1: Hapus div background titik-titik**

Hapus seluruh elemen ini (background dots SVG base64 yang dikeluhkan user):

```html
<div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,...')] dark:bg-[url('data:image/svg+xml;base64,...')] [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
```

Ganti dengan latar polos (atau hapus jika parent-nya purely dekoratif):

```html
<div class="absolute inset-0 bg-gradient-to-b from-black/[.02] to-transparent dark:from-white/[.02]"></div>
```

- [ ] **Step 2: Verifikasi manual**

Buka `/` dan `/settings`. Expected: tidak ada lagi pola titik-titik di area header/sidebar;
halaman tetap terbaca normal di light & dark mode.

- [ ] **Step 3: Commit**

```bash
git add app/Views/layouts/header_main.php
git commit -m "style(layout): hapus background titik-titik, ganti latar halus"
```

---

### Task 5: Rewrite View Login (Split Layout)

**Files:**
- Rewrite: `app/Views/login.php`

- [ ] **Step 1: Tulis view baru**

Ganti seluruh isi `app/Views/login.php` dengan (pertahankan mekanisme POST `/login`
dan pesan error existing dari controller — sesuaikan nama variabel error jika
controller memakai `$error`/flash, cek dulu `AuthController::showLogin`):

```php
<?php
$title = 'Kaiarasa Login';
include ROOT.'/app/Views/layouts/header_public.php';
?>

<main class="flex-grow flex flex-col lg:flex-row w-full">

    <!-- Panel kiri: brand (hilang/di-header tipis di mobile) -->
    <section class="lg:w-1/2 bg-[#5f7f67] text-white flex items-center justify-center py-10 lg:py-0">
        <div class="text-center lg:text-left px-8 max-w-md">
            <img src="/assets/img/logo-white.webp" alt="Kaiarasa" class="h-10 w-auto mx-auto lg:mx-0 mb-6">
            <h1 class="font-serif italic text-3xl lg:text-4xl leading-tight">
                Hotspot Voucher<br>Management
            </h1>
            <p class="text-white/70 text-sm mt-3 tracking-wide">MikroTik Hotspot Manager</p>
        </div>
    </section>

    <!-- Panel kanan: form -->
    <section class="lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
        <div class="w-full max-w-md rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-8 shadow-[0_8px_24px_rgba(0,0,0,.08)]">

            <h2 class="text-xl font-bold tracking-tight mb-1">Masuk ke MIVO</h2>
            <p class="text-[13px] opacity-50 mb-7">Kelola voucher dan router hotspot Anda.</p>

            <?php if (! empty($error)): ?>
            <div class="mb-5 flex items-start gap-2.5 rounded-xl bg-red-500/[.07] border border-red-500/20 px-3.5 py-3 text-[12.5px] text-red-600 dark:text-red-400">
                <i data-lucide="alert-circle" class="w-4 h-4 mt-px shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <?php endif; ?>

            <form action="/login" method="POST" class="space-y-4">
                <div>
                    <label for="login-username" class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">Username</label>
                    <input type="text" id="login-username" name="username" required autocomplete="username"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <div>
                    <label for="login-password" class="block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                        class="w-full h-11 rounded-xl bg-black/[.04] dark:bg-white/[.05] border border-black/10 dark:border-white/10 px-3.5 text-[14px] outline-none focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[#5f7f67]/20 transition">
                </div>
                <button type="submit"
                    class="w-full h-11 rounded-xl bg-[#5f7f67] hover:bg-[#6b8b73] text-white text-[13px] font-semibold transition-colors">
                    Sign In
                </button>
            </form>

            <p class="text-center text-[11px] opacity-40 mt-7">&copy; <?= date('Y') ?> Kaiarasa</p>
        </div>
    </section>
</main>

<?php require_once ROOT.'/app/Views/layouts/footer_public.php'; ?>
```

- [ ] **Step 2: Cek variabel error dari AuthController**

Run: `grep -n "function showLogin" -A 15 app/Controllers/AuthController.php`
Jika variabel error BUKAN `$error` (misal flash message), sesuaikan blok
`if (! empty($error))` di view dengan mekanisme yang benar. Jangan ubah controller.

- [ ] **Step 3: Verifikasi manual**

Logout, buka `/login`.
Expected: panel kiri hijau sage penuh dengan tagline serif italic "Hotspot Voucher Management",
card putih di kanan; login sukses mengarah ke `/`; password salah menampilkan error inline;
viewport sempit → panel kiri menjadi header tipis di atas form; dark mode konsisten.

- [ ] **Step 4: Commit**

```bash
git add app/Views/login.php
git commit -m "feat(login): redesign split layout sage-deep tanpa foto"
```

---

### Task 6: Verifikasi Akhir Menyeluruh

- [ ] **Step 1: Checklist visual light + dark**

Periksa kedua mode di: `/login`, `/` (dengan 0 router — kosongkan sementara via SQL
`UPDATE routers SET quick_access=0` TIDAK diperlukan; gunakan DB copy bila ragu),
`/` (dengan router online & offline), `/settings`.

- [ ] **Step 2: Checklist perilaku**

- Cache hit: dua request `/api/routers/health` beruntun, kedua <100ms
- `?refresh=1`: probe ulang nyata
- Polling: biarkan home terbuka 2 menit, teks "terakhir diperbarui" berubah 2x
- Router dimatikan saat home terbuka → poll berikutnya berubah jadi Offline merah

- [ ] **Step 3: Commit akhir bila ada sisa perubahan**

```bash
git add -A && git commit -m "chore(design): penyesuaian akhir pilot redesign login+home"
```
