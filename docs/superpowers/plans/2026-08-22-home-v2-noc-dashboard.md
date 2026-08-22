# Home v2 (NOC Dashboard) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: superpowers:subagent-driven-development. Steps use `- [ ]` checkboxes.

**Goal:** Membangun ulang Home sesuai amandemen spec H1–H6 (mockup ui home.png): header + global search, 5 tile nav, 4 kartu statistik, tabel router (search/filter/pagination), kolom kanan (availability chart + activity), baris bawah (donut, top CPU, system health).

**Architecture:** Backend menambah ingatan historis (2 tabel SQLite + logging di RouterHealthService + 2 endpoint). Frontend = rewrite `home.php` (vanilla JS + ApexCharts vendored). Tanpa sidebar di home (header_main conditional).

**Tech:** PHP 8, SQLite, RouterOSAPI, ApexCharts (vendored, MIT), Tailwind utilities, vanilla JS.

**Catatan test:** project tanpa test infra — verifikasi manual (curl + browser + screenshot headless). Setelah tiap task mengubah class Tailwind baru: `npm run build`.

---

### Task 1: Migrasi tabel historis + logging probe

**Files:**
- Create: `app/Database/migrations/00x_probe_logs.sql` (ikuti pola migrasi existing — cek dulu folder app/Database)
- Modify: `app/Services/RouterHealthService.php`

Langkah:
- [ ] Cek pola migrasi existing (`ls app/Database/`, cara tabel dibuat)
- [ ] Tambah 2 tabel: `router_probe_logs(id, router_id, checked_at, status, cpu_load, uptime, response_ms)` dan `router_events(id, router_id, event_type, created_at)` + index `(router_id, checked_at)` / `(created_at)`
- [ ] Di `getHealth()`: setelah probe per router → INSERT probe_log (status, cpu_load, uptime, response_ms bila ada); deteksi transisi vs status sebelumnya (query event terakhir router) → INSERT `connected`/`went_offline` event; high_cpu (>80%) event maksimal 1× per 15 menit per router
- [ ] Retensi: hapus probe_logs >7 hari & events >7 hari, jalan sekali per siklus probe (probabilistik 1/10 agar murah)
- [ ] Logging historis dibungkus try/catch terpisah — kegagalan TIDAK boleh menggagalkan probing
- [ ] Verifikasi: curl /api/routers/health?refresh=1 2x → baris log & event transisi muncul di sqlite
- [ ] Commit `feat(home): probe logging historis + event transisi router`

### Task 2: Endpoint history & events + perluasan payload health

**Files:**
- Modify: `app/Services/RouterHealthService.php`, `app/Controllers/RouterHealthController.php`, `routes/web.php`

- [ ] `getHistory(int $hours = 24)`: bucket per jam dari probe_logs → `[{hour: 'HH:00', availability: 96.5}, ...]` + `avg_uptime_pct`, `downtime_seconds` (akumulasi durasi offline), `incidents` (hitung transisi offline)
- [ ] `getEvents(int $limit = 10)`: join router_events + routers (nama) → `[{router_name, event_type, created_at}]`
- [ ] Payload health per router tambah: `last_seen` (MAX checked_at dgn status online), `location` (dari kolom description)
- [ ] Route: `GET /api/routers/history`, `GET /api/routers/events` (grup auth)
- [ ] Verifikasi: curl ketiga endpoint (bentuk JSON benar), commit `feat(home): endpoint history & events + payload last_seen`

### Task 3: Vendor ApexCharts

**Files:**
- Create: `public/assets/js/vendor/apexcharts.min.js`

- [ ] Download dist resmi: `curl -L https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js -o public/assets/js/vendor/apexcharts.min.js` (fallback: npm pack apexcharts bila curl gagal)
- [ ] Verifikasi file >100KB & berisi string "ApexCharts"; commit `chore(home): vendor apexcharts.min.js`

### Task 4: Header home tanpa sidebar + global search

**Files:**
- Modify: `app/Views/layouts/header_main.php`

- [ ] Conditional: saat `$isHome` (REQUEST_URI === '/' atau path tanpa sub-path) → JANGAN include sidebar_session; wrapper konten full-width; tampilkan search bar global di header (input + ikon) dan slot tombol Add Router (markup dibaca home.php via id/class)
- [ ] Halaman session lain: perilaku existing tidak berubah
- [ ] Verifikasi: render `/` tanpa sidebar; render `/{session}/dashboard` tetap ada sidebar; `npm run build`; commit

### Task 5: Rewrite home.php (NOC dashboard)

**Files:**
- Rewrite: `app/Views/home.php` (besar, ±600 baris — ikuti mockup ui home.png + spec H1)

Struktur wajib (urut): header slot search/Add Router · 5 tile nav (Network Overview aktif hijau solid; Routers → /settings; Logs & Alerts = badge "Coming Soon" + non-aktif; Settings → /settings) · 4 kartu statistik (Total/Online/Offline/Connecting, % relatif, ikon lingkaran, menu ⋮ dekoratif) · grid utama 2 kolom (kiri lebar: tabel; kanan: availability chart + recent activity) · baris bawah 3 widget (donut, top CPU, system health).

- [ ] Tabel: kolom Status/Router Name(+location)/IP/CPU(bar)/Uptime/Last Seen/Actions(tombol Open); search client-side (#21); filter status dropdown (#22); pagination 25/halaman (#23); refresh manual + auto 60s; state Connecting saat fetch berjalan (#H2)
- [ ] ApexCharts: area availability (dari /history, gradient sage), donut distribution (total di tengah), horizontal bars top-CPU (5 teratas)
- [ ] Recent Activity list dari /events (ikon per event_type, relative time "2m ago")
- [ ] System Health: API Status (fetch /api/routers/health sukses=Healthy), Database (cek sederhana via health payload), Last Backup (hardcode "—" dulu, data backup belum ada → catat deferral)
- [ ] Semua state UI States relevan: skeleton (#2), stale (#4), error fetch (#28 retry), empty (#Empty)
- [ ] `npm run build` + verifikasi render headless (screenshot 1400px & 390px) + commit

### Task 6: Verifikasi akhir

- [ ] Screenshot desktop + mobile, cek tidak ada horizontal overflow
- [ ] Alur: router online/offline → badge & statistik & donut konsisten; search/filter/pagination berfungsi; event muncul setelah transisi
- [ ] Commit sisa + laporan
