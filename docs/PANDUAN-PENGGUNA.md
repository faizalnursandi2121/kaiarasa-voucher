# MIVO — Panduan Penggunaan untuk Tim Operasional

**Versi Dokumen:** 1.1 · Agustus 2026
**Untuk:** Operator, kasir, dan staf lapangan yang menangani penjualan voucher serta pemantauan harian.

> Panduan ini disusun persis mengikuti tampilan aplikasi. Jika Anda menemukan perbedaan, kemungkinan versi aplikasi Anda belum diperbarui — laporkan ke admin teknis.

---

## Daftar Isi

1. [Memulai](#1-memulai)
2. [Halaman Home — Memantau Semua Lokasi](#2-halaman-home--memantau-semua-lokasi)
3. [Dashboard Lokasi](#3-dashboard-lokasi)
4. [Mengelola Voucher](#4-mengelola-voucher)
5. [Data Plans](#5-data-plans)
6. [Memantau Pengguna](#6-memantau-pengguna)
7. [Laporan](#7-laporan)
8. [Logos](#8-logos-branding)
9. [Penggunaan di Ponsel](#9-penggunaan-di-ponsel)
10. [FAQ dan Troubleshooting](#10-faq-dan-troubleshooting)
11. [Glosarium](#11-glosarium)

---

## 1. Memulai

### 1.1 Masuk ke Aplikasi

1. Buka alamat MIVO di browser (alamat diberikan oleh admin).
2. Ketik **username** dan **password** Anda.
3. Tekan tombol **Sign In**.

> 📷 **[SS-P01] Halaman Login** · simpan sebagai `docs/img/panduan-01-login.png`

Lupa sandi? Hubungi admin teknis untuk diatur ulang. Hindari mencoba berkali-kali.

### 1.2 Dua Tingkat Halaman

| Halaman | Isi | Kapan Dipakai |
|---|---|---|
| **Home** | Status semua router dari semua lokasi | Pagi hari: cek kondisi jaringan; saat ada keluhan lintas lokasi |
| **Dashboard lokasi** | Ruang kerja satu lokasi: voucher, paket, laporan | Pekerjaan harian lokasi tersebut |

### 1.3 Masuk & Keluar Lokasi

- Dari Home, pilih kartu lokasi yang ingin dikelola — Anda masuk ke Dashboard lokasi itu.
- Untuk keluar, klik **avatar bulat hijau** di pojok kanan atas, lalu pilih:
  - **Disconnect** — kembali ke Home (masih login di aplikasi);
  - **Logout** — keluar sepenuhnya;
  - **Settings** — pengaturan sistem (khusus admin).

> 📷 **[SS-P17] Menu Avatar (Dropdown Profil)** · simpan sebagai `docs/img/panduan-17-profil.png`

---

## 2. Halaman Home — Memantau Semua Lokasi

> 📷 **[SS-P02] Home — Tabel Router Status** · simpan sebagai `docs/img/panduan-02-home.png`

### 2.1 Membaca Tabel Router Status

Setiap baris adalah satu router beserta lokasinya. Warna statusnya:

🟢 **Online** · 🔴 **Offline** · 🟠 **Error** · 🔵 **Connecting**

Gunakan kotak **pencarian** untuk mencari nama router tertentu, atau **filter status** untuk menampilkan hanya yang bermasalah. Tombol **↻** menyegarkan data tanpa menunggu siklus otomatis.

**Kebiasaan baik:** buka Home setiap awal shift. Jika ada router merah, tangani sebelum pelanggan berdatangan.

### 2.2 Menambah / Mengubah Router

Pengelolaan router dilakukan dari halaman Home ini (bukan di dalam lokasi):

1. Klik tombol **tambah router**, isi nama, alamat IP/host, port API, dan password router.
2. Aktifkan opsi **SSL** hanya jika port API router memang memakai enkripsi (umumnya 8729).
3. Simpan. Router baru akan muncul di tabel dan mulai mengirim data.

Jika password router pernah diganti lewat Winbox, ubah juga di sini — aplikasi akan menampilkan peringatan kuning apabila kredensial tidak cocok lagi.

### 2.3 Panel Pendukung

- **Network Availability** — grafik ketersediaan 24 jam + angka uptime, downtime, dan insiden.
- **Recent Activity** — kejadian terbaru lintas lokasi, diperbarui langsung.
- **Status Distribution & Top CPU** — proporsi status dan lima router tersibuk; indikator awal bila ada perangkat mulai berat.

---

## 3. Dashboard Lokasi

Halaman pertama setiap lokasi — performa hari ini dalam satu layar.

> 📷 **[SS-P03] Sidebar Menu Lokasi** · simpan sebagai `docs/img/panduan-03-sidebar.png`
>
> 📷 **[SS-P04] KPI Dashboard** · simpan sebagai `docs/img/panduan-04-kpi.png`
>
> 📷 **[SS-P05] Grafik Dashboard** · simpan sebagai `docs/img/panduan-05-grafik.png`

### 3.1 Empat Angka Utama (KPI)

| Angka | Artinya |
|---|---|
| **Active Users** | Tamu yang sedang online saat ini |
| **Sold Today** | Jumlah voucher yang terjual hari ini |
| **Revenue Today** | Pendapatan hari ini |
| **Created Today** | Voucher baru yang dibuat hari ini |

### 3.2 Grafik

- **User Activity** — ramai pengunjung per jam (Today) atau per hari (7/30 Days); gunakan pil di pojok kartu untuk berganti periode.
- **Voucher Activity** — jumlah voucher dibuat selama 30 hari terakhir.
- **Top Packages** — paket paling laris (grafik donat + daftar peringkat).

Tombol **↻** di samping judul halaman menyegarkan semua data live.

### 3.3 Kartu Aksi Cepat

Di bawah KPI terdapat dua kartu besar: **Quick Print** (cetak instan, §4.3) dan **Generate Vouchers** (pintasan ke halaman pembuatan massal, §4.2). Inilah dua tombol yang paling sering dipakai kasir.

---

## 4. Mengelola Voucher

Alur kerjanya: siapkan **template cetak** sekali → **generate** stok secara massal → jual dan **quick print** sesuai pesanan → pantau penjualan di KPI.

### 4.1 Vouchers (Daftar Kode)

Menu **Vouchers** menampilkan seluruh kode hotspot pada lokasi ini: statusnya (belum terpakai / aktif / kedaluwarsa), profil paketnya, dan masa aktifnya. Gunakan pencarian saat pelanggan bertanya soal kode tertentu, misalnya lupa kode atau mengklaim belum terpakai.

> 📷 **[SS-P06] Daftar Vouchers** · simpan sebagai `docs/img/panduan-06-vouchers.png`

### 4.2 Generate Vouchers

Untuk menyiapkan stok:

1. Buka menu **Generate Vouchers**.
2. Pilih **Data Plan** yang dijual.
3. Tentukan jumlah, prefiks/penomoran, dan opsi lain sesuai kebutuhan.
4. Klik generate — kode langsung jadi dan otomatis terhitung di KPI *Created Today*.

💡 *Tips:* buat stok per batch kecil (misal 50) agar mudah dilacak per minggu.

> 📷 **[SS-P07] Halaman Generate** · simpan sebagai `docs/img/panduan-07-generate.png`

### 4.3 Quick Print (Cetak Instan)

Untuk penjualan satuan di kasir: klik kartu **Quick Print** pada Dashboard, pilih paket dan jumlah, lalu cetak langsung ke printer struk. Tanpa langkah tambahan — pas untuk jam sibuk.

> 📷 **[SS-P08] Quick Print** · simpan sebagai `docs/img/panduan-08-quick-print.png`
>
> 📷 **[SS-P19] Hasil Cetak Struk** · simpan sebagai `docs/img/panduan-19-struk.png` *(boleh foto printer)*

### 4.4 Voucher Templates

Mengatur bentuk struk: ukuran kertas, logo, teks sapaan, dan susunan kolom kode. Atur sekali di awal; ubah lagi bila harga/branding berubah.

> 📷 **[SS-P09] Voucher Templates** · simpan sebagai `docs/img/panduan-09-template.png`

---

## 5. Data Plans

Menu **Data Plans** berisi daftar paket layanan yang bisa dijual. Setiap paket menentukan:

| Kolom | Contoh | Artinya |
|---|---|---|
| Nama Paket | *Paket Harian* | Yang dipilih saat generate/cetak |
| Harga | Rp 5.000 | Sumber angka *Revenue Today* |
| Rate Limit (Rx/Tx) | 3M/3M | Kecepatan unduh/unggah maksimum tamu |
| Validity | 1 hari | Lama hidup voucher sejak pertama dipakai |

Perubahan paket berlaku untuk voucher yang **akan** dibuat; voucher lama tetap mengikuti aturan saat kode itu dibuat.

> 📷 **[SS-P10] Daftar Data Plans** · simpan sebagai `docs/img/panduan-10-dataplan.png`

---

## 6. Memantau Pengguna

Dua menu ini menjawab pertanyaan paling sering di lapangan: *"siapa yang sedang online?"* dan *"perangkat apa saja yang terdaftar?"*

### 6.1 Online Users

Daftar tamu yang **sedang terhubung**: username/kode, alamat IP, lama sesi, dan pemakaian. Berguna saat tamu melapor koneksi lambat — lihat di sini apakah ia benar-benar online dan seberapa berat pemakaiannya. Bila perlu, petugas dapat memutus sesi tertentu langsung dari daftar ini.

> 📷 **[SS-P11] Online Users** · simpan sebagai `docs/img/panduan-11-online-users.png`

### 6.2 Connected Devices

Daftar perangkat (host) yang dikenali router: alamat MAC, IP, dan hostname. Gunakan untuk melacak perangkat milik tamu maupun perangkat internal lokasi (mis. CCTV atau laptop kasir) saat melakukan pemeriksaan bersama teknis.

> 📷 **[SS-P12] Connected Devices** · simpan sebagai `docs/img/panduan-12-devices.png`

---

## 7. Laporan

### 7.1 Activity Log

Catatan kejadian pengguna secara kronologis: siapa login, logout, dan kapan. Ini rujukan utama saat menindaklanjuti klaim pelanggan ("kemarin saya beli, kok sudah habis?").

> 📷 **[SS-P13] Activity Log** · simpan sebagai `docs/img/panduan-13-activity-log.png`

### 7.2 Sales Report

Rekapitulasi penjualan voucher per periode — dasar penyusunan rekap harian/mingguan untuk pemilik.

> 📷 **[SS-P14] Sales Report** · simpan sebagai `docs/img/panduan-14-sales-report.png`

---

## 8. Logos (Branding)

Menu **Logos** mengatur identitas visual aplikasi dan materi cetak: logo utama, logo versi putih untuk latar gelap/hijau, serta aset pendukung lainnya. Unggah gambar dengan latar transparan (PNG/WebP) agar tampil rapi di header maupun struk.

> 📷 **[SS-P15] Halaman Logos** · simpan sebagai `docs/img/panduan-15-logos.png`

---

## 9. Penggunaan di Ponsel

Seluruh fitur tetap nyaman diakses dari HP. Susunan bilah atas versi mobile:

```
┌─────────────────────────────────────┐
│ [☰]        LOGO           avatar 👤 │
│  kiri      tengah            kanan  │
└─────────────────────────────────────┘
```

- **☰ (kiri)** membuka menu sebagai drawer geser;
- **Logo (tengah)** kembali ke Dashboard lokasi;
- **Avatar (kanan)** membuka menu akun: Settings · Disconnect · Logout.

> 📷 **[SS-P16] Header Mobile + Drawer Terbuka** · simpan sebagai `docs/img/panduan-16-mobile.png`
> Ambil: tampilan HP dengan drawer menu dalam keadaan terbuka.

---

## 10. FAQ dan Troubleshooting

| Gejala | Kemungkinan Penyebab | Yang Dilakukan |
|---|---|---|
| Router berstatus Offline/Error | Listrik mati, kabel longgar, IP berubah, port API tertutup | Periksa fisik lokasi; samakan IP/port di form router di Home; minta teknis cek firewall bila berlanjut |
| Muncul kartu kuning *"password needs to be re-entered"* | Password router diganti di luar MIVO | Edit router di Home, masukkan password terbaru |
| Voucher gagal login | Salah ketik, sudah terpakai, masa aktif habis | Cari kodenya di menu **Vouchers**; cek status & validity; bila perlu uji dengan kode baru |
| KPI/grafik kosong padahal router online | Data sampling belum cukup | Klik **↻ Refresh**, tunggu beberapa menit |
| Tamu tidak mendapat halaman login | Belum tepat tersambung ke SSID, atau perangkat bermasalah | Pastikan tersambung ke WiFi yang benar; coba matikan-nyalakan WiFi HP; bila berlanjut, laporkan ke teknis |
| Notifikasi tidak muncul | Halaman terbuka terlalu lama | Segarkan halaman (F5 / tarik-turun) |
| Lupa password admin | — | Hubungi admin teknis untuk reset |

**Aturan emas:** sebelum panik, klik **↻ Refresh** — sebagian besar indikator basi hanya butuh penyegaran.

---

## 11. Glosarium

| Istilah | Arti |
|---|---|
| **Session / Lokasi** | Ruang kerja satu cabang/titik WiFi dalam MIVO |
| **Captive Portal** | Gerbang login wajib yang muncul bagi tamu WiFi |
| **Data Plan** | Paket layanan: kecepatan, masa aktif, dan harga |
| **Rate Limit (Rx/Tx)** | Batas kecepatan unduh/unggah tamu |
| **Validity** | Masa hidup voucher sejak pertama kali dipakai |
| **Quick Print** | Mode cetak voucher instan untuk kasir |
| **Online Users** | Tamu yang sedang terhubung saat ini |
| **Connected Devices** | Seluruh perangkat yang dikenali router |
| **KPI** | Angka ringkasan performa (pengguna, penjualan, pendapatan) |
| **NOC** | Layar pemantauan pusat — di MIVO berupa halaman Home |

---

*Dokumen pendamping: `LAPORAN-PROYEK.md` (laporan proyek untuk pimpinan) · `PANDUAN-PELANGGAN.md` (panduan singkat untuk pelanggan pengguna voucher).*
