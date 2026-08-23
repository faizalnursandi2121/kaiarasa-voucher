<?php

require __DIR__.'/../app/Services/SalesReportService.php';

use App\Services\SalesReportService;

$pass = 0; $fail = 0;
function t(string $name, callable $fn): void
{
    global $pass, $fail;
    try {
        $fn();
        $pass++;
        echo "PASS  {$name}\n";
    } catch (Throwable $e) {
        $fail++;
        echo "FAIL  {$name}: {$e->getMessage()}\n";
    }
}
function eq($a, $b): void
{
    if ($a !== $b) {
        throw new Exception(var_export($a, true).' !== '.var_export($b, true));
    }
}

// ---------- fixtures ----------
function rec(array $over = []): array
{
    return array_merge([
        'name' => 'v'.rand(1000, 9999), 'profile' => '1 Day', 'price' => 0,
        'comment' => '', 'uptime' => '0s', 'bytes-out' => 0,
    ], $over);
}

// ---------- Task 1: deteksi & normalisasi ----------

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
    eq(SalesReportService::normalizeUser(rec(['uptime' => '1h2m']), [])['used'], true);
    eq(SalesReportService::normalizeUser(rec(['uptime' => '0s']), [])['used'], false);
    eq(SalesReportService::normalizeUser(rec(['uptime' => '0s', 'bytes-out' => 4096]), [])['used'], true);
});


// ---------- Task 2: computeFromRecords ----------
function datedRec(string $date, string $type, int $price, bool $used = false): array {
    $c = $type === 'quick_print' ? "p:{$price} [QP] {$date}" : "vc-B-{$date}- p:{$price}";
    return SalesReportService::normalizeUser(rec(['comment' => $c, 'profile' => '1 Day', 'uptime' => $used ? '1h' : '0s']), []);
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

t('bulk only: issued=sold walau unused; used tdk menambah sold', function () {
    $recs = [datedRec('2024-05-01','bulk_generate',5000), datedRec('2024-05-01','bulk_generate',5000)];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out['summary']['vouchers_sold'], 2);
    eq($out['summary']['revenue'], 10000);
    eq($out['summary']['used'], 0);
    $recs[0]['used'] = true;
    $out2 = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq([$out2['summary']['vouchers_sold'], $out2['summary']['used']], [2, 1]);
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
    eq($out['by_package'][0]['pct'], 100.0);
});

t('manual billable masuk, non-billable keluar dari sold', function () {
    $recs = [manualBillable(), manualFree()];
    $out = SalesReportService::computeFromRecords($recs, []);
    eq($out['summary']['vouchers_sold'], 1);
    eq($out['summary']['revenue'], 40000);
    eq($out['by_type']['manual_user']['count'], 1);
    eq($out['summary']['issued'], 0); // manual_user bukan issuance type (hanya bulk/QP)
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
    $byTypeIdx = [];
    foreach ($out['list'] as $i => $row) { $byTypeIdx[$row['sale_type']] = $i; }
    $bulk = $out['list'][$byTypeIdx['bulk_generate']];
    eq([$bulk['quantity'],$bulk['unit_price'],$bulk['total'],$bulk['used_count']],
       [2,5000,10000,0]);
    $qp = $out['list'][$byTypeIdx['quick_print']];
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

// ---------- Task 3 (ditarik ke depan): undated & range semantics ----------
function undatedQP(int $price): array {
    return SalesReportService::normalizeUser(rec(['comment' => "p:{$price} [QP]"]), []);
}

t('undated: all-time view -> ikut total', function () {
    $recs = [undatedQP(2500), datedRec('2024-05-01','bulk_generate',5000)];
    $out = SalesReportService::computeFromRecords($recs, []);
    eq($out['summary']['vouchers_sold'], 2);
    eq($out['summary']['revenue'], 7500);
    eq($out['meta']['undated_count'], 1);
    eq(count($out['revenue_trend']), 1);
});

t('undated: ranged view -> dikecualikan (konsistensi dashboard)', function () {
    $recs = [undatedQP(2500), datedRec('2024-05-01','bulk_generate',5000)];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out['summary']['vouchers_sold'], 1);
    eq($out['summary']['revenue'], 5000);
    eq($out['meta']['undated_count'], 1);
    eq($out['summary']['sold_dated'], 1);
});

t('range filter membuang di luar rentang', function () {
    $recs = [datedRec('2024-04-01','bulk_generate',5000), datedRec('2024-05-01','quick_print',3000)];
    $out = SalesReportService::computeFromRecords($recs, ['start'=>'2024-05-01','end'=>'2024-05-31']);
    eq($out['summary']['vouchers_sold'], 1);
    eq($out['summary']['revenue'], 3000);
});

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
