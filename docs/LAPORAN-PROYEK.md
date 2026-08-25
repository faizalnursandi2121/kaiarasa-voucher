# LAPORAN PROYEK — MIVO

**Platform Manajemen WiFi Hotspot Multi-Lokasi**

| | |
|---|---|
| **Ditujukan kepada** | Yth. Bapak/Ibu [Nama Atasan] |
| **Disusun oleh** | [Nama Penyusun] |
| **Tanggal** | Agustus 2026 |
| **Versi Dokumen** | 1.0 |
| **Sifat** | Laporan proyek — disusun agar dapat dibaca oleh pembaca teknis maupun umum |

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Latar Belakang dan Tujuan](#2-latar-belakang-dan-tujuan)
3. [Fitur dan Fungsionalitas](#3-fitur-dan-fungsionalitas)
4. [Konsep Multi-Lokasi](#4-konsep-multi-lokasi)
5. [Cara Kerja Inti](#5-cara-kerja-inti)
6. [Teknologi yang Digunakan](#6-teknologi-yang-digunakan)
7. [Status Proyek Saat Ini](#7-status-proyek-saat-ini)
8. [Rencana Pengembangan (Roadmap)](#8-rencana-pengembangan-roadmap)
9. [Penutup](#9-penutup)

---

## 1. Ringkasan Eksekutif

MIVO adalah **aplikasi web yang kami bangun sepenuhnya dari nol** untuk memusatkan pengelolaan jaringan WiFi Hotspot berbasis MikroTik dalam satu antarmuka. Melalui MIVO, seluruh pekerjaan yang semula dilakukan secara manual pada tiap router — membuat voucher, menentukan paket kecepatan, memeriksa status perangkat, hingga menyusun rekap penjualan — kini dapat dilakukan dari satu tempat, untuk banyak lokasi sekaligus.

Proyek ini **telah selesai dibangun dan berjalan operasional**. Seluruh modul inti telah tersedia: pusat pemantauan seluruh lokasi, dashboard kinerja harian, ekosistem voucher mulai dari pembuatan massal hingga pencetakan struk, kontrol bandwidth, serta laporan aktivitas dan penjualan. Rencana pengembangan lanjutan telah disusun bertahap pada Bab 8.

---

## 2. Latar Belakang dan Tujuan

### 2.1 Latar Belakang

Pengelolaan hotspot MikroTik secara konvensional menuntut operator masuk ke konsol tiap router untuk pekerjaan-pekerjaan rutin. Seiring bertambahnya jumlah titik/jaringan, cara ini menimbulkan sejumlah kesulitan: pekerjaan menjadi lambat dan rawan salah, tidak ada catatan penjualan yang rapi, status gangguan baru diketahui setelah ada keluhan, dan standarisasi layanan antar lokasi sulit dijaga.

### 2.2 Tujuan Proyek

1. Memusatkan seluruh operasional hotspot ke dalam satu aplikasi web yang ringan.
2. Mendukung **banyak lokasi** dalam satu instalasi dengan data yang terpisah rapi.
3. Menghadirkan angka-angka kunci (pengguna, penjualan, pendapatan) secara langsung tanpa penghitungan manual.
4. Memberikan pengalaman tamu yang profesional melalui halaman login (*captive portal*) kustom milik sendiri.

---

## 3. Fitur dan Fungsionalitas

Bab ini merangkum kemampuan aplikasi per bagian layar, beserta manfaatnya.

### 3.1 Home — Pusat Pemantauan Semua Lokasi

Halaman pertama setelah masuk adalah *layar kendali* (NOC) yang memperlihatkan kondisi seluruh router lintas lokasi dalam satu tabel: status daring/luring, alamat, dan lokasinya. Dilengkapi pencarian cepat, filter status, panel ketersediaan jaringan 24 jam terakhir (rata-rata uptime, durasi gangguan, jumlah insiden), daftar kejadian terbaru yang diperbarui langsung, serta peringkat router dengan beban tertinggi.

> **Manfaat:** potensi gangguan diketahui sebelum pelanggan berkeluhan; inspeksi pagi cukup satu layar.

> 📷 **[SS-T01] Halaman Home (NOC)** · simpan sebagai `docs/img/proyek-01-home.png`

### 3.2 Dashboard Lokasi

Setiap lokasi memiliki ruang kerjanya sendiri. Bagian atas menampilkan empat angka kinerja hari ini — **pengguna aktif**, **voucher terjual**, **pendapatan**, dan **voucher baru dibuat**. Di bawahnya tersedia grafik ramai pengunjung (harian/mingguan/bulanan), grafik produksi voucher 30 hari, peringkat paket terlaris, serta alur kejadian terbaru lokasi tersebut.

> **Manfaat:** pemilik dan operator mendapat gambaran kinerja tanpa penghitungan manual.

> 📷 **[SS-T02] Dashboard Lokasi** · simpan sebagai `docs/img/proyek-02-dashboard.png`

### 3.3 Ekosistem Voucher

Seluruh siklus voucher ditangani aplikasi:

| Tahap | Fasilitas |
|---|---|
| Pembuatan massal | **Generate Vouchers** — puluhan hingga ratusan kode sekaligus per paket |
| Penjualan satuan | **Quick Print** — cetak instan ke printer struk untuk kasir |
| Tata cetak | **Voucher Templates** — desain struk: ukuran, logo, susunan kolom |
| Pemeliharaan | **Vouchers** — daftar seluruh kode beserta status dan masa aktifnya |

### 3.4 Data Plans — Paket Layanan dan Bandwidth

Setiap jenis layanan didefinisikan sekali sebagai paket: nama, harga, batas kecepatan unduh/unggah (satuan fleksibel Kbps/Mbps/Gbps), serta masa aktif voucher. Harga paket otomatis menjadi sumber angka pendapatan harian.

### 3.5 Pemantauan Pengguna

- **Online Users** — daftar tamu yang sedang terhubung: kode, IP, lama sesi, dan pemakaian; bila diperlukan sesi tertentu dapat diputus langsung.
- **Connected Devices** — seluruh perangkat yang dikenali router (alamat MAC/IP/hostname), berguna untuk pemeriksaan bersama teknis.

### 3.6 Laporan dan Catatan Aktivitas

- **Activity Log** — catatan kronologis login/keluar pengguna; menjadi rujukan resmi saat menindaklanjuti klaim pelanggan.
- **Sales Report** — rekapitulasi penjualan voucher per periode untuk kebutuhan pertanggungjawaban.

### 3.7 Identitas dan Kenyamanan Pakai

Pengaturan logo terpusat untuk tampilan aplikasi dan materi cetak; antarmuka terang yang konsisten; tata letak responsif sehingga seluruh fitur tetap nyaman dioperasikan dari ponsel.

---

## 4. Konsep Multi-Lokasi

Satu instalasi MIVO melayani banyak lokasi (disebut *session*). Setiap lokasi memiliki alamat kerjanya sendiri, dan datanya **terisolasi penuh**: voucher, paket, dan laporan Lokasi A tidak akan pernah tercampur dengan Lokasi B.

```
                        ┌────────────────────┐
                        │      MIVO          │
                        └─────────┬──────────┘
           ┌──────────────────────┼──────────────────────┐
           ▼                      ▼                      ▼
    ┌─────────────┐        ┌─────────────┐        ┌─────────────┐
    │  Lokasi A   │        │  Lokasi B   │        │  Lokasi C   │
    │ Router A1   │        │ Router B1   │        │ Router C1   │
    │ Voucher A   │        │ Voucher B   │        │ Voucher C   │
    │ Laporan A   │        │ Laporan B   │        │ Laporan C   │
    └─────────────┘        └─────────────┘        └─────────────┘
```

Perpindahan antar lokasi maupun keluar dari lokasi dilakukan melalui menu profil di sudut kanan atas, tanpa perlu login ulang.

---

## 5. Cara Kerja Inti

Bab ini menjelaskan mekanisme sistem secara garis besar; kedalaman teknis sengaja dibuat secukupnya agar tetap nyaman dibaca kalimatinya.

### 5.1 Gambaran Arsitektur

MIVO berjalan sebagai aplikasi web pada satu server, dan berkomunikasi dengan para router melalui jalur resmi RouterOS API (tersedia mode terenkripsi):

```
 Operator/Admin ──► SERVER MIVO ──► Router MikroTik ──► Tamu WiFi
                    (database lokal     (hotspot &
                     tersimpan aman      captive portal)
                     pada volume)
```

Prinsipnya sederhana: **data bisnis tersimpan di server**, sedangkan **eksekusi jaringan terjadi di router** — MIVO menjembatani keduanya dan mencatat segalanya.

### 5.2 Mekanisme Captive Portal

*Captive portal* adalah gerbang wajib bagi tamu WiFi: koneksi baru dicegat router dan dialihkan ke halaman login sebelum dapat menjelajah. MIVO menggunakan **halaman login kustom milik sendiri** — identitas visual konsisten dengan merek — yang tetap tersambung penuh ke autentikasi hotspot router.

Alur lengkapnya:

```
 TAMU                          ROUTER MIKROTIK                 MIVO
 ────                          ───────────────                 ────
 1. Menyambungkan       →  DHCP memberi alamat IP
    perangkat
 2. Membuka situs       →  Koneksi dicegat, dialihkan ke
                           halaman login kustom
 3. Halaman LOGIN tampil ←  (layanan penting seperti
    di perangkat tamu          mobile banking tetap dapat
                               diakses pra-login sesuai
                               kebijakan keamanan)
 4. Memasukkan KODE     →  Autentikasi user hotspot  ←  Voucher dibuat &
    VOUCHER                                                dicetak dari MIVO
 5. SESI AKTIF          →  Kecepatan & masa aktif    ←  Data Plan didefinisikan
                           mengikuti profil             di MIVO
 6. Menikmati internet  →  Kejadian tercatat         →  Dashboard & notifikasi
```

Pengaturan keamanan tingkat lanjut untuk fase pra-login (misalnya mengizinkan layanan tertentu, atau mengecualikan perangkat khusus seperti CCTV dari kewajiban login) tersedia sebagai **kebijakan jaringan yang dikelola terpusat oleh administrator** — di luar alur operasional harian operator.

### 5.3 Keandalan Operasional

Aplikasi dikemas dalam kontainer Docker tunggal: basis data tersimpan pada **volume persisten** (aman terhadap pembaruan), pembaruan struktur data berjalan **otomatis saat aplikasi dinyalakan**, dan proses instalasi dilindungi penanda permanen sehingga tidak dapat diakses ulang oleh pihak yang tidak berwenang setelah sistem aktif.

---

## 6. Teknologi yang Digunakan

| Teknologi | Peran |
|---|---|
| PHP 8 | Mesin aplikasi sisi-server |
| SQLite | Basis data tertanam — cadangan semudah menyalin berkas, tanpa server tambahan |
| Tailwind CSS | Kerangka tampilan — konsistensi visual dan ukuran halaman yang ringan |
| ApexCharts & Lucide | Grafik interaktif dan ikonografi antarmuka |
| RouterOS API | Jalur komunikasi resmi ke router MikroTik (mendukung SSL) |
| Docker | Pengemasan deployment — sekali bangun, dapat dijalankan di mana saja |

Pemilihan teknologi mengutamakan **kesederhanaan operasional**: seluruh sistem berjalan dalam satu kontainer tanpa ketergantungan layanan eksternal, sehingga minim titik kegagalan dan murah dirawat.

---

## 7. Status Proyek Saat Ini

Seluruh modul inti telah selesai dan berjalan stabil:

| Modul | Status |
|---|---|
| Pusat pemantauan multi-lokasi (Home/NOC) | ✅ Stabil |
| Dashboard KPI & grafik per lokasi | ✅ Stabil |
| Generate, cetak, dan template voucher | ✅ Stabil |
| Data Plans & kontrol bandwidth | ✅ Stabil |
| Monitoring pengguna (online & perangkat) | ✅ Stabil |
| Activity Log & Sales Report | ✅ Stabil |
| Captive portal berhalaman login kustom | ✅ Berjalan |
| Antarmuka ponsel (responsif penuh) | ✅ Stabil |
| Deployment kontainer + migrasi otomatis | ✅ Stabil |

Catatan mutu terbaru: proses instalasi kini mengunci dirinya sendiri setelah selesai dan menyimpan kunci enkripsi secara permanen pada volume — dua perbaikan keamanan yang menutup celah akses ulang installer serta menstabilkan data terenkripsi antar pembaruan.

---

## 8. Rencana Pengembangan (Roadmap)

Rencana berikut disusun bertahap mengikuti prioritas nilai bagi operasional, dimulai dari yang paling mendesak. Setiap fase dirancang saling menopang dan tidak mengganggu sistem yang sudah berjalan.

### Fase 1 — Penguatan Fondasi (0–3 bulan)

| Inisiatif | Deskripsi Singkat | Nilai yang Diberikan |
|---|---|---|
| Peran & hak akses | Pembeda akun admin, operator, dan kasir dengan menu yang berbeda | Keamanan data lebih ketat; kasir hanya melihat fungsi penjualan |
| Pemulihan kata sandi mandiri | Reset sandi admin tanpa harus membuka basis data | Mengurangi ketergantungan pada teknis |
| Cadangan otomatis terjadwal | Salinan basis data berkala ke lokasi aman | Perlindungan data tanpa disiplin manual |
| Editor portal visual | Pengaturan tampilan halaman login tamu langsung dari aplikasi | Personalisasi per lokasi tanpa sentuhan teknis |

### Fase 2 — Otomasi dan Integrasi (3–6 bulan)

| Inisiatif | Deskripsi Singkat | Nilai yang Diberikan |
|---|---|---|
| Notifikasi WhatsApp/Telegram | Kabar router offline atau cap penjualan harian ke grup pimpinan | Respons gangguan lebih cepat tanpa membuka aplikasi |
| Laporan terjadwal (PDF/Excel) | Rekap harian/mingguan terkirim otomatis | Hemat waktu penyusunan laporan manual |
| Pembayaran QRIS mandiri | Pelanggan membeli voucher sendiri lewat halaman bayar | Penjualan berjalan 24 jam tanpa kasir |
| API publik terdokumentasi | Gerbang data untuk integrasi mitra/sistem lain | Membuka peluang kerja sama dan pengembangan turunan |

### Fase 3 — Skala dan Kecerdasan (6–12 bulan)

| Inisiatif | Deskripsi Singkat | Nilai yang Diberikan |
|---|---|---|
| Analitik lintas lokasi | Tren pendapatan, perbandingan cabang, prediksi kebutuhan stok | Dasar keputusan bisnis yang lebih tajam |
| Aplikasi mobile (PWA) + notifikasi dorong | Pengalaman ponsel kelas aplikasi native | Kemudahan pantau dari mana saja |
| Multi-tenant (SaaS) | Satu instalasi melayani banyak perusahaan operator | Membuka model bisnis langganan baru |
| Abstraksi lapisan router | Persiapan dukungan perangkat selain MikroTik | Cakupan pasar melebar |

**Prinsip pengembangan:** stabilitas sistem berjalan selalu didahulukan; setiap pembaruan wajib kompatibel dengan data lama; dan urutan pengerjaan menyesuaikan masukan dari penggunaan lapangan.

---

## 9. Penutup

MIVO telah menghadirkan solusi pengelolaan WiFi Hotspot multi-lokasi yang utuh: dari gerbang tamu berupa captive portal berhalaman login kustom, ekosistem voucher yang lengkap, hingga angka-angka kinerja yang tersaji langsung kepada pengambil keputusan. Sistem dibangun dengan fondasi teknologi yang sederhana namun kokoh, sehingga murah dioperasikan dan siap dikembangkan.

Dengan rencana pengembangan tiga fase di atas, kami meyakini MIVO tidak hanya menyelesaikan persoalan operasional hari ini, tetapi juga menjadi fondasi produk yang bernilai lebih besar ke depan. Demikian laporan ini disusun; kami terbuka atas arahan serta masukan Bapak/Ibu untuk penyempurnaan tahapan berikutnya.

Atas perhatian dan dukungannya, kami ucapkan terima kasih.

---

*Dokumentasi pendamping: `PANDUAN-PENGGUNA.md` (buku panduan tim operasional) · `PANDUAN-PELANGGAN.md` (panduan singkat pelanggan akhir).*
