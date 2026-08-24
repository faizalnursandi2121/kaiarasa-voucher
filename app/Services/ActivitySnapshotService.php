<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Config;
use App\Libraries\RouterOSAPI;

/**
 * Active Users snapshot service untuk Session Dashboard.
 *
 * RouterOS tidak menyimpan riwayat jumlah user aktif — service ini
 * merekam snapshot lokal (sampler 5 menit: lazy saat dashboard dibuka,
 * atau cron via scripts/sample-activity.php).
 */
class ActivitySnapshotService
{
    private string $session;

    public function __construct(string $session)
    {
        $this->session = $session;
    }

    /** Hitung user aktif live dari RouterOS. Null bila unreachable. */
    public function countActiveNow(): ?int
    {
        $config = (new Config)->getSession($this->session);
        if (! $config) {
            return null;
        }

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

    /**
     * Lazy sampling: rekam snapshot bila terakhir lebih tua dari 4 menit.
     * Dipanggil saat dashboard dibuka; cron script memakai recordNow().
     */
    public function sampleIfStale(): void
    {
        $db = Database::getInstance()->getConnection();
        $st = $db->prepare('SELECT MAX(recorded_at) t FROM session_activity_snapshots WHERE session_name = ?');
        $st->execute([$this->session]);
        $last = $st->fetchColumn();

        if ($last && (time() - strtotime($last)) < 240) {
            return; // masih fresh
        }

        $n = $this->countActiveNow();
        if ($n !== null) {
            $this->recordNow($n);
        }
    }

    public function recordNow(?int $activeUsers): void
    {
        if ($activeUsers === null) {
            return;
        }

        Database::getInstance()->getConnection()
            ->prepare('INSERT INTO session_activity_snapshots (session_name, active_users) VALUES (?, ?)')
            ->execute([$this->session, $activeUsers]);
    }

    /**
     * Series untuk chart User Activity.
     *
     * @param  string  $mode  'today' (intraday per snapshot) | 'days' (harian avg/max, default 7 hari)
     * @return array<int, array{label:string, value:int, avg?:int, max?:int}>
     */
    public function getSeries(string $mode, int $days = 7): array
    {
        $db = Database::getInstance()->getConnection();

        if ($mode === 'today') {
            $st = $db->prepare("SELECT strftime('%H:%M', recorded_at) label, active_users v
                FROM session_activity_snapshots
                WHERE session_name = ? AND date(recorded_at) = date('now','localtime')
                ORDER BY recorded_at");
            $st->execute([$this->session]);

            return array_map(fn ($r) => ['label' => $r['label'], 'value' => (int) $r['v']], $st->fetchAll(\PDO::FETCH_ASSOC));
        }

        $days = max(1, min(30, $days));
        $st = $db->prepare("SELECT date(recorded_at) d, ROUND(AVG(active_users)) avg_v, MAX(active_users) max_v
            FROM session_activity_snapshots
            WHERE session_name = ? AND date(recorded_at) >= date('now','localtime', ?)
            GROUP BY date(recorded_at) ORDER BY d");
        $st->execute([$this->session, '-'.($days - 1).' day']);

        return array_map(fn ($r) => [
            'label' => $r['d'],
            'value' => (int) $r['avg_v'],
            'avg' => (int) $r['avg_v'],
            'max' => (int) $r['max_v'],
        ], $st->fetchAll(\PDO::FETCH_ASSOC));
    }
}
