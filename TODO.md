# TODO — Eksekusi Plan Redesign Login + Home

Plan: `docs/superpowers/plans/2026-08-22-home-login-redesign.md`
Mode: Subagent-driven (implementer → spec review → quality review per task)

- [x] Task 1: `docs/design.md` — design system document
- [x] Task 2: Service + endpoint `/api/routers/health` (cache TTL 60s)
- [x] Task 3: Rewrite view home (fleet monitor)
- [x] Task 4: Restyle header layout (hapus titik-titik)
- [x] Task 5: Rewrite view login (split layout)
- [ ] Task 6: Verifikasi akhir menyeluruh
- [ ] Final review seluruh implementasi

## Deferred (disepakati, bukan bagian pilot)
- [ ] Spinner saat submit login (spec §3 — deferral sadar)
- [ ] `last_online` pada kartu router offline (spec §4 — butuh pelacakan backend)
- [ ] Restyle sidebar global dengan token baru (spec §7.5 — menyusul bersama dashboard)
- [ ] Alignment kecil: alpha border design.md (.10/.08) vs view (.07); tinggi tombol login h-11 vs token h-10
