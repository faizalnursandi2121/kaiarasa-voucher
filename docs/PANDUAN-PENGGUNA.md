# MIVO — Panduan Pengguna

**Versi Dokumen:** 1.0 · Agustus 2026
**Untuk:** Operator, kasir, dan teknis lapangan yang mengoperasikan MIVO sehari-hari

---

## Daftar Isi

1. [Memulai](#1-memulai)
2. [Halaman Home (Pusat Kendali)](#2-halaman-home-pusat-kendali)
3. [Tur Menu di Dalam Lokasi](#3-tur-menu-di-dalam-lokasi)
4. [Alur Tamu WiFi](#4-alur-tamu-wifi)
5. [Menu Security](#5-menu-security)
6. [Notifikasi dan Aktivitas](#6-notifikasi-dan-aktivitas)
7. [Penggunaan di Ponsel](#7-penggunaan-di-ponsel)
8. [FAQ dan Troubleshooting](#8-faq-dan-troubleshooting)
9. [Glosarium](#9-glosarium)

---

## 1. Memulai

### 1.1 Masuk ke Aplikasi

1. Buka alamat MIVO di browser (diberikan oleh admin).
2. Masukkan **username** dan **password** akun operator.
3. Klik **Sign In**.

> 📷 **[SS-P01] Halaman Login** · simpan sebagai `docs/img/panduan-01-login.png`
> Ambil: halaman login utuh di desktop.

Jika lupa sandi, hubungi admin teknis untuk pengaturan ulang — jangan menebak berulang kali.

### 1.2 Home vs Dashboard Lokasi

Setelah masuk, Anda berada di **Home**: pusat kendali semua lokasi.
Klik salah satu lokasi/kartu untuk masuk ke **Dashboard lokasi** tersebut — ruang kerja harian dengan menunya sendiri.

| Halaman | Untuk Apa |
|---|---|
| **Home** | Pantau SEMUA router dari semua lokasi sekaligus |
| **Dashboard lokasi** | Kelola satu lokasi: voucher, paket, laporan |

### 1.3 Berpindah Lokasi / Keluar

- Klik **avatar bulat hijau** di pojok kanan atas (desktop) → pilih:
  - **Disconnect** — keluar dari lokasi, kembali ke Home;
  - **Logout** — keluar dari aplikasi sepenuhnya;
  - **Settings** — pengaturan sistem (khusus admin).

---

## 2. Halaman Home (Pusat Kendali)

> 📷 **[SS-P02] Home — Tabel Router Status** · simpan sebagai `docs/img/panduan-02-home.png`

### 2.1 Kartu Navigasi Atas

Baris kartu di bagian atas adalah pintasan: ringkasan jaringan, pengaturan, dan fitur menyusul (bertanda *Soon*).

### 2.2 Tabel Router Status

Tabel utama berisi seluruh router lintas lokasi:

- **Kolom pencarian** — ketik nama router untuk memfilter cepat.
- **Filter status** — tampilkan hanya Online / Offline / Error / Connecting.
- **Tombol ↻** — segarkan data manual tanpa menunggu siklus otomatis.

Warna status: 🟢 online · 🔴 offline · 🟠 error · 🔵 connecting.

### 2.3 Network Availability

Panel kanan menampilkan grafik ketersediaan jaringan 24 jam terakhir beserta tiga angka penting: **Avg Uptime**, **Downtime**, dan **Incidents**.

### 2.4 Recent Activity

Daftar kejadian terbaru lintas lokasi yang diperbarui langsung (*live*) — berguna memantau dari meja kasir.

### 2.5 Widget Bawah

**Status Distribution** (proporsi status router) dan **Top Router by CPU** (lima router tersibuk) — indikator awal bila ada perangkat yang mulai "berat".

---

## 3. Tur Menu di Dalam Lokasi

Masuk ke sebuah lokasi, menu tersusun rapi di sidebar kiri (desktop) atau drawer setelah tombol ☰ (ponsel):

> 📷 **[SS-P03] Sidebar Menu Lokasi** · simpan sebagai `docs/img/panduan-03-sidebar.png`

| Grup | Menu | Fungsi |
|---|---|---|
| **Voucher** | Users | Daftar user/voucher hotspot aktif per router |
| | Generate | Buat voucher massal |
| | Quick Print | Cetak voucher instan |
| | Voucher Templates | Atur desain struk cetak |
| **Data Plan** | Profiles/Data Plans | Paket kecepatan & masa aktif |
| **Aktivitas/Laporan** | Reports | Laporan penjualan & aktivitas |
| **Security** | Walled Garden, IP Bindings | Akses pra-login & perangkat bypass |
| **Network** | DHCP | Daftar perangkat yang sedang pinjam IP |
| **Administration/Branding** | Settings, identitas | Konfigurasi & logo aplikasi |

### 3.1 Dashboard

Halaman pertama tiap lokasi — pantau performa dalam sekali lirik:

> 📷 **[SS-P04] KPI Dashboard** · simpan sebagai `docs/img/panduan-04-kpi.png`
>
> 📷 **[SS-P05] Grafik Dashboard** · simpan sebagai `docs/img/panduan-05-grafik.png`

| KPI | Arti |
|---|---|
| **Active Users** | Jumlah tamu yang sedang online |
| **Sold Today** | Voucher terjual hari ini |
| **Revenue Today** | Pendapatan hari ini |
| **Created Today** | Voucher baru yang dibuat hari ini |

Grafik: **User Activity** (Today / 7 Days / 30 Days), **Voucher Activity** 30 hari, dan **Top Packages** (paket terlaris). Tombol ↻ di pojok judul menyegarkan data live.

### 3.2 Users (Voucher Hotspot)

Daftar semua kode voucher/user: status (aktif/terpakai/expired), profil, kuota, dan masa aktif. Gunakan pencarian untuk mencari kode tertentu saat pelanggan bertanya.

> 📷 **[SS-P06] Daftar Users/Vouchers** · simpan sebagai `docs/img/panduan-06-users.png`

### 3.3 Generate Voucher

Membuat banyak voucher sekaligus: pilih **Data Plan**, isi **jumlah**, atur prefiks/nomor sesuai kebutuhan → klik generate. Voucher langsung siap dicetak dan otomatis tercatat sebagai stok.

> 📷 **[SS-P07] Form Generate** · simpan sebagai `docs/img/panduan-07-generate.png`

💡 *Tips:* generate per batch kecil (mis. 50) agar stok mudah dilacak per minggu.

### 3.4 Quick Print

Mode kasir: pilih paket + jumlah → cetak langsung ke printer struk. Untuk penjualan harian yang cepat, inilah menu yang paling sering dipakai.

> 📷 **[SS-P08] Quick Print** · simpan sebagai `docs/img/panduan-08-quick-print.png`

### 3.5 Voucher Templates

Mengatur tampilan struk cetak: ukuran, logo, teks ucapan, susunan kolom kode.

> 📷 **[SS-P09] Voucher Templates** · simpan sebagai `docs/img/panduan-09-template.png`

### 3.6 Data Plans (Profiles)

Definisikan paket layanan:

| Kolom | Isi Contoh | Arti |
|---|---|---|
| Nama | *Paket Harian* | Ditampilkan saat generate/cetak |
| Harga | Rp 5.000 | Masuk KPI Revenue |
| Rate Limit | 3M/3M (unduh/unggah) | Kecepatan maksimum tamu |
| Validity | 1 hari | Lama hidup voucher sejak dipakai |
| Expired Mode | Remove/Move | Nasib voucher setelah habis |

> 📷 **[SS-P10] Data Plans** · simpan sebagai `docs/img/panduan-10-dataplan.png`

### 3.7 Routers

Tambah/edit router per lokasi: nama, alamat IP/host, port API, **aktifkan SSL** bila port API router memakai enkripsi, serta opsi mode kedaluwarsa. Jika password router diganti di Winbox, masukkan ulang di sini — MIVO akan memberi tahu lewat peringatan kuning bila kredensial tidak cocok.

> 📷 **[SS-P11] Form Add/Edit Router** · simpan sebagai `docs/img/panduan-11-router.png`

### 3.8 Reports

Laporan penjualan & aktivitas per periode — sumber data untuk rekap mingguan/bulanan.

> 📷 **[SS-P19] Laporan** · simpan sebagai `docs/img/panduan-19-reports.png`

### 3.9 Settings & Branding

Pengaturan global (khusus admin): preferensi sistem hingga identitas/logo aplikasi.

> 📷 **[SS-P20] Settings** · simpan sebagai `docs/img/panduan-20-settings.png`

---

## 4. Alur Tamu WiFi

Beri tahu petugas lapangan alur ini agar bisa menjelaskan ke pelanggan:

1. Pelanggan menyambung WiFi Anda.
2. Halaman **login kustom** muncul otomatis (portal milik operator — logo & warna konsisten).
3. Pelanggan memasukkan **kode voucher** yang dibeli di kasir.
4. Bila benar → internet aktif sesuai paket; bila gagal → cek FAQ §8.

Sementara itu, situs tertentu (mis. mobile banking) tetap bisa dibuka **sebelum login** karena diizinkan lewat Walled Garden (§5).

---

## 5. Menu Security

### 5.1 Walled Garden

Daftar situs/layanan yang **boleh diakses tanpa login**.

- **Tambah rule** → isi domain tujuan (mis. domain bank) → simpan; rule langsung aktif di router.
- Ada dua jenis entri: berbasis **domain** (situs) dan berbasis **IP** (layanan tertentu).
- Gunakan secukupnya — semakin longgar walled garden, semakin besar celah pemakaian gratis.

> 📷 **[SS-P12] Walled Garden** · simpan sebagai `docs/img/panduan-12-walled-garden.png`

### 5.2 IP Bindings

Membuat **perangkat tertentu lolos tanpa login** (bypass MAC): CCTV, laptop kasir, server lokal.

- Pilih perangkat dari daftar DHCP atau masukkan MAC manual → tipe *bypassed* → simpan.
- Cocok juga untuk pelanggan langganan tetap yang tidak ingin login tiap kali.

> 📷 **[SS-P13] IP Bindings** · simpan sebagai `docs/img/panduan-13-bindings.png`

### 5.3 Network → DHCP

Melihat perangkat yang sedang meminjam IP di router — berguna untuk mencari MAC address saat akan membuat binding, atau mendiagnosis tamu yang "tidak mau muncul" di hotspot.

> 📷 **[SS-P14] DHCP Leases** · simpan sebagai `docs/img/panduan-14-dhcp.png`

---

## 6. Notifikasi dan Aktivitas

Ikon **lonceng 🔔** di bilah atas (desktop) menampilkan kejadian router terbaru:

| Kejadian | Arti |
|---|---|
| 🟢 Connected | Tamu baru berhasil login |
| 🔴 Went offline | Router hilang/tidak terjangkau |
| 🟠 High CPU | Beban router tinggi — pantau |

Titik merah pada lonceng = ada kejadian belum dibaca. Daftar **Recent Activity** di dashboard menampilkan hal senada dalam konteks lokasi.

> 📷 **[SS-P15] Notifikasi Terbuka** · simpan sebagai `docs/img/panduan-15-notifikasi.png`
>
> 📷 **[SS-P16] Dropdown Profil Desktop** · simpan sebagai `docs/img/panduan-16-profil.png`

---

## 7. Penggunaan di Ponsel

Aplikasi penuh nyaman dibuka dari HP:

- **☰ (kiri)** — buka menu sidebar sebagai drawer;
- **Logo tengah** — kembali ke dashboard lokasi;
- **Avatar (kanan)** — menu akun: Settings · Disconnect · Logout.

Semua tabel otomatis menyesuaikan layar; untuk tabel lebar, geser horizontal seperti biasa.

> 📷 **[SS-P17] Header Mobile** · simpan sebagai `docs/img/panduan-17-mobile-header.png`
> Ambil: tampilan HP dengan drawer sidebar TERBUKA agar menu ikut terlihat.
>
> 📷 **[SS-P18] Hasil Cetak Voucher** · simpan sebagai `docs/img/panduan-18-struk.png`
> Ambil: foto hasil cetakan struk voucher (boleh foto printer).

---

## 8. FAQ dan Troubleshooting

| Gejala | Kemungkinan Penyebab | Solusi |
|---|---|---|
| Router berstatus **Offline/Error** | Listrik mati, kabel putus, IP berubah, port API tertutup | Cek fisik lokasi; pastikan IP/port API sesuai form router; cek firewall |
| Kartu kuning *"password needs to be re-entered"* | Password router diganti di luar MIVO | Edit router → masukkan password baru |
| Voucher **tidak bisa login** | Salah ketik, sudah terpakai, validity habis, profil bermasalah | Cek di menu Users; uji dengan kode lain; periksa Data Plan |
| KPI/grafik **kosong padahal router online** | Data sampling belum cukup / cache | Klik ↻ Refresh; tunggu beberapa menit |
| Tamu tidak mendapat halaman login | Perangkat terlanjur di-bind (bypass), atau DHCP bermasalah | Cek IP Bindings & DHCP leases |
| Notifikasi tidak muncul | Sesi halaman lama | Segarkan halaman (F5) |
| Lupa password admin | — | Hubungi admin teknis untuk reset |

**Aturan emas:** sebelum panik, klik **↻ Refresh** — sebagian besar indikator basi hanya butuh penyegaran.

---

## 9. Glosarium

| Istilah | Arti |
|---|---|
| **Session / Lokasi** | Ruang kerja satu cabang/titik WiFi dalam MIVO |
| **Captive Portal** | Gerbang login wajib bagi tamu WiFi |
| **Walled Garden** | Daftar situs yang bebas diakses sebelum login |
| **IP Binding** | Pengecualian perangkat agar tidak perlu login |
| **Data Plan / Profile** | Paket: kecepatan + masa aktif + harga |
| **Rate Limit (Rx/Tx)** | Batas kecepatan unduh/unggah tamu |
| **Validity** | Masa hidup voucher sejak pertama dipakai |
| **Quick Print** | Mode cetak voucher instan untuk kasir |
| **KPI** | Angka ringkasan performa (pengguna, penjualan, pendapatan) |
| **NOC** | *Network Operations Center* — istilah layar pemantauan pusat (halaman Home) |

---

*Dokumen pendamping: `LAPORAN-TEKNIS.md` — arsitektur dan mekanisme internal aplikasi.*
