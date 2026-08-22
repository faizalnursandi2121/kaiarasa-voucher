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
