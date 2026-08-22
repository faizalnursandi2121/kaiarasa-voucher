# Design Spec — Redesign Login & Home + DESIGN.md

**Tanggal:** 2026-08-22
**Status:** Disetujui (diskusi sesi live)
**Scope:** Pilot redesign — Login page + Home page + dokumen `docs/design.md`

---

## 1. Latar Belakang & Keputusan

MIVO (Hotspot Voucher Manager untuk MikroTik) akan diperbarui tampilannya. Setelah
diskusi, diputuskan pendekatan **redesign dengan refactor terarah**, diawali pilot
dua halaman agar pola desain teruji sebelum diperluas.

### Keputusan yang disepakati

| # | Keputusan | Rationale |
|---|---|---|
| D1 | Scope pilot: **Login + Home** | Hasil cepat, risiko kecil, jadi acuan halaman lain |
| D2 | Arah visual: **Hybrid** — dasar enterprise netral + sage green sebagai satu-satunya accent | Profesional tapi punya kepemilikan brand |
| D3 | Referensi: **Metronic demo18** sebagai *pattern reference saja* — tidak ada asset/CSS/JS Metronic disalin ke produksi | Lisensi aman; stack tetap Tailwind native |
| D4 | Chart memakai **ApexCharts atau Chart.js langsung** (MIT), bukan bundle Metronic | Library underlying bebas lisensi; MIVO sudah punya Chart.js — pilih satu, jangan dobel |
| D5 | Home = ** pusat monitoring router**, bukan ringkasan bisnis | Pemisahan tanggung jawab halaman yang jelas |
| D6 | Level monitoring home: **B** — status online/offline + uptime + CPU load + user hotspot aktif, cache TTL 60 detik | Cukup informatif tanpa berat; telemetri penuh tetap di dashboard per-session |
| D7 | Login = anatomi *creative* Metronic **tanpa foto**; panel kiri solid sage-deep | Nol request gambar → login instan |
| D8 | Tagline panel kiri: **"Hotspot Voucher Management"** + sub-teks "MikroTik Hotspot Manager" | Deskriptif dan natural |

### Yang eksplisit di luar scope

- Dashboard per-session (menyusul, mengikuti DESIGN.md)
- Widget bisnis di home (voucher terjual dll) — milik dashboard
- Global search di header — belum ada objek pencarian; bisa menyusul
- Perubahan flow autentikasi/endpoint login PHP yang existing
- Migrasi halaman settings/hotspot/reports ke pattern baru

---

## 2. Fondasi Token — inti `docs/design.md`

| Token | Nilai | Catatan |
|---|---|---|
| `accent` | `#5f7f67` (sage-deep) | semua aksi primer (tombol, link aktif) |
| `accent-light` | `#92aa96` (sage) | hover, highlight sekunder |
| Netral | skala abu-abu hangat: ink `#1c2420` → bg `#fafaf8` | konsisten dengan captive portal |
| Semantik | success/warning/danger netral standar | badge status router |
| Font UI | Inter | body, form, tabel, angka |
| Font display | Georgia italic | HANYA tagline/judul hero; dilarang di komponen data |
| Radius | card 16px · input 12px · button 12px | |
| Dark mode | atribut `data-theme="dark"` (pola setara `data-bs-theme` Metronic) | setiap token wajib punya pasangan light/dark |

### Aturan anti-slop (wajib di DESIGN.md)

1. Tanpa gradient dekoratif; overlay di atas foto boleh, gradient warna-warni tidak.
2. Maksimal dua level elevasi (border halus ATAU shadow lembut — bukan keduanya bertumpuk).
3. Icon Lucide stroke-width 2px konsisten.
4. Spacing grid kelipatan 4px.
5. Serif italic hanya untuk display text; komponen data 100% Inter.
6. Satu accent color — status semantik (hijau/kuning/merah) adalah pengecualian fungsional.

---

## 3. Login Page

### Anatomi

```
┌──────────────────────────┬─────────────────────┐
│ KIRI 50% (sage-deep      │ KANAN (bg netral    │
│ #5f7f67, solid)          │ halus)              │
│                          │  ┌───────────────┐  │
│  logo Kaiarasa           │  │ Card putih    │  │
│                          │  │ rounded-16    │  │
│  "Hotspot Voucher        │  │ Masuk ke MIVO │  │
│   Management"            │  │ [username]    │  │
│  (Georgia italic, putih) │  │ [password]    │  │
│  sub: MikroTik Hotspot   │  │ [Sign In]     │  │
│       Manager (kecil)    │  │ error inline  │  │
│                          │  └───────────────┘  │
│  © Kaiarasa              │                     │
└──────────────────────────┴─────────────────────┘
```

- Mobile (<lg): panel kiri menjadi header tipis (logo + tagline satu baris), form full-width.
- State: error inline dalam card (bukan alert terpisah), tombol dengan spinner saat submit.
- Tanpa foto, tanpa social login, tanpa ornamen tambahan.
- Flow POST login PHP existing tidak berubah — ini restyle view saja.

## 4. Home Page (`/`)

### Struktur

1. **Header** (pola ala Metronic): logo kiri · toggle theme · menu user kanan.
   Sidebar existing MIVO dipertahankan, di-restyle dengan token baru.
2. **Fleet health strip** — 3 kartu ringkas:
   `Total Router` · `Online` (badge hijau) · `Offline` (badge merah).
3. **Grid kartu router** (grid responsif, bukan tabel):

   ```
   ┌─────────────────────────────┐
   │ 🟢 kaiarasa-main        [⋯] │
   │ 10.5.50.1 · Hotspot Utama   │
   │ CPU 12% · Uptime 8d · 👥 23 │
   │ [Buka Dashboard]            │
   └─────────────────────────────┘
   ```

   State per kartu (wajib lengkap):
   - **Loading**: skeleton shimmer saat health belum tersedia
   - **Online**: badge hijau + metrik live
   - **Offline**: badge merah + "terakhir online <waktu>" bila diketahui
   - **Error API**: kuning + pesan error singkat dari RouterOSAPI

4. **Kartu "Tambah Router"** — kartu dashed di ujung grid, mengarah ke `/settings/add`.
5. **Auto-refresh**: polling fetch tiap 60 detik + indikator teks "Diperbarui HH:MM".

### Data yang ditampilkan per kartu (level B)

| Data | Sumber RouterOS |
|---|---|
| Online/offline | keberhasilan koneksi API |
| CPU load | `/system/resource` → `cpu-load` |
| Uptime | `/system/resource` → `uptime` |
| User aktif | `/ip hotspot active print` (count) |

## 5. Backend Scope

- Endpoint baru: `GET /api/routers/health`
  - Loop semua session **paralel**
  - Ambil `/system/resource` + count `/ip hotspot active`
  - **Cache TTL 60 detik** (file atau DB — ikuti mekanisme cache existing MIVO)
  - Per-router try/catch → gagal koneksi = `status: offline`, pesan error disimpan
- HomeController tetap melayani view; kartu mengambil health via fetch dari browser
  (pattern polling sama dengan yang dipakai `status.html` captive portal).
- Response JSON per router minimal:
  `{ id, session_name, hotspot_name, ip_address, status: online|offline|error,
     cpu_load, uptime, active_users, last_online?, error? }`

## 6. Chart (keputusan D4)

- Pilih **satu** library: ApexCharts (MIT) direkomendasikan untuk chart baru;
  Chart.js existing dievaluasi — jika sudah cukup untuk kebutuhan dashboard nanti,
  gunakan itu dan jangan tambahkan library kedua.
- Konfigurasi visual yang ditiru dari Metronic: grid line sangat halus, rounded bar,
  tooltip bersih, palet = token kita (sage + netral).
- Catatan: home level-B **belum butuh chart** — keputusan ini untuk dashboard per-session nanti.

## 7. Deliverables

1. `docs/design.md` — design system: tokens §2, aturan anti-slop, anatomi komponen inti
   (card, button, input, badge/status, table, header) dengan mapping class Tailwind.
2. View `app/Views/login.php` — restyle sesuai §3 (flow auth tidak berubah).
3. View `app/Views/home.php` — restructure sesuai §4.
4. Controller/endpoint health sesuai §5.
5. Layout global (`header_main.php`, sidebar) — restyle token, struktur tetap.

## 8. Testing / Verifikasi

- Login: sukses, password salah (error inline), mobile viewport, dark mode.
- Home: 0 router (empty state + CTA), router online, router mati (offline state),
  API error, cache hit vs miss (response <100ms saat hit), polling 60 detik.
- Visual: light & dark mode semua state; bandingkan dengan referensi Metronic hanya
  dari sisi proporsi/anatomi.
