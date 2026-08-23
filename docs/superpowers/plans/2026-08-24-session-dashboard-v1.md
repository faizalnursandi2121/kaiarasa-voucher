# Session Dashboard V1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mengubah `/{session}/dashboard` menjadi operational control panel untuk receptionist/operator — 4 KPI, Quick Actions, Recent Activity, 3 chart ApexCharts — plus regroup sidebar operasional. Tanpa info teknis router (CPU/RAM/HDD/board/uptime/traffic).

**Architecture:** `ActivitySnapshotService` (tabel lokal + sampler 5-menit lazy/cron) untuk histori Active Users; `SalesReportService::getTodaySummary()` (dari Plan sales-report-v1) untuk Sold/Revenue; activity feed derived dari user creation data + RouterOS hotspot log; Chart.js dihapus, semua chart ApexCharts reuse helper Home.

**Tech Stack:** PHP views, ApexCharts (vendor existing), Tailwind build (`npm run build`), SQLite (snapshots), Lucide.

**Prasyarat:** Plan `2026-08-24-sales-report-v1.md` sudah dieksekusi (SalesReportService tersedia).

**Spec induk:** brief user + `docs/design.md` §6/§8 + spec sales report.

---

## File Structure

- Modify: `app/Database/Migrations.php` (+1 tabel snapshot)
- Create: `app/Services/ActivitySnapshotService.php` — record/getSeries/lazy-sample
- Create: `scripts/sample-activity.php` — CLI sampler (cron opsional)
- Modify: `app/Controllers/DashboardController.php` — payload operasional baru
- Rewrite: `app/Views/dashboard.php` — layout operasional penuh (ApexCharts)
- Modify: `app/Views/layouts/sidebar_session.php` — grup ACCESS/ACTIVITY/SALES/ADMINISTRATION + terminology
- Modify: `public/lang/en.json` — key label baru
- Delete usage: `<script src="/assets/js/chart.min.js">` dari dashboard

---

### Task 1: Migration — tabel session_activity_snapshots

**Files:** Modify `app/Database/Migrations.php`

- [ ] **Step 1: Tambah migration idempotent**

Ikuti pattern existing di Migrations.php (cek pragma table_info / try-catch duplicate):

```php
// Session dashboard: active-users snapshots (sampler 5 menit)
try {
    $db->exec("CREATE TABLE IF NOT EXISTS session_activity_snapshots (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        session_name TEXT NOT NULL,
        active_users INTEGER NOT NULL DEFAULT 0,
        recorded_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
    )");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_sas_session_time
               ON session_activity_snapshots(session_name, recorded_at)");
} catch (\Throwable $e) { /* already exists */ }
```

- [ ] **Step 2: Jalankan migrasi**

Run: `php scripts/migrate-home-v2.php` (atau entry migrate yang ada)
Expected: tanpa error; verifikasi `sqlite3 app/Database/database.sqlite ".schema session_activity_snapshots"`

- [ ] **Step 3: Commit**

```bash
git add app/Database/Migrations.php && git commit -m "feat(db): tabel session_activity_snapshots"
```

---

### Task 2: ActivitySnapshotService

**Files:** Create `app/Services/ActivitySnapshotService.php`

- [ ] **Step 1: Implement service**

```php
<?php

namespace App\Services;

use App\Core\Database;

class ActivitySnapshotService
{
    public function __construct(private string $session) {}

    /** Hitung user aktif live dari RouterOS. Null bila unreachable. */
    public function countActiveNow(): ?int
    {
        $config = (new \App\Models\Config)->getSession($this->session);
        if (! $config) return null;
        try {
            $api = RouterOSAPI::fromSession($config);
            $api->attempts = 1;
            $api->timeout = 5;
            if (! $api->connect($config['ip_address'], $config['username'], $config['password'])) {
                return null;
            }
            $active = $api->comm('/ip/hotspot/active/print');
            $api->disconnect();

            return is_array($active) && ! isset($active['!trap']) ? count($active) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Lazy sampling: rekam snapshot bila terakhir > 4 menit. */
    public function sampleIfStale(): void
    {
        $db = Database::getInstance()->getConnection();
        $last = $db->prepare('SELECT MAX(recorded_at) t FROM session_activity_snapshots WHERE session_name=?');
        $last->execute([$this->session]);
        $t = $last->fetchColumn();
        if ($t && (time() - strtotime($t)) < 240) return;

        $n = $this->countActiveNow();
        if ($n === null) return;
        $ins = $db->prepare('INSERT INTO session_activity_snapshots (session_name, active_users) VALUES (?,?)');
        $ins->execute([$this->session, $n]);
    }

    /** Series untuk chart: mode today (intraday) | days=N (daily avg/max). */
    public function getSeries(string $mode, int $days = 7): array
    {
        $db = Database::getInstance()->getConnection();
        if ($mode === 'today') {
            $st = $db->prepare("SELECT strftime('%H:%M', recorded_at) label, active_users v
                                FROM session_activity_snapshots
                                WHERE session_name=? AND date(recorded_at)=date('now','localtime')
                                ORDER BY recorded_at");
            $st->execute([$this->session]);

            return array_map(fn($r) => ['label'=>$r['label'],'value'=>(int)$r['v']], $st->fetchAll());
        }

        $st = $db->prepare("SELECT date(recorded_at) d, ROUND(AVG(active_users)) avg_v,
                                   MAX(active_users) max_v
                            FROM session_activity_snapshots
                            WHERE session_name=? AND date(recorded_at) >= date('now','localtime', ?)
                            GROUP BY date(recorded_at) ORDER BY d");
        $st->execute([$this->session, '-'.($days - 1).' day']);

        return array_map(fn($r) => ['label'=>$r['d'],'avg'=>(int)$r['avg_v'],'max'=>(int)$r['max_v']], $st->fetchAll());
    }
}
```

Catatan: `RouterOSAPI::fromSession` dipakai persis seperti service lain; demo session tidak connect (lihat Task 5 override).

- [ ] **Step 2: php -l + commit**

Run: `php -l app/Services/ActivitySnapshotService.php`
Commit: `feat(dashboard): ActivitySnapshotService (lazy sampler + series)`

---

### Task 3: CLI sampler (cron opsional)

**Files:** Create `scripts/sample-activity.php`

- [ ] **Step 1: Script**

```php
#!/usr/bin/env php
<?php
/** Sampler Active Users per sesi router. Cron: *\/5 * * * *
 *  Usage: php scripts/sample-activity.php [session ...]   (tanpa arg = semua sesi) */
require __DIR__.'/../vendor/autoload.php';

use App\Core\Database;
use App\Services\ActivitySnapshotService;

$sessions = array_slice($argv, 1);
if (! $sessions) {
    $rows = Database::getInstance()->getConnection()
        ->query('SELECT session_name FROM routers')->fetchAll();
    $sessions = array_column($rows, 'session_name');
}

foreach ($sessions as $s) {
    $svc = new ActivitySnapshotService($s);
    $n = $svc->countActiveNow();
    echo $s.': '.($n ?? 'unreachable')."\n";
    // record langsung (bypass stale-check utk cron)
    if ($n !== null) {
        $db = Database::getInstance()->getConnection();
        $db->prepare('INSERT INTO session_activity_snapshots (session_name, active_users) VALUES (?,?)')
           ->execute([$s, $n]);
    }
}
```

Jika vendor/autoload tidak ada (tanpa composer install), ganti require manual ke autoloader framework yang dipakai index.php (cek `public/index.php`).

- [ ] **Step 2: Smoke test demo/offline**

Run: `php scripts/sample-activity.php demo` → output `demo: unreachable` (tanpa crash) adalah OK.

- [ ] **Step 3: Commit**

```bash
git add scripts/sample-activity.php && git commit -m "feat(dashboard): CLI activity sampler (cron opsional)"
```

---

### Task 4: DashboardController — payload operasional

**Files:** Modify `app/Controllers/DashboardController.php`

- [ ] **Step 1: Ganti method index (non-demo & demo) agar membangun payload baru**

Payload shape (dikirim ke view sebagai `$data`):

```php
$data = [
    'session' => $session,
    'kpis' => [
        'active_users' => <int|null>,                 // live API (cache via snapshot terbaru)
        'sold_today' => <int>,                        // SalesReportService getTodaySummary
        'revenue_today' => <int>,                     // idem
        'created_today' => <int>,                     // derive created-today (Task 4 Step 2)
    ],
    'quick_actions' => [
        ['label'=>'Generate Vouchers','icon'=>'ticket-plus','href'=>"/$session/hotspot/generate"],
        ['label'=>'Quick Print','icon'=>'printer','href'=>"/$session/quick-print"],
        ['label'=>'Create User','icon'=>'user-plus','href'=>"/$session/hotspot/users/add"],
    ],
    'activity' => [ /* list item: icon,label,detail,ago */ ],
    'charts' => [
        'user_activity' => ['mode'=>'today'|'days','series'=>[...]],  // ActivitySnapshotService
        'voucher_activity' => ['today_vs_yesterday'=>['created'=>x,'prev'=>y], 'daily'=>[...]],
        'top_packages' => [['name','count']],
    ],
    'unreachable' => <bool>,
];
```

- [ ] **Step 2: Helper created-today & top-packages & activity feed**

Tambahkan private methods:

```php
private function getCreatedStats(array $records): array
{
    $today = date('Y-m-d');
    $yest = date('Y-m-d', strtotime('-1 day'));
    $todayN = 0; $yestN = 0; $byPkg = [];
    foreach ($records as $r) {
        if (($r['sale_type'] ?? '') === 'manual_user' && empty($r['billable'])) continue;
        // hitung issuance types saja
        if (! in_array($r['sale_type'], ['bulk_generate','quick_print'], true)) continue;
        if (($r['date'] ?? null) === $today) {
            $todayN++;
            $byPkg[$r['profile']] = ($byPkg[$r['profile']] ?? 0) + 1;
        }
        if (($r['date'] ?? null) === $yest) $yestN++;
    }
    arsort($byPkg);

    return ['today'=>$todayN,'yesterday'=>$yestN,'top_packages'=>$byPkg];
}
```

Sumber records: `(new SalesReportService($session))->getVoucherRecords(isset($_GET['refresh']))` — normalisasi sudah menyediakan sale_type/date/billable.

Activity feed (derived, tanpa fabrikasi):

```php
private function getActivityFeed(array $records): array
{
    $items = [];
    foreach ($records as $r) {
        if (($r['date'] ?? null) !== date('Y-m-d')) continue;
        if ($r['sale_type'] === 'quick_print') {
            $items[] = ['icon'=>'printer','text'=>'Voucher printed','detail'=>$r['profile'],'ts'=>null];
        } elseif ($r['sale_type'] === 'bulk_generate') {
            $items[] = ['icon'=>'ticket-plus','text'=>'Voucher generated','detail'=>$r['profile'],'ts'=>null];
        } elseif (! empty($r['billable'])) {
            $items[] = ['icon'=>'user-plus','text'=>'User account created','detail'=>$r['profile'],'ts'=>null];
        }
    }
    usort($items, fn($a,$b) => strcmp($b['detail'] ?? '', $a['detail'] ?? ''));

    return array_slice($items, 0, 12);
}
```

Feed = gabungan dua sumber (best-effort, tanpa fabrikasi):
1) Creation events (kode di atas): berbasis creation-date hari ini, tanpa jam.
2) Connection events: reuse pattern `LogController::index` — `$API->comm('/log/print',
   ['?topics' => 'hotspot,info,debug'])`, ambil baris hari-ini:
   - mengandung 'logged in'  -> icon `log-in`,  "User connected",    detail nama user
   - mengandung 'logged out' -> icon `log-out`, "User disconnected", detail nama user
Gabungkan, urut stabil, slice 12 item. Bila log buffer kosong/rotated, feed tetap
tampil dengan creation-events saja.

- [ ] **Step 3: Demo branch** — session `demo`: `active_users=25`, records dari `SalesReportService::demoRaw()` dinormalisasi, snapshots sintetis dibuat on-the-fly bila tabel kosong (INSERT 48 titik intraday nilai 18–30).

- [ ] **Step 4: Hapus payload lama** (resource/board/uptime/traffic vars) dan `chart.min.js` include di view (Task 5 ikut rewrite view).

- [ ] **Step 5: php -l + commit**

```bash
git add app/Controllers/DashboardController.php && git commit -m "feat(dashboard): payload operasional (KPI/quick-actions/activity/charts)"
```

---

### Task 5: Rewrite dashboard.php

**Files:** Rewrite `app/Views/dashboard.php`

- [ ] **Step 1: Struktur view (urutan visual disetujui)**

```
page-header: "Dashboard" + lokasi/session aktif (chip nama session)
grid KPI 4 kartu — markup persis pattern yang sudah diimplementasi:
```
<div class="rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19] p-5">
  <div class="flex items-center gap-3 mb-4">
    <span class="w-11 h-11 rounded-full flex items-center justify-center {chip}">
      <i data-lucide="{icon}" class="w-5 h-5 {iconColor}"></i>
    </span>
    <span class="text-sm font-semibold">{Label}</span>
  </div>
  <p class="text-3xl font-bold tabular-nums leading-none">{value}</p>
</div>
```
Chips: Active=emerald-500/10+text-emerald-600 · Sold=sage #5f7f67/10+#47614d ·
Revenue=#92aa96/15 · Created=amber-500/10+text-amber-600. Nilai: formatCurrency utk Revenue.
grid lg:grid-cols-3:
  [col-span-1] QUICK ACTIONS card: 3 link vertikal, tiap item:
```
<a href="{href}" class="flex items-center gap-3 rounded-xl border border-black/[.07] dark:border-white/[.08] p-3.5 hover:bg-[#5f7f67]/[.08] hover:border-[#5f7f67]/40 transition-colors">
  <i data-lucide="{icon}" class="w-5 h-5 text-[#47614d] dark:text-[#92aa96]"></i>
  <span class="text-[13px] font-semibold">{label}</span>
</a>
```
  [col-span-2] RECENT ACTIVITY card: list ikon+teks+detail (empty state "No activity yet.")
grid lg:grid-cols-2:
  USER ACTIVITY card (toggle Today/7D pills; area chart)
  VOUCHER ACTIVITY card (pill Today / Last 7 Days / Last 30 Days; Today→dua bar compare today vs yesterday; 7D & 30D→bar harian dari `charts.voucher_activity.daily[]`)
TOP PACKAGES donut full-width (atau col-span-2 + kolom list paket kanan)
```

Semua card: `rounded-2xl border border-black/[.07] dark:border-white/[.08] bg-white dark:bg-[#1a1c19]`.

- [ ] **Step 2: Charts ApexCharts inline**

Salin helper dari home.php ke script block: `baseChart(), gridColor(), isDark()` + render:

```js
// User Activity
new ApexCharts(el, baseChart({
  chart:{type:'area',height:220}, colors:['#5f7f67'],
  series:[{name:'Active users',data:UA.series.map(p=>[p.label,p.value])}],
  xaxis:{categories:UA.labels}, stroke:{curve:'smooth',width:2},
}));
// Voucher Activity (7D bar)
colors per bar: today vs yesterday -> dua series sederhana
// Top Packages donut: labels=pkg names, series=counts, colors sage family ['#5f7f67','#92aa96','#6b8b73','#47614d','#c9d6cc']
```

Script include: `<script src="/assets/js/vendor/apexcharts.min.js"></script>` (HAPUS chart.min.js).

- [ ] **Step 3: Unreachable & empty states**

`$unreachable===true` → tiap KPI tampil `—` + banner amber tipis "Live data unavailable — router cannot be reached." + tombol Retry (`?refresh=1`). Chart kosong → pesan "Not enough data yet — activity is being sampled every few minutes." (jujur, tanpa data palsu).

- [ ] **Step 4: Lazy sampling hook** — sebelum render, controller memanggil `(new ActivitySnapshotService($session))->sampleIfStale();` (skip demo).

- [ ] **Step 5: php -l + npm run build (class baru) + smoke /demo/dashboard & router real**

- [ ] **Step 6: Commit**

```bash
git add app/Views/dashboard.php public/assets/css/styles.css
git commit -m "feat(dashboard): rewrite UI operasional — KPI/quick-actions/activity/charts (ApexCharts)"
```

---

### Task 6: Sidebar regroup + terminology

**Files:** Modify `app/Views/layouts/sidebar_session.php`, `public/lang/en.json`

- [ ] **Step 1: Grup baru (pakai pattern collapsible Hotspot existing)**

```
Dashboard
ACCESS      : User Accounts(/hotspot/users) · Vouchers(/hotspot/generate) · Access Packages(/hotspot/profiles)
ACTIVITY    : Active Users(/hotspot/active) · Connected Devices(/hotspot/hosts)
SALES       : Sales Report(/reports/sales)
              [deviasi D1 disetujui: entri "Transactions" ditunda sampai ada local transaction source]
ADMINISTRATION (collapsible):
   Voucher Templates · Logos · Network ▸ DHCP · Security ▸ IP Bindings, Walled Garden · System ▸ Scheduler, Reboot, Shutdown
footer: Quick Print shortcut tetap · Disconnect · Logout
```

Label English hardcoded via LanguageHelper::t fallback (keys baru ditambah en.json: `sidebar.access`, `sidebar.activity_group`, `sidebar.sales`, `sidebar.administration`, `access.user_accounts`, `access.vouchers`, `access.packages`, `activity.active_users`, `activity.devices`, `sales.report`).

- [ ] **Step 2: Aktif-state check update** — `$reportsPages` dsb. mengikuti href baru; hapus grup lama yang kosong (Reports lama, Network lama top-level, dst.) dengan memindahkan link ke ADMINISTRATION.

- [ ] **Step 3: json lint + php -l + commit**

```bash
php -r "json_decode(file_get_contents('public/lang/en.json'),true);" && php -l app/Views/layouts/sidebar_session.php
git commit -m "feat(sidebar): regroup operasional ACCESS/ACTIVITY/SALES/ADMINISTRATION + terminology"
```

---

### Task 7: Final verification

- [ ] `/demo/dashboard` — semua widget terisi fixture, charts render light+dark
- [ ] Router real — KPI live, snapshot masuk tabel, unreachable state saat kabel dicabut
- [ ] Sidebar semua link tepat target; tidak ada istilah MikroTik di level atas
- [ ] `grep -n "chart.min.js" app/Views/dashboard.php` → 0 hasil
- [ ] Commit final + push

