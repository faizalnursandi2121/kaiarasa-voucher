# Catatan Deployment — Hotspot Kaiarasa + MIVO

> Checklist langkah yang harus dilakukan **setelah MIVO di-deploy** agar
> tab **Check** di halaman login hotspot berfungsi penuh.

Domain MIVO (staging): `https://captive-kaiarasa.stg.net.id`

---

## 1. Ganti `apiSession` di template

**Apa itu:** nama session/router yang didaftarkan di dashboard MIVO.
Template mengirim nama ini lewat header `X-Kaiarasa-Session` setiap kali
tab Check memanggil API. Server MIVO memakai nama ini untuk tahu router
mana yang harus ditanya.

**Langkah:**
1. Di dashboard MIVO, daftarkan router Kaiarasa (menu Routers/Sessions).
   Catat **nama session**-nya (misal: `kaiarasa-router`).
2. Edit `mikrotik templet/assets/js/main.js`:
   ```js
   window.MivoConfig = {
       apiBaseUrl: "https://captive-kaiarasa.stg.net.id",
       apiSession: "kaiarasa-router",   // ← ganti "my-router" dengan nama asli
       debugMode: false
   };
   ```
3. Upload ulang template ke MikroTik.

**Kalau dilewati:** tab Check muncul tapi selalu error
`HTTP 404 — Session not found`.

---

## 2. Tambah rule CORS di dashboard MIVO

**Apa itu:** browser memblokir request lintas-domain (halaman hotspot di
IP/domain MikroTik → API di domain MIVO) kecuali server mengizinkan.
CORS rule = izin tersebut.

**Langkah:**
1. Buka dashboard MIVO → **Settings → API CORS** (`/settings/api-cors`).
2. Tambah rule baru:
   - **Origin:** domain/IP halaman hotspot tamu.
     - Paling aman: isi origin yang sebenarnya (misal `http://10.5.50.1`
       atau domain captive portal).
     - Praktis: `*` (izinkan semua) — acceptable untuk endpoint publik
       read-only seperti voucher check.
   - **Methods:** `["GET","POST","OPTIONS"]`
   - **Headers:** `["Content-Type","X-Kaiarasa-Session"]`
     (default `["*"]` juga boleh)
3. Save.

**Kalau dilewati:** tab Check error `Failed to fetch` / CORS error di
console browser, padahal servernya hidup.

---

## 3. Walled Garden di MikroTik

**Apa itu:** daftar tujuan yang boleh diakses **sebelum** user login.
Tanpa ini, tamu yang belum login tidak bisa menghubungi server MIVO —
padahal tab Check justru dipakai sebelum login.

**Langkah (via terminal MikroTik):**
```
/ip hotspot walled-garden
add action=allow dst-host=captive-kaiarasa.stg.net.id
```

Atau via Winbox: **IP → Hotspot → Walled Garden → + (Add)**
- Action: `allow`
- Dst. Host: `captive-kaiarasa.stg.net.id`

**Catatan penting (HTTPS):**
- Karena API pakai HTTPS, browser butuh resolve DNS + TLS handshake ke
  server MIVO. Walled garden `dst-host` bekerja di layer 7 (HTTP Host /
  SNI), jadi biasanya cukup.
- Kalau masih gagal, alternatif: allow IP server MIVO di walled garden
  IP (`/ip hotspot walled-garden ip add action=allow dst-address=<IP>
  dst-port=443`).
- Pastikan sertifikat SSL domain valid (sudah, karena pakai domain
  staging dengan TLS).

**Kalau dilewati:** tab Check timeout / tidak bisa connect saat user
belum login.

---

## 4. Upload template ke MikroTik + test

**Langkah:**
1. Zip isi folder `mikrotik templet/` (file HTML + assets + xml +
   favicon + api.json — struktur folder harus dipertahankan).
2. Upload ke MikroTik: **Winbox → Files → drag & drop** (atau `/file`
   via FTP).
3. Ekstrak/replace folder `hotspot` di router dengan isi template.
4. Test di device asli:
   - [ ] Buka WiFi tamu → captive portal muncul (login.html)
   - [ ] Login pakai voucher → alogin → redirect → internet jalan
   - [ ] status.html tampil (uptime, kuota, disconnect)
   - [ ] logout.html tampil setelah disconnect
   - [ ] Tab Check: masukkan kode voucher → data muncul (setelah #1–#3)
   - [ ] Trial 60 menit berfungsi (cek `trial-uptime=1h` di
     `/ip hotspot user profile`)
   - [ ] Test di HP Android (Chrome) dan iPhone (Safari)

---

## Ringkasan alur tab Check

```
Tamu buka login.html → klik tab Check → ketik kode voucher
   ↓
Browser: GET https://captive-kaiarasa.stg.net.id/api/voucher/check/<kode>
         header: X-Kaiarasa-Session: <nama session>
   ↓  (butuh: walled garden #3 + CORS #2 + session valid #1)
MIVO: cek voucher di router via API MikroTik → balas JSON
   ↓
Template: tampilkan sisa waktu / kuota voucher
```

## Status

| # | Item | Status |
|---|------|--------|
| 1 | Ganti `apiSession` | ⏳ Menunggu router didaftarkan di MIVO |
| 2 | Rule CORS | ⏳ Menunggu MIVO deploy |
| 3 | Walled garden | ⏳ Menunggu MIVO deploy |
| 4 | Upload + test | ⏳ Template sudah siap (commit `9cfb1e1`, `56dc1b8`) |
