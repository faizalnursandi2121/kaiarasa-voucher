# Sales Report V1 + Domain Reconciliation — Design

## Domain Decision (approved)
- **Issuance = Sale.** Bulk Generate dan Quick Print keduanya langsung terhitung
  Vouchers Sold + Revenue saat voucher diterbitkan.
- LEGACY SEMANTICS (deprecated): realized = QP + generated-that-is-used;
  unused-generated = "inventory". Dokumentasikan sebagai legacy; jangan pertahankan
  di UI ("Inventory Value" dihapus dari UI utama).
- Used = metrik pemakaian terpisah (issued → used → unused gap), tidak mempengaruhi sold/revenue.
- Sale types: `bulk_generate` | `quick_print` | `manual_user`.
  - `[QP]` di comment → quick_print
  - comment prefix `vc-/up-{batchId}-{date}` → bulk_generate
  - sisanya → manual_user; **billable hanya jika ada harga eksplisit di comment**
    (`p:|price:|harga:`) — profile-price default saja TIDAK membuat manual user menjadi sale.
- Undated records (ATURAN RANGE): bila agregasi memakai rentang tanggal
  (Today/7D/30D/Custom), record tanpa tanggal DIKECUALIKAN dari angka rentang
  dan dilaporkan via meta/data-quality note; bila TANPA rentang (all-time),
  undated ikut total. Tujuan: Dashboard "Today" ≡ Sales Report "Today" by
  construction. Jangan mengarang tanggal.
- Operator/payment/refund/timestamp-per-transaksi: tidak tersedia → kolom tidak ditampilkan.

## Consistency Rule
`SalesReportService` adalah SATU sumber kalkulasi untuk: Session Dashboard KPI,
Sales Report, Financial Report (migrated), Export. Dashboard ≡ Sales Report by construction.

## Architecture
- `app/Services/SalesReportService.php`
  - `fetchRecords($session)`: RouterOS `/ip/hotspot/user/print` + profile price map
    (reuse HotspotHelper::parseProfileMetadata) → normalized records; cache 60s
    (sys_get_temp_dir/mivo-sales-{session}.json).
  - `computeFromRecords(array $records, array $filters)` PURE — testable tanpa API.
  - Public API: getSummary/getRevenueTrend/getSalesVolume/getSalesByPackage/
    getSalesByType/getDailyBreakdown/getList + getTodaySummary (untuk Dashboard).
- Filters: start,end (Y-m-d) · package · sale_type.
- Record shape: username, profile, price, comment, sale_type, billable, date|null, used(bool).

## Semantics (compute)
- billable = sale_type !== manual_user OR explicit price marker in comment.
- Sold/Revenue aggregates HANYA billable && price>0.
- used = uptime>0 || bytes-out>0 (legacy definition dipertahankan sebagai usage metric).
- Undated: date===null → eksklusi dari agregasi BER-RANGE; ikut total hanya pada
  view all-time; counter `undated` + note UI.

## Sales Report Page (/reports/sales, alias lama /reports/financial redirect)
Filter bar: Range (Today/Yesterday/7D/30D/Custom) · Package · Sale Type.
KPI: Revenue · Vouchers Sold · Average Sale · Top Package.
Charts (ApexCharts): Revenue Trend (area, harian) · Vouchers Sold (bar) ·
Sales by Package (donut) · Sales by Type (2 stat cards).
Table: batch rows Date|Package|Qty|Unit Price|Total|Type (+Used qty) — search/sort/pagination
client-side; Export CSV; Print. Empty state: "No sales for this period" + Adjust filters.
Error state: badge amber "Sales data unavailable" (router unreachable), bukan nol.
Loading: skeleton per bagian.

## Tests (scripts/test-sales-report.php, tanpa dependensi PHPUnit)
Cover: bulk-only, qp-only, mixed, used-vs-unused, undated, zero-sales,
manual billable/non-billable, dashboard≡report consistency, unknown-date bucket,
type detection (marker/prefix/manual), price-detection precedence.

## Phases
A Service + tests → B Financial Report migration → C expose Today-summary API
(dipakai Dashboard pada task berikutnya) → D UI/charts/table/export →
E reconciliation vs legacy + demo fixture.

## Known Limitations (documented, not fabricated)
- Tanpa jam pada tanggal → tidak ada granularitas per-jam.
- Tanpa operator/payment/refund → kolom tidak ditampilkan.
- Manual user non-billable tetap terlihat di table (tipe manual_user, tanpa kontribusi revenue).
