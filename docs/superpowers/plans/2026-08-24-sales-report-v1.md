# Sales Report V1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sales Report page (`/{session}/reports/sales`) + `SalesReportService` sebagai satu sumber kalkulasi Sold/Revenue (semantika baru: issuance = sale) untuk Dashboard, Sales Report, Financial Report, dan Export.

**Architecture:** Service murni (`computeFromRecords` tanpa I/O, testable offline) + lapisan fetch/cache 60s ala RouterHealthService. ReportController & halaman baru menjadi consumer. Chart.js dibuang dari konteks ini; chart memakai ApexCharts (pola Home). Semantika legacy realized/inventory diganti issuance-based; Used jadi metrik terpisah.

**Tech Stack:** PHP 8+, Tailwind (build via `npm run build`), ApexCharts (vendor existing), Lucide, SQLite hanya untuk framework bootstrap (tidak dipakai service), test runner standalone PHP (tanpa PHPUnit).

**Spec:** `docs/superpowers/specs/2026-08-24-sales-report-design.md`

---

## File Structure

- Create: `app/Services/SalesReportService.php` — deteksi tipe/tanggal/harga + normalisasi record + agregasi murni + fetch/cache/demo
- Create: `scripts/test-sales-report.php` — runner test standalone (assert manual)
- Modify: `app/Controllers/ReportController.php` — delegasi ke service (index/export), method legacy dihapus
- Modify: `app/Views/reports/financial.php` — relabel KPI/badge ke kosakata baru
- Create: `app/Views/reports/sales.php` — halaman Sales Report (filter/KPI/charts/table/export/print)
- Modify: `routes/web.php` — rute `/reports/sales` (+export); `/reports/financial` redirect permanen ke sales
- Modify: `app/Views/layouts/sidebar_session.php` — label & href Sales Report
- Modify: `public/lang/en.json` — `reports_menu.financial` → "Sales Report"

---

### Task 1: Service — deteksi & normalisasi (TDD)

**Files:**
- Create: `app/Services/SalesReportService.php`
- Test: `scripts/test-sales-report.php`

- [ ] **Step 1: Tulis test yang gagal**

Buat `scripts/test-sales-report.php`:

```php
<?php
require __DIR__.'/../app/Services/SalesReportService.php';

use App\Services\SalesReportService;

$pass = 0; $fail = 0;
function t(string $name, callable $fn): void {
    global $pass, $fail;
    try { $fn(); $pass++; echo "PASS  {$name}\n"; }
    catch (Throwable $e) { $fail++; echo "FAIL  {$name}: {$e->getMessage()}\n"; }
}
function eq($a, $b): void {
    if ($a !== $b) throw new Exception(var_export($a, true).' !== '.var_export($b, true));
}

// ---------- fixtures ----------
function rec(array $over = []): array {
    return array_merge([
        'name' => 'v'.rand(1000,9999), 'profile' => '1 Day', 'price' => 0,
        'comment' => '', 'uptime' => '0s', 'bytes-out' => 0,
    ], $over);
}

t('detectSaleType: [QP] marker', function () {
    eq(SalesReportService::detectSaleType('p:5000 [QP] 2024-05-01'), 'quick_print');
});
t('detectSaleType: vc- prefix bulk_generate', function () {
    eq(SalesReportService::detectSaleType('vc-AB123-2024-05-01 promo'), 'bulk_generate');
});
t('detectSaleType: up- prefix bulk_generate', function () {
    eq(SalesReportService::detectSaleType('up-ZZ9-2024-05-01'), 'bulk_generate');
});
t('detectSaleType: manual fallback', function () {
    eq(SalesReportService::detectSaleType('tamu hotel rina'), 'manual_user');
});

t('parseDate: Y-m-d', function () {
    eq(SalesReportService::parseDate('p:2000 [QP] 2024-05-01'), '2024-05-01');
});
t('parseDate: d/m/y two-digit year', function () {
    eq(SalesReportService::parseDate('vc-A-1/2/24- x'), '2024-02-01');
});
t('parseDate: no date -> null', function () {
    eq(SalesReportService::parseDate('tanpa tanggal'), null);
});

t('normalizeUser: price precedence comment > profile > K-notation', function () {
    $map = ['1 Day' => 5000];
    $r = SalesReportService::normalizeUser(rec(['comment' => 'p:7000 [QP]', 'profile' => '1 Day']), $map);
    eq($r['price'], 7000);
    $r = SalesReportService::normalizeUser(rec(['comment' => '[QP]', 'profile' => '1 Day']), $map);
    eq($r['price'], 5000);
    $r = SalesReportService::normalizeUser(rec(['comment' => '', 'profile' => '3h K10']), []);
    eq($r['price'], 10000);
});

t('billable: qp & bulk selalu; manual hanya dgn harga eksplisit', function () {
    $qp = SalesReportService::normalizeUser(rec(['comment' => 'p:3000 [QP]']), []);
    eq($qp['billable'], true);
    $bulk = SalesReportService::normalizeUser(rec(['comment' => 'vc-B1-2024-05-01']), []);
    eq($bulk['billable'], true);
    $manualPriced = SalesReportService::normalizeUser(rec(['comment' => 'harga 40000 tamu vip', 'profile' => 'none']), []);
    eq([$manualPriced['sale_type'], $manualPriced['billable']], ['manual_user', true]);
    $manualPlain = SalesReportService::normalizeUser(rec(['comment' => 'tamu', 'profile' => '1 Day']), ['1 Day' => 5000]);
    eq([$manualPlain['sale_type'], $manualPlain['billable']], ['manual_user', false]);
});

t('used detection', function () {
    eq(SalesReportService::normalizeUser(rec(['uptime' => '1h2m']))['used'], true);
    eq(SalesReportService::normalizeUser(rec(['uptime' => '0s']))['used'], false);
    eq(SalesReportService::normalizeUser(rec(['uptime' => '0s', 'bytes-out' => 4096]))['used'], true);
});

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
```

- [ ] **Step 2: Run → gagal**

Run: `php scripts/test-sales-report.php`
Expected: `PHP Fatal error: require ... Failed opening .../SalesReportService.php`

- [ ] **Step 3: Implementasi minimal service**

Buat `app/Services/SalesReportService.php`:

```php
<?php

namespace App\Services;

class SalesReportService
{
    /** Deteksi sale type dari comment voucher. */
    public static function detectSaleType(string $comment): string
    {
        if (strpos($comment, '[QP]') !== false) {
            return 'quick_print';
        }
        if (preg_match('/^(vc|up)-/', $comment)) {
            return 'bulk_generate';
        }

        return 'manual_user';
    }

    /** Parse tanggal dari comment (legacy format support). Null bila tidak ada. */
    public static function parseDate(string $comment): ?string
    {
        if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $comment, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }
        if (preg_match('/\b(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{2,4})\b/', $comment, $m)) {
            $p1 = intval($m[1]); $p2 = intval($m[2]); $y = intval($m[3]);
            $y = $y < 100 ? $y + 2000 : $y;
            if ($p1 > 12) {
                return sprintf('%04d-%02d-%02d', $y, $p2, $p1);
            }

            return sprintf('%04d-%02d-%02d', $y, $p1, $p2);
        }

        return null;
    }

    /** Harga: comment override -> profile map -> notasi K pada nama profile. */
    public static function detectPrice(array $user, array $profileMap): int
    {
        $comment = $user['comment'] ?? '';
        if (preg_match('/\b(?:p|price|harga)\s*[:-]?\s*(\d+)/i', $comment, $m)) {
            return intval($m[1]);
        }
        $profile = $user['profile'] ?? 'default';
        if (isset($profileMap[$profile])) {
            return intval($profileMap[$profile]);
        }
        if (preg_match('/(\d+)k\b/i', $profile, $m)) {
            return intval($m[1]) * 1000;
        }

        return 0;
    }

    public static function hasExplicitPriceMarker(string $comment): bool
    {
        return (bool) preg_match('/\b(?:p|price|harga)\s*[:-]?\s*(\d+)/i', $comment);
    }

    /** Normalisasi satu baris user RouterOS menjadi record penjualan. */
    public static function normalizeUser(array $user, array $profilePriceMap): array
    {
        $comment = (string) ($user['comment'] ?? '');
        $saleType = self::detectSaleType($comment);
        $price = self::detectPrice($user, $profilePriceMap);

        $explicit = self::hasExplicitPriceMarker($comment);
        $billable = $saleType !== 'manual_user'
            ? true
            : ($explicit && $price > 0);

        $uptime = (string) ($user['uptime'] ?? '0s');
        $bytesOut = $user['bytes-out'] ?? 0;

        return [
            'name' => (string) ($user['name'] ?? ''),
            'password' => (string) ($user['password'] ?? ''),
            'profile' => (string) ($user['profile'] ?? 'default'),
            'price' => $price,
            'comment' => $comment,
            'sale_type' => $saleType,
            'billable' => $billable,
            'date' => self::parseDate($comment),
            'used' => ($uptime !== '' && $uptime !== '0s') || (is_numeric($bytesOut) && $bytesOut > 0),
            'uptime' => $uptime,
        ];
    }

    // computeFromRecords & agregasi menyusul di Task 2.
}
```

- [ ] **Step 4: Run tests → PASS**

Run: `php scripts/test-sales-report.php`
Expected: `11 passed, 0 failed`

- [ ] **Step 5: Commit**

```bash
git add app/Services/SalesReportService.php scripts/test-sales-report.php
git commit -m "feat(sales): SalesReportService — deteksi tipe/tanggal/harga + normalisasi"
```

---

### Task 2: computeFromRecords — agregasi inti (TDD)

**Files:**
- Modify: `app/Services/SalesReportService.php`
- Modify: `scripts/test-sales-report.php`

- [ ] **Step 1: Tulis test agregasi**

Tambahkan ke `scripts/test-sales-report.php` sebelum blok echo akhir:

```php
// ---------- computeFromRecords ----------
function datedRec(string $date, string $type, int $price, bool $used = false): array {
    $c = $type === 'quick_print' ? "p:{$price} [QP] {$date}" : "vc-B-{$date}- p:{$price}";
    $r = SalesReportService::normalizeUser(rec(['comment' => $c, 'profile' => '1 Day', 'uptime' => $used ? '1h' : '0s']), []);
    return $r;
}
function undatedQP(int $price): array {
    return SalesReportService::normalizeUser(rec(['comment' => "[QP]"]), []); // tanpa tanggal
}
function manualBillable(): array {
    return SalesReportService::normalizeUser(rec(['comment' => 'harga 40000 vip', 'profile' => '-']), []);
}
function manualFree(): array {
    return SalesReportService::normalizeUser(rec(['comment' => 'tamu gratis', 'profile' => '1 Day']), ['1 Day' => 5000]);
}

t('zero sales', function () {
    $out = SalesReportService::computeFromRecords([]);
    eq($out['summary']['revenue'], 0);
    eq($out['summary']['vouchers_sold'], 0);
    eq($out['summary']['avg_sale'], null);
    eq($out['summary']['top_package'], null);
});

t('bulk only: issued=sold walau unused', function () {
    $recs = [datedRec('2024-05-01','bulk_generate',5000), datedRec('2024-05-01','bulk_generate',5000)];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out['summary']['vouchers_sold'], 2);
    eq($out['summary']['revenue'], 10000);
    eq($out['summary']['issued'], 2);
    eq($out['summary']['used'], 0);
    eq($out['summary']['unused'], 2);
    // used tidak menambah sold
    $recs[0]['used'] = true;
    $out2 = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out2['summary']['vouchers_sold'], 2);
    eq($out2['summary']['used'], 1);
});

t('qp only', function () {
    $recs = [datedRec('2024-05-01','quick_print',3000,true)];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out['summary']['vouchers_sold'], 1);
    eq($out['by_type']['quick_print']['count'], 1);
    eq($out['by_type']['quick_print']['revenue'], 3000);
});

t('mixed + by_package pct', function () {
    $recs = [
        datedRec('2024-05-01','bulk_generate',5000),
        datedRec('2024-05-02','quick_print',3000,true),
        datedRec('2024-05-02','bulk_generate',5000),
    ];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out['summary']['revenue'], 13000);
    eq($out['by_package'][0]['name'], '1 Day');
    eq($out['by_package'][0]['count'], 3);
    eq($out['by_package'][0]['revenue'], 13000);
    eq($out['by_package'][0]['pct'], 100.0);
});

t('manual billable masuk, non-billable keluar dari sold', function () {
    $recs = [manualBillable(), manualFree()];
    $out = SalesReportService::computeFromRecords($recs, []);
    eq($out['summary']['vouchers_sold'], 1);
    eq($out['summary']['revenue'], 40000);
    eq($out['by_type']['manual_user']['count'], 1);
    // non-billable tetap tercatat sbg issued usage metric? tidak — issued hanya issuance types
    eq($out['summary']['issued'], 1);
});

t('undated: all-time view -> ikut total', function () {
    $recs = [undatedQP(2500), datedRec('2024-05-01','bulk_generate',5000)];
    $out = SalesReportService::computeFromRecords($recs, []); // tanpa range
    eq($out['summary']['vouchers_sold'], 2);
    eq($out['summary']['revenue'], 7500);
    eq($out['meta']['undated_count'], 1);
    eq(count($out['revenue_trend']), 1);
});

t('undated: ranged view -> dikecualikan + meta note (konsistensi dashboard)', function () {
    $recs = [undatedQP(2500), datedRec('2024-05-01','bulk_generate',5000)];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out['summary']['vouchers_sold'], 1);          // hanya dated dalam range
    eq($out['summary']['revenue'], 5000);
    eq($out['meta']['undated_count'], 1);
    eq(count($out['revenue_trend']), 1);
    eq($out['summary']['sold_dated'], 1);
});

t('range filter membuang di luar rentang', function () {
    $recs = [datedRec('2024-04-01','bulk_generate',5000), datedRec('2024-05-01','quick_print',3000)];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out['summary']['vouchers_sold'], 1);
    eq($out['summary']['revenue'], 3000);
});

t('avg_sale & top_package', function () {
    $recs = [datedRec('2024-05-01','bulk_generate',5000), datedRec('2024-05-01','quick_print',3000)];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out['summary']['avg_sale'], 4000);
    eq($out['summary']['top_package']['name'], '1 Day');
    eq($out['summary']['top_package']['count'], 2);
});

t('getList: batch rows', function () {
    $recs = [
        datedRec('2024-05-01','bulk_generate',5000),
        datedRec('2024-05-01','bulk_generate',5000),
        datedRec('2024-05-01','quick_print',3000,true),
    ];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq(count($out['list']), 2);
    $bulk = $out['list'][0];
    eq([$bulk['quantity'],$bulk['unit_price'],$bulk['total'],$bulk['sale_type'],$bulk['used_count']],
       [2,5000,10000,'bulk_generate',0]);
    $qp = $out['list'][1];
    eq($qp['quantity'], 1);
    eq($qp['used_count'], 1);
});

t('daily_breakdown', function () {
    $recs = [datedRec('2024-05-01','bulk_generate',5000), datedRec('2024-05-02','quick_print',3000)];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq(count($out['daily_breakdown']), 2);
    eq($out['daily_breakdown'][0]['date'], '2024-05-01');
    eq($out['daily_breakdown'][0]['revenue'], 5000);
});
```

- [ ] **Step 2: Run → gagal** (`Call to undefined method computeFromRecords`)

- [ ] **Step 3: Implementasi computeFromRecords**

Tambahkan ke `SalesReportService` (setelah normalizeUser):

```php
/**
 * MURNI (tanpa I/O): agregasi records hasil normalizeUser.
 * Filter: start,end (Y-m-d, hanya menyaring record bertanggal),
 *         package (nama profile), sale_type.
 * Undated billable: DIKECUALIKAN dari agregasi ber-range (Today/7D/Custom)
 * agar Dashboard ≡ Sales Report by construction; masuk total hanya pada
 * view all-time (tanpa start/end). Selalu dilaporkan meta.undated_count.
 */
public static function computeFromRecords(array $records, array $f = []): array
{
    $start = $f['start'] ?? null;
    $end = $f['end'] ?? null;
    $pkg = $f['package'] ?? null;
    $stype = $f['sale_type'] ?? null;

    $summary = ['revenue'=>0,'vouchers_sold'=>0,'avg_sale'=>null,'top_package'=>null,
                'issued'=>0,'used'=>0,'unused'=>0,'undated'=>0,
                'sold_dated'=>0,'revenue_dated'=>0];
    $byType = ['bulk_generate'=>['count'=>0,'revenue'=>0],
               'quick_print'=>['count'=>0,'revenue'=>0],
               'manual_user'=>['count'=>0,'revenue'=>0]];
    $pkgAgg = [];
    $daily = [];
    $batches = [];
    $undatedCount = 0;
    $totalRecords = 0;

    foreach ($records as $rec) {
        if ($pkg !== null && $rec['profile'] !== $pkg) continue;
        if ($stype !== null && $rec['sale_type'] !== $stype) continue;
        $totalRecords++;

        $isIssuance = in_array($rec['sale_type'], ['bulk_generate','quick_print'], true);
        if ($isIssuance) {
            $summary['issued']++;
            if ($rec['used']) $summary['used']++;
        }

        $inRange = $rec['date'] !== null
            && ($start === null || $rec['date'] >= $start)
            && ($end === null || $rec['date'] <= $end);

        $sold = $rec['billable'] && $rec['price'] > 0;
        if (! $sold) continue;

        $isUndated = $rec['date'] === null;
        $rangeActive = ($start !== null || $end !== null);

        // Undated keluar dari SEMUA agregasi ber-range (konsistensi dashboard)
        if ($isUndated && $rangeActive) { $undatedCount++; continue; }
        if ($isUndated) $undatedCount++;

        $summary['revenue'] += $rec['price'];
        $summary['vouchers_sold']++;
        if (! $isUndated) {
            $summary['sold_dated']++;
            $summary['revenue_dated'] += $rec['price'];
        }

        if ($isUndated) continue; // series/list/batch wajib bertanggal
        if ($start !== null && $rec['date'] < $start) continue;
        if ($end !== null && $rec['date'] > $end) continue;

        $byType[$rec['sale_type']]['count']++;
        $byType[$rec['sale_type']]['revenue'] += $rec['price'];

        if (! isset($pkgAgg[$rec['profile']])) {
            $pkgAgg[$rec['profile']] = ['name'=>$rec['profile'],'count'=>0,'revenue'=>0];
        }
        $pkgAgg[$rec['profile']]['count']++;
        $pkgAgg[$rec['profile']]['revenue'] += $rec['price'];

        if (! isset($daily[$rec['date']])) {
            $daily[$rec['date']] = ['date'=>$rec['date'],'revenue'=>0,'sold'=>0];
        }
        $daily[$rec['date']]['revenue'] += $rec['price'];
        $daily[$rec['date']]['sold']++;

        $refParts = explode(' ', trim($rec['comment']));
        $ref = $refParts[0] ?? '';
        $key = $rec['date'].'|'.$rec['sale_type'].'|'.$rec['profile'].'|'.$rec['price'].'|'.$ref;
        if (! isset($batches[$key])) {
            $batches[$key] = ['date'=>$rec['date'],'package'=>$rec['profile'],
                'quantity'=>0,'unit_price'=>$rec['price'],'total'=>0,
                'sale_type'=>$rec['sale_type'],'used_count'=>0,'reference'=>$ref];
        }
        $batches[$key]['quantity']++;
        $batches[$key]['total'] += $rec['price'];
        if ($rec['used']) $batches[$key]['used_count']++;
    }

    $summary['issued'] = max($summary['issued'], 0);
    $summary['unused'] = max($summary['issued'] - $summary['used'], 0);
    $summary['undated'] = $undatedCount;
    $summary['avg_sale'] = $summary['vouchers_sold'] > 0
        ? (int) round($summary['revenue'] / $summary['vouchers_sold']) : null;

    uasort($pkgAgg, fn($a,$b) => $b['count'] <=> $a['count']);
    $byPackage = array_values($pkgAgg);
    if (isset($byPackage[0])) {
        foreach ($byPackage as &$p) {
            $p['pct'] = $summary['vouchers_sold'] > 0
                ? round($p['count'] / $summary['vouchers_sold'] * 100, 1) : 0.0;
        }
        unset($p);
        $summary['top_package'] = ['name'=>$byPackage[0]['name'],'count'=>$byPackage[0]['count']];
    }

    usort($byPackage, fn($a,$b) => $b['count'] <=> $a['count']);
    $trend = array_values(array_map(fn($d) => $d, array_values($daily)));
    usort($trend, fn($a,$b) => strcmp($a['date'],$b['date']));
    $breakdown = array_map(fn($d) => ['date'=>$d['date'],'vouchers'=>$d['sold'],'revenue'=>$d['revenue']], $trend);

    $list = array_values($batches);
    usort($list, fn($a,$b) => [$b['date'],$a['reference']] <=> [$a['date'],$b['reference']]);

    ksort($byType);

    return [
        'filters' => ['start'=>$start,'end'=>$end,'package'=>$pkg,'sale_type'=>$stype],
        'meta' => ['total_records'=>$totalRecords,'undated_count'=>$undatedCount],
        'summary' => $summary,
        'by_type' => $byType,
        'by_package' => $byPackage,
        'revenue_trend' => $trend,
        'sales_volume' => $trend,
        'daily_breakdown' => $breakdown,
        'list' => $list,
    ];
}
```

- [ ] **Step 4: Run → PASS**

Run: `php scripts/test-sales-report.php`
Expected: semua PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/SalesReportService.php scripts/test-sales-report.php
git commit -m "feat(sales): computeFromRecords — agregasi issuance-based + filters"
```

---

### Task 3: Fetch layer + cache + demo fixture

**Files:**
- Modify: `app/Services/SalesReportService.php`

- [ ] **Step 1: Tambahkan fetch/cache/demo**

```php
    private string $session;

    public function __construct(string $session)
    {
        $this->session = $session;
    }

    private function cachePath(): string
    {
        return sys_get_temp_dir().'/mivo-sales-'.$this->session.'.json';
    }

    /** Ambil users + profile price map dari RouterOS. ['__unreachable'] bila gagal. */
    public function fetchRaw(): array
    {
        $configModel = new \App\Models\Config;
        $config = $configModel->getSession($this->session);
        if (! $config) {
            return ['__unreachable' => true];
        }

        try {
            $api = \App\Services\RouterOSAPI::fromSession($config);
            $api->attempts = 1;
            $api->timeout = 5;
            if (! $api->connect($config['ip_address'], $config['username'], $config['password'])) {
                return ['__unreachable' => true];
            }
            $users = $api->comm('/ip/hotspot/user/print');
            $profiles = $api->comm('/ip/hotspot/user/profile/print');
            $api->disconnect();
        } catch (\Throwable $e) {
            return ['__unreachable' => true];
        }

        $map = [];
        foreach ((array) $profiles as $p) {
            $meta = \App\Helpers\HotspotHelper::parseProfileMetadata($p['on-login'] ?? '');
            if (! empty($meta['price'])) {
                $map[$p['name']] = intval($meta['price']);
            }
        }

        return ['users' => (array) $users, 'price_map' => $map];
    }

    /** Records ternormalisasi + cache 60 detik. */
    public function getVoucherRecords(bool $force = false): array
    {
        $file = $this->cachePath();
        if (! $force && is_file($file)) {
            $json = json_decode((string) file_get_contents($file), true);
            if (isset($json['ts']) && (time() - $json['ts']) < 60) {
                return $json['payload'];
            }
        }

        if ($this->session === 'demo') {
            $raw = self::demoRaw();
        } else {
            $raw = $this->fetchRaw();
        }

        $payload = isset($raw['__unreachable'])
            ? ['__unreachable' => true]
            : array_map(
                fn($u) => self::normalizeUser($u, $raw['price_map']),
                array_values($raw['users'])
            );

        @file_put_contents($file, json_encode(['ts' => time(), 'payload' => $payload]));

        return $payload;
    }

    /** Entry point utama: records + agregasi terfilter. */
    public function getReport(array $filters = [], bool $force = false): array
    {
        $records = $this->getVoucherRecords($force);
        if (isset($records['__unreachable'])) {
            return ['__unreachable' => true];
        }

        return self::computeFromRecords($records, $filters);
    }

    /** Ringkasan hari-ini untuk Dashboard KPI (dated-only, konsisten dgn report Today). */
    public function getTodaySummary(bool $force = false): array
    {
        $today = date('Y-m-d');
        $rep = $this->getReport(['start' => $today, 'end' => $today], $force);
        if (isset($rep['__unreachable'])) {
            return ['__unreachable' => true];
        }

        return ['sold' => $rep['summary']['sold_dated'], 'revenue' => $rep['summary']['revenue_dated']];
    }

    /** Fixture session demo. */
    public static function demoRaw(): array
    {
        $today = date('Y-m-d');
        $yest = date('Y-m-d', strtotime('-1 day'));
        $users = [];
        for ($i = 0; $i < 8; $i++) {
            $users[] = ['name'=>'demo-q'.$i,'profile'=>'1 Hour','price'=>0,
                'comment'=>"p:3000 [QP] {$today}",'uptime'=>$i<3?'45m':'0s','bytes-out'=>0];
        }
        for ($i = 0; $i < 10; $i++) {
            $users[] = ['name'=>'demo-g'.$i,'profile'=>'1 Day','price'=>0,
                'comment'=>"vc-D1-{$today}- p:5000",'uptime'=>$i<4?'2h':'0s','bytes-out'=>0];
        }
        for ($i = 0; $i < 6; $i++) {
            $users[] = ['name'=>'demo-y'.$i,'profile'=>'3 Hours','price'=>0,
                'comment'=>"vc-Y2-{$yest}- p:3500",'uptime'=>'1h','bytes-out'=>2048];
        }
        $users[] = ['name'=>'demo-nodate','profile'=>'1 Day','price'=>0,'comment'=>'[QP]','uptime'=>'0s','bytes-out'=>0];

        return ['users' => $users, 'price_map' => ['1 Hour'=>3000,'1 Day'=>5000,'3 Hours'=>3500]];
    }
```

Catatan: fixture demo sengaja mengabaikan kolom `price` pada raw dan mengandalkan comment override agar jalur deteksi harga ikut teruji.

- [ ] **Step 2: Tambahkan test demo (offline, pure)**

```php
t('demo fixture: compute konsisten', function () {
    $raw = SalesReportService::demoRaw();
    $map = $raw['price_map'];
    $recs = array_map(fn($u) => SalesReportService::normalizeUser($u, $map), $raw['users']);
    $today = date('Y-m-d');
    $out = SalesReportService::computeFromRecords($recs, ['start'=>$today,'end'=>$today]);
    // QP today 8x3000 = 24000 ; Gen today 10x5000 = 50000 ; nodate excluded dari range
    eq($out['summary']['vouchers_sold'], 18);
    eq($out['summary']['revenue'], 74000);
});
```

- [ ] **Step 3: Run → PASS**

Run: `php scripts/test-sales-report.php`
Expected: semua PASS

- [ ] **Step 4: Commit**

```bash
git add app/Services/SalesReportService.php scripts/test-sales-report.php
git commit -m "feat(sales): fetch layer + cache 60s + demo fixture + getTodaySummary"
```

---

### Task 4: Migrasi ReportController + financial.php (Phase B)

**Files:**
- Modify: `app/Controllers/ReportController.php` (rewrite penuh, ±120 baris)
- Modify: `app/Views/reports/financial.php` (relabel)

- [ ] **Step 1: Rewrite ReportController**

Ganti isi `app/Controllers/ReportController.php` menjadi delegasi tipis (hapus getFinancialReportData/detectPrice legacy):

```php
<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SalesReportService;

class ReportController extends Controller
{
    /** Halaman Sales Report (menggantikan Financial Report). */
    public function sales($session)
    {
        $svc = new SalesReportService($session);
        $filters = [
            'start' => $_GET['start'] ?? null,
            'end' => $_GET['end'] ?? null,
            'package' => ($_GET['package'] ?? '') !== '' ? $_GET['package'] : null,
            'sale_type' => ($_GET['sale_type'] ?? '') !== '' ? $_GET['sale_type'] : null,
        ];

        $report = $svc->getReport($filters, isset($_GET['refresh']));
        $packages = [];
        if (! isset($report['__unreachable'])) {
            $packages = array_map(fn($p) => $p['name'], $report['by_package']);
        }

        return $this->view('reports/sales', [
            'session' => $session,
            'filters' => $filters,
            'packages' => $packages,
            'report' => $report,
        ]);
    }

    /** Legacy route: financial kini redirect ke Sales Report. */
    public function index($session)
    {
        header('Location: /'.$session.'/reports/sales');
        exit;
    }

    public function sellingExport($session, $type)
    {
        $svc = new SalesReportService($session);
        $filters = [
            'start' => $_GET['start'] ?? null,
            'end' => $_GET['end'] ?? null,
            'package' => ($_GET['package'] ?? '') !== '' ? $_GET['package'] : null,
            'sale_type' => ($_GET['sale_type'] ?? '') !== '' ? $_GET['sale_type'] : null,
        ];
        $report = $svc->getReport($filters);

        header('Content-Type: application/json');
        if (isset($report['__unreachable'])) {
            echo json_encode(['error' => 'Router unreachable']);
            exit;
        }

        if ($type === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sales-report.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date','Package','Quantity','Unit Price','Total','Sale Type','Used']);
            foreach ($report['list'] as $row) {
                fputcsv($out, [$row['date'],$row['package'],$row['quantity'],
                    $row['unit_price'],$row['total'],$row['sale_type'],$row['used_count']]);
            }
            fclose($out);
            exit;
        }

        echo json_encode($report['list']);
        exit;
    }
}
```

Hapus juga import tak terpakai (`HotspotHelper`, `Config`, `RouterOSAPI`, `Setting`, `Logo`, dsb.) sesuai sisa pemakaian.

- [ ] **Step 2: php -l**

Run: `php -l app/Controllers/ReportController.php && php -l app/Services/SalesReportService.php`
Expected: No syntax errors

- [ ] **Step 3: Relabel financial.php**

Di `app/Views/reports/financial.php`:
- Ganti teks KPI `Realized Income` → `Revenue`; sub-teks `(Quick Print + Used)` → `(Issued vouchers)`
- Ganti card `Inventory Value (Pending)` → `Used` (nilai `used` metrik netral ink, bukan yellow)
- Badge `SOLD` → `ISSUED`; `USED` tetap; hapus badge `SOLD OUT` branch
- Judul halaman `$page_desc`: "Voucher sales and revenue overview."

(Ini halaman legacy yang akan dialihkan; cukup relabel agar konsisten bila diakses.)

- [ ] **Step 4: Update routes**

Di `routes/web.php` ganti dua baris reports:

```php
        $router->get('/{session}/reports/sales', [ReportController::class, 'sales']);
        $router->get('/{session}/reports/sales/export/{type}', [ReportController::class, 'sellingExport']);
        $router->get('/{session}/reports/financial', function ($session) {
            header('Location: /'.rawurlencode($session).'/reports/sales');
            exit;
        });
```

(hapus baris lama index/financial & export financial)

- [ ] **Step 5: Commit**

```bash
git add app/Controllers/ReportController.php app/Views/reports/financial.php routes/web.php
git commit -m "feat(sales): migrasi Financial Report ke SalesReportService (issuance semantics)"
```

---

### Task 5: Halaman Sales Report UI

**Files:**
- Create: `app/Views/reports/sales.php`

- [ ] **Step 1: Tulis view lengkap**

Struktur (ikuti pola home.php untuk chart helpers):

- Header: title "Sales Report", subtitle "Track voucher sales and revenue for this location.", breadcrumbs
- Filter bar GET form: select Range (Today/Yesterday/Last 7 Days/Last 30 Days/Custom→input start&end date), select Package (opsi dari `$packages`), select Sale Type (All/Bulk Generate/Quick Print), tombol Apply + Refresh(`?refresh=1`)
- Unreachable: amber banner "Sales data unavailable — unable to retrieve data from the router." + tombol Retry; skip sisanya
- Empty (vouchers_sold===0): "No sales for this period." + link reset filter; skip charts
- Undated note: jika `meta.undated_count>0` → chip kuning kecil "{n} vouchers without a readable date are included in totals."
- 4 KPI cards (pattern stat-card Home): Revenue (formatCurrency), Vouchers Sold, Average Sale, Top Package
- Charts section grid 2 kolom: `<div id="chart-trend">`, `<div id="chart-volume">`, donut `<div id="chart-package">` full-width bawahnya
- By Type: dua kartu kecil angka Bulk Generate / Quick Print (+ manual_user bila >0)
- Daily Breakdown (range >= 7 hari): tabel ringkas `table-glass` Date | Vouchers | Revenue
  dari `$report['daily_breakdown']` (maks 31 baris), posisi sebelum Sales table
- Table `table-glass`: Date | Package | Quantity | Unit Price | Total | Type | Used; input search client-side; sort klik th (data-sort); pagination 15/baris
- Toolbar table: Export CSV (link `sales/export/csv?<query>`), Print (`onclick="window.print()"`)
- Script inline: helper `baseChart()/gridColor()/isDark()` disalin gaya home.php; render 3 ApexCharts dari `window.__SALES_DATA` (di-echo json_encode dari PHP); search/paginate/sort vanilla JS pada tbody rows

Skeleton loading: server-render sudah sinkron (data siap saat paint), jadi skeleton tidak diperlukan untuk load awal; gunakan spinner kecil pada tombol Apply saat submit (pola login submit).

- [ ] **Step 2: php -l + build**

Run: `php -l app/Views/reports/sales.php && npm run build`

- [ ] **Step 3: Verifikasi manual**

Login → buka `/demo/reports/sales` (fixture) → cek KPI/chart/table/filter/export CSV terunduh.

- [ ] **Step 4: Commit**

```bash
git add app/Views/reports/sales.php public/assets/css/styles.css
git commit -m "feat(sales): halaman Sales Report — KPI, charts ApexCharts, table, export"
```

---

### Task 6: Sidebar & i18n

**Files:**
- Modify: `app/Views/layouts/sidebar_session.php:45` → `$reportsPages = ['/reports/sales', '/reports/user-log'];`
- Modify: `app/Views/layouts/sidebar_session.php:310-311` → href `.../reports/sales`, aktif-check `/reports/sales`, label tetap pakai key `reports_menu.financial`
- Modify: `public/lang/en.json` → `"financial": "Sales Report"`

- [ ] Step 1: apply edits · [ ] Step 2: `php -l` sidebar + json lint (`php -r "json_decode(file_get_contents('public/lang/en.json'),true);"`) · [ ] Step 3: commit `"feat(sidebar): Financial Report -> Sales Report"`

---

### Task 7: Konsistensi Dashboard (Phase C stub) + Reconcile

- [ ] Tambahkan ke `DashboardController::index` (branch non-demo & demo):
```php
$salesSvc = new \App\Services\SalesReportService($session);
$data['today_sales'] = $salesSvc->getTodaySummary(isset($_GET['refresh']));
```
Belum dirender ke UI dashboard lama — dikonsumsi penuh oleh task redesign dashboard berikutnya; tersedia agar konsistensi by construction sudah teruji.
- [ ] Reconciliation: bandingkan `Sold Today` service vs hitungan manual dari fixture demo (18 / Rp74.000) — sudah tertutup test demo fixture.
- [ ] Commit: `feat(dashboard): expose getTodaySummary via controller (konsistensi KPI)`

---

### Task 8: Final verification

- [ ] `php scripts/test-sales-report.php` → all pass
- [ ] `php -l` semua file yang diubah
- [ ] Manual: `/demo/reports/sales` (fixtures) & router sungguhan (real API) light+dark
- [ ] Export CSV terbuka di spreadsheet; Print preview rapi
- [ ] Commit final + push

