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

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail > 0 ? 1 : 0);
