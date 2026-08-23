# Home v2.1 — Router CRUD Modal + Settings Config-only + Templates/Logos per-Session

> **For agentic workers:** superpowers:subagent-driven-development. `- [ ]` checkbox steps.
**Spec:** docs/superpowers/specs/2026-08-22-home-login-redesign-design.md → AMANDEMEN 2 (R1–R5)
**Verifikasi:** manual (php -l, curl JSON, screenshot headless dgn sesi admin). `npm run build` setelah ubah view.

### Task 1: Backend — skema per-session + router CRUD mode JSON
- [ ] Migrations.php: `ALTER TABLE voucher_templates ADD COLUMN session_id INTEGER NULL REFERENCES routers(id)`; sama utk `logos`; (SQLite ALTER ADD COLUMN ok). Pastikan idempotent (cek pragma table_info sebelum alter ATAU try/catch duplicate column)
- [ ] Perbarui scripts/migrate-home-v2.php agar tetap jalan; eksekusi; verifikasi kolom via `.schema`
- [ ] SettingsController store/update/delete: deteksi JSON request (`str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')` atau header X-Requested-With) → response JSON `{success:true,message:'...',router:{...}}` / `{success:false,message,error?}` + status code tepat (422 validasi); non-JSON = perilaku lama
- [ ] VoucherTemplateController & Logo controller: query list tambahkan `WHERE session_id = :sid OR session_id IS NULL`, badge default utk global; create mengisi session_id dari route param saat berada di konteks session
- [ ] Routes baru (auth group): `/{session}/voucher-templates*` (index/preview/add/store/edit) memakai middleware router.valid seperti dashboard; `/{session}/logos`
- [ ] Route lama `/settings/voucher-templates*` & `/settings/logos` → redirect 302 ke `/{firstSession}/...` (prioritas quick_access=1)
- [ ] Verifikasi curl JSON add/edit/delete router + akses routes baru; commit

### Task 2: ✅ SELESAI — Frontend Home — modal CRUD + row actions ⋮
- [ ] home.php: hapus tile Routers; tabel Actions → tombol ⋮ dropdown per baris (Open/Edit/Test Connection/Delete merah+confirm); klik baris tetap buka dashboard
- [ ] Modal Add Router (markup form = fields settings/add existing) + submit fetch JSON; sukses → tutup modal, toast SweetAlert, load(true) refresh tabel
- [ ] Modal Edit Router (prefill dari data baris) + Delete dengan konfirmasi SweetAlert (#26) → DELETE JSON
- [ ] UI States: loading spinner tombol, validation inline 422, empty/error states
- [ ] npm run build + verifikasi headless authenticated (add/edit/delete nyata di DB dev) + commit

### Task 3: Settings config-only + sidebar dashboard
- [ ] View settings routers dirapikan/dihapus dari nav Settings; navbar/settings link templates+logos dihapus; pastikan /settings tetap menampilkan System/CORS/Plugins
- [ ] sidebar_session.php: grup "Voucher" → Voucher Templates + Logos (URL session-scoped); restyle konsisten design.md
- [ ] Route lama redirect bekerja (bookmark tidak mati); commit

### Task 4: Verifikasi akhir menyeluruh
- [ ] Alur penuh: home add/edit/delete router via modal · settings bersih · templates+logos terbuka dari sidebar dashboard dgn fallback global · redirect lama · screenshots light mode
- [ ] Commit akhir
