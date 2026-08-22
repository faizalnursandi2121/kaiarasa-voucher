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
Header kartu: title kiri (13px/600), action kanan (icon button ghost). Card: bg surface, border 1px, radius 16px, padding 20px (kartu kecil 14–16px).

### Button
| Varian | Style |
|---|---|
| primary | bg #5f7f67, teks putih, radius 12px, h-40px |
| secondary | bg transparan, border border-color, teks ink |
| ghost (icon) | tanpa bg, hover bg rgba(ink,.05) |

### Input
bg rgba(ink,.04); border 1px border; radius 12px; h-44px; padding-x 14px;
focus: border #5f7f67 + ring 3px rgba(95,127,103,.12)
label: 11px/600 uppercase di atas input, margin-bottom 8px

### Badge Status
badge-online: dot #10b981 + teks, bg rgba(16,185,129,.10), radius 999
badge-offline: dot #dc2626, bg rgba(220,38,38,.08)
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
| Card | `rounded-2xl border border-black/[.07] dark:bg-[#1a1c19] p-5` |
| Primary btn | `bg-[#5f7f67] hover:bg-[#6b8b73] text-white rounded-xl h-10 px-4 text-[13px] font-semibold` |
| Badge online | `inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[11px] font-semibold text-emerald-600` |
| Label form | `block text-[11px] font-semibold uppercase tracking-wider opacity-60 mb-2` |
