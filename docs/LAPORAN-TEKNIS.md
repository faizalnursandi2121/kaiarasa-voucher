# MIVO — Laporan Teknis Aplikasi

**Versi Dokumen:** 1.0 · Agustus 2026
**Sifat:** Dokumen internal — gambaran arsitektur dan mekanisme kerja aplikasi

---

## Daftar Isi

1. [Pendahuluan](#1-pendahuluan)
2. [Arsitektur Sistem](#2-arsitektur-sistem)
3. [Teknologi Inti](#3-teknologi-inti)
4. [Konsep Multi-Lokasi](#4-konsep-multi-lokasi)
5. [Mekanisme Captive Portal](#5-mekanisme-captive-portal)
6. [Manajemen Voucher dan Profil Layanan](#6-manajemen-voucher-dan-profil-layanan)
7. [Keamanan](#7-keamanan)
8. [Deployment dan Operasional](#8-deployment-dan-operasional)
9. [Penutup](#9-penutup)

---

## 1. Pendahuluan

### 1.1 Apa itu MIVO

MIVO adalah **aplikasi web manajemen jaringan WiFi Hotspot** yang dirancang untuk mengelola perangkat router MikroTik secara terpusat. Melalui satu antarmuka, operator dapat membuat dan mencetak voucher akses, menentukan paket kecepatan, memantau status router dari banyak lokasi, serta membaca laporan aktivitas pengguna — tanpa perlu masuk ke konsol router satu per satu.

Aplikasi dibangun dari nol dengan prinsip **ringan, cepat, dan mudah dioperasikan**: cukup berjalan di satu server kecil, mampu melayani banyak lokasi sekaligus, dan seluruh konfigurasi dilakukan lewat browser.

### 1.2 Masalah yang Diselesaikan

| Tanpa MIVO | Dengan MIVO |
|---|---|
| Konfigurasi hotspot dilakukan manual via Winbox/konsol | Semua operasi rutin lewat antarmuka web |
| Voucher ditulis/dicetak manual satu per satu | Generate ratusan voucher sekaligus + cetak massal |
| Status tiap router dicek berkunjung antar lokasi | Dashboard NOC: semua router terpantau real-time |
| Tidak ada catatan penjualan harian | KPI pendapatan & penjualan otomatis per lokasi |
| Tamu bebas akses jaringan sebelum login sulit dikendalikan | Walled Garden & IP Bindings dikelola dari satu tempat |

### 1.3 Fitur Utama

- **Multi-lokasi (multi-session):** satu instalasi mengelola banyak lokasi/cabang secara terisolasi.
- **Manajemen voucher:** template, generate massal, Quick Print, dan riwayat pemakaian.
- **Data Plan / Profil Hotspot:** kendali kecepatan unggah/unduh serta masa aktif voucher.
- **Pemantauan router:** status daring/luring, beban CPU, ketersediaan jaringan, notifikasi kejadian.
- **Captive portal dengan halaman login kustom** milik sendiri.
- **Keamanan jaringan pra-login:** Walled Garden dan IP Bindings.
- **Laporan:** aktivitas pengguna, aktivitas voucher, paket terlaris, pendapatan harian.
- **Antarmuka responsif:** penuh nyaman dipakai di desktop maupun ponsel.

---

## 2. Arsitektur Sistem

### 2.1 Gambaran Umum

MIVO berjalan sebagai **aplikasi web sisi-server** pada satu mesin (server/VPS), berkomunikasi dengan router melalui jalur resmi RouterOS API.

```
 ┌──────────────┐      HTTPS       ┌─────────────────────────────┐
 │  Browser     │ ◄─────────────►  │  SERVER MIVO                │
 │  Operator /  │                  │  ┌───────────────────────┐  │
 │  Admin       │                  │  │ Aplikasi MIVO         │  │
 └──────────────┘                  │  │ (antarmuka + logika)  │  │
                                   │  ├───────────────────────┤  │
                                   │  │ Database SQLite       │  │
                                   │  └───────────┬───────────┘  │
                                   └──────────────┼──────────────┘
                                                  │ RouterOS API (opsi SSL)
                                   ┌──────────────▼──────────────┐
                                   │  ROUTER MIKROTIK            │
                                   │  · Hotspot & captive portal │
                                   │  · Halaman login kustom     │
                                   └──────────────┬──────────────┘
                                                  │ WiFi
                                        ┌─────────▼─────────┐
                                        │  TAMU / PELANGGAN │
                                        └───────────────────┘
```

### 2.2 Pemisahan Lapisan

Aplikasi mengikuti pola pemisahan tanggung jawab yang bersih:

| Lapisan | Tanggung Jawab |
|---|---|
| **Presentasi** | Templat tampilan (HTML/CSS) — kerangka layout sesi, kartu, tabel, grafik |
| **Logika Bisnis** | Controller & helper — aturan voucher, validasi, olah data monitoring |
| **Data** | Basis data SQLite — pengguna admin, daftar router, voucher, log aktivitas |
| **Integrasi** | Klien RouterOS API — jembatan perintah/pembacaan data ke router |

Pemisahan ini membuat setiap perubahan aman dan terlokalisir: misalnya memperbaiki tampilan tidak akan menyentuh logika penjualan voucher.

### 2.3 Siklus Request

Setiap interaksi operator mengikuti alur yang sama:

1. Browser mengirim permintaan (mis. buka dashboard lokasi).
2. Komponen router aplikasi memetakan URL ke controller yang tepat, sambil memvalidasi sesi login.
3. Controller membaca/menulis data lokal bila perlu, lalu — jika informasi live dibutuhkan — mengambilnya dari router via API.
4. Hasil digabungkan ke dalam templat tampilan dan dikirim balik sebagai halaman utuh.
5. Interaksi ringan (buka dropdown, muat notifikasi) ditangani JavaScript di browser tanpa memuat ulang halaman.

---

## 3. Teknologi Inti

| Teknologi | Peran dalam MIVO | Alasan Dipilih |
|---|---|---|
| **PHP 8** | Mesin aplikasi sisi-server | Matang, luas didukung hosting, performa baik |
| **SQLite** | Basis data tertanam (satu file) | Nol instalasi, cadangan semudah menyalin file, ideal skala ini |
| **Tailwind CSS** | Kerangka desain antarmuka | Konsistensi visual, ukuran keluaran kecil, tema terkunci terang |
| **ApexCharts** | Grafik interaktif (area, batang, donat) | Cantik, responsif, ringan |
| **Lucide Icons** | Ikonografi antarmuka | Seragam dan modern |
| **RouterOS API** | Jalur komunikasi resmi ke MikroTik | Dukungan penuh fitur hotspot; tersedia mode terenkripsi SSL |
| **Docker** | Pengemasan & deployment | Sekali bangun, jalan di mana saja; upgrade dan migrasi otomatis |

**Prinsip umum:** MIVO sengaja *tidak* bergantung pada layanan eksternal tambahan (tidak butuh server basis data terpisah, tidak butuh antrean pesan). Semua yang dibutuhkan ada dalam satu kontainer — meminimalkan titik gagal dan biaya operasional.

---

## 4. Konsep Multi-Lokasi

Satu instalasi MIVO dapat melayani **banyak lokasi** (disebut *session*). Setiap lokasi memiliki ruang kerja sendiri yang sepenuhnya terisolasi:

```
                        ┌────────────────────┐
                        │      MIVO          │
                        └─────────┬──────────┘
           ┌──────────────────────┼──────────────────────┐
           ▼                      ▼                      ▼
    ┌─────────────┐        ┌─────────────┐        ┌─────────────┐
    │  Lokasi A   │        │  Lokasi B   │        │  Lokasi C   │
    │ /loksi-a/.. │        │ /lokasi-b/..│        │ /lokasi-c/..│
    │ Router A1   │        │ Router B1   │        │ Router C1   │
    │ Voucher A   │        │ Voucher B   │        │ Voucher C   │
    │ Laporan A   │        │ Laporan B   │        │ Laporan C   │
    └─────────────┘        └─────────────┘        └─────────────┘
```

**Karakteristik:**

- Alamat tiap lokasi berpola `/nama-lokasi/dashboard`, `/nama-lokasi/vouchers`, dst.
- Voucher, profil, laporan, dan router hanya terlihat di lokasinya masing-masing.
- Halaman **Home** bertindak sebagai pusat kendali (NOC) lintas lokasi: status seluruh router dalam satu layar.
- Operator dapat berpindah lokasi atau keluar dari sesi lokasi (*Disconnect*) melalui menu profil.

**Manfaat bisnis:** cocok untuk operator RT/RW Net, hotel, kafe berjaringan, hingga ISP lokal dengan banyak titik — satu staf pusat mengelola semuanya.

> 📷 **[SS-T01] Halaman Home (NOC)** — simpan sebagai `docs/img/teknis-01-home.png`
> Ambil: halaman Home lengkap saat beberapa router berstatus bervariasi (online/offline) agar tabel status terlihat informatif.

---

## 5. Mekanisme Captive Portal

Bab ini menjelaskan bagaimana tamu mendapatkan akses internet, serta peran MIVO di setiap tahap.

### 5.1 Prinsip Captive Portal

*Captive portal* adalah mekanisme "gerbang" WiFi: tamu yang baru tersambung **belum bisa** menjelajah sampai berhasil melewati halaman login. Router MikroTik melakukan ini secara native — koneksi tamu dicegat dan dialihkan ke halaman login.

### 5.2 Peran Halaman Login Kustom

MIVO menggunakan **halaman login kustom milik sendiri** (bukan halaman bawaan router), sehingga:

- Identitas visual (logo, warna, bahasa) konsisten dengan merek operator;
- Pengalaman tamu lebih ramah — instruksi jelas, tampilan rapi di ponsel;
- Tetap tersambung penuh ke mekanisme autentikasi hotspot router: kode voucher yang divalidasi adalah user hotspot yang dikelola MIVO.

### 5.3 Alur Lengkap Tamu

```
 TAMU                          ROUTER MIKROTIK                 MIVO
 ────                          ───────────────                 ────
 1. Sambung WiFi        →  DHCP memberi alamat IP
 2. Membuka situs apa   →  Koneksi dicegat, dialihkan
    pun                     ke halaman login kustom
 3. Halaman LOGIN tampil ←  (situs tertentu tetap bisa
    di perangkat tamu          diakses pra-login: Walled
                               Garden — dikelola MIVO)
 4. Memasukkan KODE     →  Autentikasi user hotspot  ←  Voucher dibuat &
    VOUCHER                                          dicetak dari MIVO
 5. SESI AKTIF          →  Kecepatan & masa aktif    ←  Data Plan (profil)
                           mengikuti profil              didefinisikan di MIVO
 6. Menikmati internet  →  Kejadian sesi tercatat    →  Dashboard, grafik,
                           (login, offline, dsb.)        notifikasi 🔔
```

### 5.4 Akses Pra-Login: Walled Garden dan IP Bindings

| Fitur | Fungsi | Contoh Pemakaian |
|---|---|---|
| **Walled Garden** | Menetapkan situs/layanan yang tetap terjangkau **sebelum** login | Mobile banking, WhatsApp, halaman status |
| **IP Bindings** | Mengecualikan perangkat tertentu agar **tanpa login sama sekali** | CCTV, server kasir, perangkat langganan tetap |

Keduanya dikelola dari menu **Security** di MIVO; perubahan langsung disinkronkan ke router melalui API.

### 5.5 Pengendalian Pasca-Login

Setelah voucher diterima, pengalaman tamu sepenuhnya dibentuk oleh **Data Plan (profil hotspot)** yang didefinisikan di MIVO: kecepatan unduh/unggah, masa aktif voucher (*validity*), dan perilaku kedaluwarsa. Artinya strategi komersial (paket 3 jam, harian, mingguan) cukup diatur sekali di aplikasi.

### 5.6 Monitoring Kejadian Portal

Peristiwa penting di router — tamu baru terhubung, router mati/listrik, CPU tinggi — mengalir ke MIVO dan tampil sebagai **notifikasi** (ikon lonceng) serta **Recent Activity** di dashboard. Operator tahu ada gangguan bahkan sebelum pelanggan protes.

> 📷 **[SS-T02] Dashboard Lokasi** — simpan sebagai `docs/img/teknis-02-dashboard.png`
> Ambil: dashboard lokasi dengan KPI terisi + grafik aktif.
>
> 📷 **[SS-T03] Walled Garden** — simpan sebagai `docs/img/teknis-03-walled-garden.png`
> Ambil: menu Security → Walled Garden dengan beberapa rule contoh.

---

## 6. Manajemen Voucher dan Profil Layanan

### 6.1 Siklus Hidup Voucher

```
 TEMPLATE  →  GENERATE  →  CETAK  →  DISTRIBUSI  →  TERPAKAI  →  BERAKHIR
 (format &   (massal,     (Quick     (kasir/jualan)  (tamu login   (validity
  harga)      batch)       Print/                     di portal)    habis/
                            template)                                 auto-expire)
```

- **Template** menentukan format cetak dan identitas visual struk.
- **Generate** membuat banyak kode sekaligus mengikuti Data Plan tertentu.
- **Quick Print** mencetak instan untuk kebutuhan kasir harian.
- Seluruh peristiwa (dibuat, terjual hari ini, dipakai) tercatat otomatis menjadi KPI.

### 6.2 Data Plan / Profil Hotspot

Setiap paket layanan didefinisikan sebagai profil dengan parameter komersial:

| Parameter | Arti |
|---|---|
| **Rate Limit (Rx/Tx)** | Batas kecepatan unduh & unggah — satuan fleksibel Kbps/Mbps/Gbps |
| **Validity** | Lama hidup voucher sejak pertama dipakai (jam/hari/minggu) |
| **Expired Mode** | Perilaku saat masa aktif habis (hapus, pindah grup, dsb.) |
| **Harga** | Nilai jual — menjadi dasar KPI *Revenue Today* |

---

## 7. Keamanan

| Aspek | Mekanisme |
|---|---|
| Akun operator | Login terpusat; kata sandi disimpan dalam bentuk *hash*, tidak pernah polos |
| Kredensial router | Disimpan **terenkripsi** di basis data; bila kunci berubah, aplikasi meminta entri ulang secara proaktif |
| Isolasi lokasi | Data antar lokasi tidak saling bocor; navigasi dijaga sesi aktif |
| Keluaran antarmuka | Semua data dinamis disaring sebelum dirender (anti-injeksi markup) |
| Jalur router | Komunikasi API dapat menggunakan enkripsi SSL |
| Saran operasional | Akses aplikasi melalui HTTPS (reverse proxy), dan lakukan pencadangan berkalá file basis data |

---

## 8. Deployment dan Operasional

**Model penyebaran:** MIVO dikemas sebagai **kontainer Docker** tunggal.

- Berjalan di port **8081** (diatur lewat variabel lingkungan platform seperti Dokploy).
- Basis data berada pada **volume persisten** — kontainer boleh di-recreate, data tetap aman.
- **Migrasi skema otomatis** dijalankan saat kontainer boot: versi baru langsung siap pakai tanpa langkah manual.
- Kebutuhan sumber daya minim — VPS kelas kecil (1 vCPU / 1 GB RAM) sudah memadai untuk belasan lokasi.

**Rutinitas operasional yang disarankan:**

1. Cadangkan volume basis data secara berkala (harian/mingguan).
2. Pantau notifikasi router offline; pastikan uptime listrik lokasi.
3. Setelah pembaruan versi, hard-refresh browser untuk memuat aset terbaru.

---

## 9. Penutup

MIVO merangkum seluruh kompleksitas pengelolaan hotspot MikroTik multi-lokasi ke dalam satu aplikasi web yang ringan: dari pembuatan voucher, gerbang captive portal berhalaman login kustom, keamanan pra-login, sampai laporan penjualan harian. Arsitektur satu-kontainer dengan basis data tertanam membuatnya murah dijalankan dan mudah dirawat — sementara integrasi RouterOS API memastikan apa yang dilihat operator selalu mencerminkan kondisi jaringan yang sebenarnya.

---

*Lampiran dokumentasi terkait: `PANDUAN-PENGGUNA.md` (buku panduan operasional harian).*
