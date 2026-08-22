# MIVO Design System

Sumber kebenaran pattern UI MIVO. Setiap halaman baru / komponen baru WAJIB
mengikuti dokumen ini. Referensi proporsi: Metronic demo18 (pola saja, tanpa asset).

## 1. Warna

### Accent (brand)
| Token | Hex | Pemakaian |
|---|---|---|
| accent | #5f7f67 | tombol primer, link aktif, indikator online |
| accent-light | #92aa96 | hover state, highlight sekunder |
| accent-hover | #6b8b73 | hover state tombol primer |

### Netral (abu-abu hangat)
| Token | Hex | Pemakaian |
|---|---|---|
| ink | #1c2420 | teks utama |
| ink-75 | rgba(28,36,32,.75) | teks sekunder |
| ink-50 | rgba(28,36,32,.5) | teks tersier/placeholder |
| surface | #ffffff | permukaan kartu (light) |
| bg | #fafaf8 | latar halaman (light) |
| border | rgba(28,36,32,.10) | border kartu/input |

### Netral (dark)
| Token | Hex/RGBA |
|---|---|
| surface-dark | #1a1c19 |
| bg-dark | #121412 |
| ink-dark (teks utama) | #e9ebe7 |
| border-dark | rgba(255,255,255,.08) |

Nilai ini yang dipakai di seluruh mapping Tailwind (`dark:`).

### Semantik
| Makna | Light | Dark |
|---|---|---|
| success/online | #10b981 | #34d399 |
| warning/error-api | #f59e0b | #fbbf24 |
| danger/offline | #dc2626 | #ef4444 |

Catatan: teks error API pada kartu monitoring memakai warning (amber) bila router masih reachable tapi API gagal, dan danger (merah) hanya bila router benar-benar tidak dapat dihubungi.

### CSS Variables
```css
:root {
  --accent: #5f7f67;
  --accent-light: #92aa96;
  --accent-hover: #6b8b73;
  --ink: #1c2420;
  --surface: #ffffff;
  --bg: #fafaf8;
  --border: rgba(28,36,32,.10);
}
.dark {
  --surface: #1a1c19;
  --bg: #121412;
  --ink: #e9ebe7;
  --border: rgba(255,255,255,.08);
}
```

### Dark mode
Aktif via class `dark` pada `<html>` (mekanisme existing: localStorage.theme).
Semua warna netral dibalik; accent tetap sama.

## 2. Tipografi

| Peran | Font | Aturan |
|---|---|---|
| UI/data | Inter | body, form, tabel, angka, tombol |
| Display | Georgia italic | HANYA tagline/judul hero. DILARANG di komponen data |

Skala: h1 22px/600 · h2 17px/600 · body 13px/400 · label 11px/600 uppercase tracking .06em · micro 10px

Inter belum dimuat di codebase saat ini (yang ada Geist) — penerapan Inter wajib menyertakan @font-face/CDN + update `fontFamily.sans` di `tailwind.config.js`.

## 3. Spasi & Bentuk

- Spacing: kelipatan 4px
- Radius: card 16px · input/button 12px · menu item/elemen kecil 10px · badge 999px
- Border default 1px solid rgba(28,36,32,.10), dark: rgba(255,255,255,.08)
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
Header kartu: title kiri (13px/600), action kanan (icon button ghost). Card: bg surface, border 1px, radius 16px, padding 20px (kartu kecil 14–16px).

### Button
| Varian | Style |
|---|---|
| primary | bg #5f7f67, teks putih, radius 12px, h-40px |
| secondary | bg transparan, border border-color, teks ink |
| ghost (icon) | tanpa bg, hover bg rgba(ink,.05) |

### Input
bg rgba(ink,.04); border 1px border; radius 12px; h-44px; padding-x 14px;
focus: border #5f7f67 + ring 3px rgba(95,127,103,.20)
label: 11px/600 uppercase di atas input, margin-bottom 8px

### Badge Status
badge-online: dot #10b981 + teks, bg rgba(16,185,129,.10), radius 999
badge-offline: dot #dc2626, bg rgba(220,38,38,.10)
badge-warning: dot #f59e0b, bg rgba(245,158,11,.10)
ukuran: font 11px/600, padding 4px 10px

### Table
Header: 11px/600 uppercase ink-50, border-bottom. Baris: 13px, hover bg rgba(ink,.03).

## 6. Layout Global

- Header app: h-60px, logo kiri, action kanan (theme toggle, user menu)
- Sidebar: w-240px, item menu h-38px radius 10px, item aktif bg rgba(146,170,150,.15)
- Konten max-w-1200px center, padding 24px

## 7. Mapping Tailwind (cheatsheet)

| Kebutuhan | Class |
|---|---|
| Card | `rounded-2xl border border-[rgba(28,36,32,.10)] dark:border-white/[.08] dark:bg-[#1a1c19] p-5` |
| Primary btn | `bg-[#5f7f67] hover:bg-[#6b8b73] text-white rounded-xl h-10 px-4 text-[13px] font-semibold` |
| Secondary btn | `inline-flex items-center rounded-xl h-10 px-4 text-[13px] font-semibold border border-[rgba(28,36,32,.10)] dark:border-white/[.08]` |
| Input focus | `focus:border-[#5f7f67] focus:ring-[3px] focus:ring-[rgba(95,127,103,.20)] outline-none` |
| Badge online | `inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-600 (teks lebih gelap dari token #10b981 demi kontras)` |
| Label form | `block text-[11px] font-semibold uppercase tracking-[.06em] opacity-60 mb-2` |

Untuk dark mode, badge/teks semantik memakai pasangan dark dari §1 (#34d399 / #fbbf24 / #ef4444).

---

## 8. UI States Reference (Wajib)

Setiap komponen interaktif WAJIB punya rencana untuk state-state yang relevan
dengannya — sebelum dianggap selesai. "Komponen tanpa state error/loading"
adalah komponen yang belum selesai. Daftar berikut adalah kosakata resmi;
gunakan namanya saat diskusi desain & review kode.

### 8.1 Siklus data

| # | State | Konvensi MIVO |
|---|---|---|
| 1 | **Loading** | Spinner inline di dalam tombol/konteks, bukan overlay layar penuh. Teks tetap terbaca. |
| 2 | **Skeleton** | Blok `animate-pulse` berbentuk mirip konten final — dipakai untuk first paint kartu/tabel. |
| 3 | **Partial Loading** | Bagian yang sudah siap tampil; bagian yang belum menampilkan skeleton/placeholder — jangan blokir semua. |
| 4 | **Stale Data** | Data lama tetap tampil + label "terakhir diperbarui HH:MM" + indikator refresh berjalan. |
| 5 | **Syncing** | Ikon spinner kecil + teks "menyinkronkan…"; data lama tidak dikunci. |
| 6 | **Synced** | Checkmark halus / teks "tersinkron", menghilang sendiri setelah beberapa detik. |
| 7 | **Auto-save** | Simpan diam-diam setelah idle singkat; indikator status kecil di dekat field. |
| 8 | **Optimistic Update** | UI berubah segera + bisa dibatalkan otomatis bila server menolak (rollback + toast). |
| 9 | **Refresh** | Selalu manual-able (tombol Refresh) + indikator berputar saat proses. |
| 10 | **Completed** | Feedback sukses jelas (toast/checkmark) lalu kembali ke state normal. |

### 8.2 Feedback & hasil

| # | State | Konvensi MIVO |
|---|---|---|
| 11 | **Success** | Toast hijau (SweetAlert existing) atau badge emerald; auto-dismiss. |
| 12 | **Error** | Toast merah untuk aksi; pesan inline merah untuk validasi form. Selalu sebut penyebabnya. |
| 13 | **Warning** | Amber — operasi berjalan tapi ada catatan (mis. API router gagal tapi router reachable). |
| 14 | **Toast / Notification** | Satu mekanisme resmi: SweetAlert (existing). Jangan campur sistem notifikasi lain. |
| 15 | **Progress** | Bar/percent hanya untuk proses >3 detik; selain itu cukup spinner. |
| 39 | **Completed** | Lihat #10. |
| 40 | **Cancelled** | Kembali ke state sebelum proses tanpa sisa perubahan; toast netral "dibatalkan". |

### 8.3 Interaksi

| # | State | Konvensi MIVO |
|---|---|---|
| 16 | **Disabled** | `opacity-60 pointer-events-none` + `aria-disabled="true"` + `tabindex="-1"` pada link. Beri alasan bila memungkinkan. |
| 17 | **Hover** | Transisi 150–200ms; perubahan halus (bg tint/border), tanpa scale/lompatan. |
| 18 | **Focus** | Ring `3px rgba(95,127,103,.20)` + border accent. JANGAN hapus outline tanpa pengganti. |
| 19 | **Active / Selected** | Item menu: bg sage transparan + indikator kiri; tab: indikator geser. |
| 20 | **Validation** | Pesan inline merah di bawah field + border merah; validasi realtime setelah blur pertama. |
| 29 | **Read-only** | Tampil sebagai teks biasa (bukan input disabled) + nilai jelas bisa disalin. |

### 8.4 Keadaan tak terduga

| # | State | Konvensi MIVO |
|---|---|---|
| 21 | **Search** | Kosong → tampilkan hint; hasil nol → "Tidak ada hasil untuk …" + saran. |
| 22 | **Filter** | Filter aktif selalu terlihat (chip/badge) + tombol reset. |
| 23 | **Pagination** | Untuk tabel data besar; minimal info "x–y dari z". |
| 24 | **Infinite Scroll** | Hanya untuk feed ringan; tabel admin pakai pagination. |
| 25 | **Authentication / Session** | Sesi habis → redirect login dengan flash "Sesi berakhir". Fetch 401 = trigger redirect. |
| 26 | **Destructive Action** | Wajib konfirmasi eksplisit (modal), tombol merah, sebutkan objek spesifik yang dihapus. |
| 27 | **Confirmation** | Modal SweetAlert confirm; fokus default pada tombol aman. |
| 28 | **Retry** | Semua error jaringan/API menyediakan tombol "Coba Lagi". |
| 30 | **Maintenance / Unavailable** | Halaman/full-banner netral dengan ETA bila ada; hindari blank. |
| 31 | **No Permission** | Sembunyikan menu yang tak boleh diakses; halaman terlarang → pesan 403 rapi. |
| 32 | **Rate Limited** | Toast "Terlalu banyak percobaan, tunggu X detik" + hitungan mundur. |
| 33 | **Unsaved Changes** | Guard `beforeunload` + indikator dot pada item menu/nama form. |
| 37 | **Connection Lost** | Banner kuning tipis di atas konten "Koneksi terputus" + polling berhenti. |
| 38 | **Reconnecting** | Banner berubah "Menyambungkan ulang…" + spinner; pulih → banner hilang + toast sukses. |
| 34 | **Auto-save** | Lihat #7. |
| 35 | **Syncing** | Lihat #5. |
| 36 | **Synced** | Lihat #6. |

### 8.5 Contoh penerapan (sudah ada di codebase)

- Home fleet monitor: Loading (#2 skeleton), Stale (#4 "terakhir diperbarui"),
  Error (#13 amber "API Error"), Offline (#merah), Disabled (#16 link dashboard),
  Retry (#28 tombol Refresh), Auto-refresh (#9).
- Login: Validation (#20 inline/toast), Loading submit (#1 spinner + disabled).

> Aturan praktis: **setiap elemen interaktif baru harus menyebut state mana saja yang
> diimplementasikan dari daftar ini di deskripsi PR/task-nya.**
