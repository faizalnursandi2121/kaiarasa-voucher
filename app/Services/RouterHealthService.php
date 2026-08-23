<?php

namespace App\Services;

use App\Helpers\EncryptionHelper;
use App\Libraries\RouterOSAPI;
use App\Models\Config;

/**
 * Probe kondisi semua router + cache hasil TTL 60 detik.
 * Catatan: probe berurutan per router (PHP sinkron) dengan timeout pendek;
 * cache menjaga agar load home tetap instan.
 */
class RouterHealthService
{
    private const CACHE_TTL = 60; // detik

    public function getHealth(bool $forceRefresh = false): array
    {
        if (! $forceRefresh) {
            $cached = $this->readCache();
            if ($cached !== null) {
                return $cached;
            }
        }

        $results = [];
        foreach ((new Config)->getAllSessions() as $session) {
            $row = $this->probeRouter($session);
            $results[] = $row;
        }

        $this->detectTransitions($results);
        $this->pruneHistory();

        // last_seen dihitung SETELAH semua probe tersimpan agar siklus
        // sekarang ikut terhitung.
        foreach ($results as &$row) {
            $lastSeen = $this->getLastSeen((int) $row['id']);
            // last_seen disimpan naive UTC (SQLite datetime('now')); parse eksplisit
            // sebagai UTC agar offset ISO-8601 benar walau PHP TZ server bukan UTC.
            $row['last_seen'] = $lastSeen !== null
                ? (new \DateTimeImmutable($lastSeen, new \DateTimeZone('UTC')))->format(DATE_ATOM)
                : null;
        }
        unset($row);

        $payload = [
            'checked_at' => time(),
            'checked_at_iso' => date('c'),
            'routers' => $results,
        ];

        $this->writeCache($payload);

        return $payload;
    }

    private function probeRouter(array $session): array
    {
        $row = [
            'id' => (int) $session['id'],
            'session_name' => $session['session_name'],
            'hotspot_name' => $session['hotspot_name'] ?? '',
            'ip_address' => $session['ip_address'] ?? '',
            'quick_access' => (int) ($session['quick_access'] ?? 0),
            'location' => $session['description'] ?? '',
            'last_seen' => null,
            'status' => 'offline',
            'cpu_load' => null,
            'mem_load' => null,
            'uptime' => null,
            'os_version' => null,
            'board_name' => null,
            'active_users' => null,
            'error' => null,
        ];

        if (! empty($session['password'])) {
            $session['password'] = EncryptionHelper::decrypt($session['password']);
        }

        $startedAt = microtime(true);

        try {
            $API = RouterOSAPI::fromSession($session);
            $API->attempts = 1;
            $API->timeout = 3;
            $API->delay = 0;

            if (! $API->connect($session['ip_address'], $session['username'], $session['password'])) {
                $row['error'] = 'Connection failed';
                $row['response_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
                $this->persistProbeLog((int) $session['id'], $row);

                return $row;
            }

            $resource = $API->comm('/system/resource/print');
            $activeUsers = $API->comm('/ip/hotspot/active/print');
            $API->disconnect();

            if (! is_array($resource) || isset($resource['!trap']) || empty($resource[0])) {
                $row['status'] = 'error';
                $row['error'] = 'Resource query failed';
                $row['response_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
                $this->persistProbeLog((int) $session['id'], $row);

                return $row;
            }

            $row['status'] = 'online';
            $row['cpu_load'] = isset($resource[0]['cpu-load']) ? (int) $resource[0]['cpu-load'] : null;
            $totMem = isset($resource[0]['total-memory']) ? (int) $resource[0]['total-memory'] : 0;
            $freeMem = isset($resource[0]['free-memory']) ? (int) $resource[0]['free-memory'] : 0;
            $row['mem_load'] = $totMem > 0 ? (int) round((($totMem - $freeMem) / $totMem) * 100) : null;
            $row['uptime'] = $resource[0]['uptime'] ?? null;
            $row['os_version'] = $resource[0]['version'] ?? null;
            $row['board_name'] = $resource[0]['board-name'] ?? null;
            $row['active_users'] = is_array($activeUsers) && ! isset($activeUsers['!trap']) ? count($activeUsers) : 0;
        } catch (\Throwable $e) {
            $row['status'] = 'error';
            $row['error'] = $e->getMessage();
        }

        $row['response_ms'] = (int) round((microtime(true) - $startedAt) * 1000);
        $this->persistProbeLog((int) $session['id'], $row);

        return $row;
    }

    /**
     * Simpan hasil probe ke riwayat. Kegagalan logging tidak boleh
     * mengganggu probing.
     */
    private function persistProbeLog(int $routerId, array $row): void
    {
        try {
            $db = \App\Core\Database::getInstance();
            $stmt = $db->getConnection()->prepare(
                'INSERT INTO router_probe_logs (router_id, checked_at, status, cpu_load, uptime, response_ms)
                 VALUES (:rid, datetime("now"), :status, :cpu, :uptime, :ms)'
            );
            $stmt->execute([
                ':rid' => $routerId,
                ':status' => $row['status'],
                ':cpu' => $row['cpu_load'],
                ':uptime' => $row['uptime'],
                ':ms' => $row['response_ms'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // logging must never break probing
            error_log('[probe-log] '.$e->getMessage());
        }
    }

    /**
     * Prune riwayat probe & event lebih tua dari 7 hari.
     * Kegagalan prune tidak boleh mengganggu health check.
     */
    private function pruneHistory(): void
    {
        try {
            $db = \App\Core\Database::getInstance();
            $pdo = $db->getConnection();
            // index tunggal utk delete tanpa router_id
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_probe_logs_checked ON router_probe_logs(checked_at)');
            $pdo->exec("DELETE FROM router_probe_logs WHERE checked_at < datetime('now', '-7 days')");
            $pdo->exec("DELETE FROM router_events WHERE created_at < datetime('now', '-7 days')");
        } catch (\Throwable $e) {
            error_log('[probe-history] prune failed: '.$e->getMessage());
        }
    }

    /**
     * Deteksi transisi status router dan catat event.
     * Seluruh penulisan event dibungkus try/catch agar aman.
     */
    private function detectTransitions(array $rows): void
    {
        try {
            $pdo = \App\Core\Database::getInstance()->getConnection();
            foreach ($rows as $row) {
                $routerId = (int) $row['id'];
                $prev = $this->getPreviousStatus($pdo, $routerId);

                // prev === null berarti belum ada log sebelumnya (probe pertama)
                if ($prev !== null && $prev !== $row['status']) {
                    if ($prev === 'online' && $row['status'] !== 'online') {
                        // guard 2 menit: cegah duplikat dari siklus probe paralel
                        if (! $this->hasRecentEvent($pdo, $routerId, 'went_offline', 2)) {
                            $this->insertEvent($pdo, $routerId, 'went_offline');
                        }
                    } elseif ($prev !== 'online' && $row['status'] === 'online') {
                        if (! $this->hasRecentEvent($pdo, $routerId, 'connected', 2)) {
                            $this->insertEvent($pdo, $routerId, 'connected');
                        }
                    }
                }

                if (($row['cpu_load'] ?? null) !== null && (float) $row['cpu_load'] > 80) {
                    if (! $this->hasRecentEvent($pdo, $routerId, 'high_cpu', 15)) {
                        $this->insertEvent($pdo, $routerId, 'high_cpu');
                    }
                }
            }
        } catch (\Throwable $e) {
            // deteksi event tidak boleh mengganggu health check
            error_log('[probe-log] '.$e->getMessage());
        }
    }

    private function getPreviousStatus(\PDO $pdo, int $routerId): ?string
    {
        // detectTransitions dijalankan SETELAH probe saat ini tersimpan,
        // jadi status "sebelumnya" adalah baris kedua terbaru.
        $stmt = $pdo->prepare(
            'SELECT status FROM router_probe_logs WHERE router_id = :rid ORDER BY checked_at DESC, id DESC LIMIT 1 OFFSET 1'
        );
        $stmt->execute([':rid' => $routerId]);
        $latest = $stmt->fetchColumn();

        return $latest === false ? null : (string) $latest;
    }

    private function insertEvent(\PDO $pdo, int $routerId, string $eventType): void
    {
        $stmt = $pdo->prepare('INSERT INTO router_events (router_id, event_type) VALUES (:rid, :type)');
        $stmt->execute([':rid' => $routerId, ':type' => $eventType]);
    }

    /**
     * Cek apakah event serupa sudah ada untuk router ini dalam N menit terakhir.
     */
    private function hasRecentEvent(\PDO $pdo, int $routerId, string $eventType, int $minutes): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM router_events
             WHERE router_id = :rid AND event_type = :type
               AND created_at >= datetime("now", :ago)'
        );
        $stmt->execute([':rid' => $routerId, ':type' => $eventType, ':ago' => "-{$minutes} minutes"]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Waktu probe online terakhir untuk router ini (UTC datetime string).
     */
    private function getLastSeen(int $routerId): ?string
    {
        try {
            $stmt = \App\Core\Database::getInstance()->getConnection()->prepare(
                "SELECT MAX(checked_at) FROM router_probe_logs WHERE router_id = :rid AND status = 'online'"
            );
            $stmt->execute([':rid' => $routerId]);
            $val = $stmt->fetchColumn();

            return $val === false || $val === null ? null : (string) $val;
        } catch (\Throwable $e) {
            error_log('[probe-log] last_seen failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Riwayat availability per jam utk N jam terakhir + agregat.
     * return: ['series'=>[['hour'=>'HH:00','availability_pct':float],...],
     *          'avg_uptime_pct'=>float,'downtime_seconds'=>int,'incidents'=>int]
     *
     * Catatan: hanya jam yang punya data yang dikembalikan (urut ascending);
     * frontend menggambar gap utk jam kosong.
     * downtime_seconds = approximasi: baris offline x 60s interval probe.
     */
    public function getHistory(int $hours = 24): array
    {
        try {
            $pdo = \App\Core\Database::getInstance()->getConnection();
            $hours = max(1, min(168, $hours));

            // Window mulai dari awal jam (now-N); checked_at disimpan UTC,
            // jadi pakai gmdate agar benar walau PHP TZ bukan UTC.
            $windowStart = gmdate('Y-m-d H:00:00', time() - $hours * 3600);

            $stmt = $pdo->prepare(
                "SELECT strftime('%Y-%m-%d %H', checked_at) AS bucket,
                        COUNT(*) AS total,
                        SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) AS online_cnt
                 FROM router_probe_logs
                 WHERE checked_at >= :start
                 GROUP BY bucket
                 ORDER BY bucket ASC"
            );
            $stmt->execute([':start' => $windowStart]);

            $series = [];
            $totalRows = 0;
            $onlineRows = 0;
            foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
                $total = (int) $r['total'];
                $onlineCnt = (int) $r['online_cnt'];
                $series[] = [
                    // bucket = 'YYYY-MM-DD HH' (UTC); label membawa tanggal agar
                    // jam lintas hari tidak ambigu, plus epoch utk chart frontend.
                    'hour' => substr((string) $r['bucket'], 0, 13).':00',
                    'ts' => \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $r['bucket'].':00:00', new \DateTimeZone('UTC'))->getTimestamp(),
                    'availability_pct' => $total > 0 ? round($onlineCnt / $total * 100, 1) : 100.0,
                ];
                $totalRows += $total;
                $onlineRows += $onlineCnt;
            }

            // Approximasi downtime: tiap baris offline ~ 60 detik interval probe.
            $dStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM router_probe_logs WHERE checked_at >= :start AND status != 'online'"
            );
            $dStmt->execute([':start' => $windowStart]);
            $downtimeSeconds = (int) $dStmt->fetchColumn() * 60;

            $iStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM router_events WHERE event_type = 'went_offline' AND created_at >= :start"
            );
            $iStmt->execute([':start' => $windowStart]);
            $incidents = (int) $iStmt->fetchColumn();

            return [
                'series' => $series,
                'avg_uptime_pct' => $totalRows > 0 ? round($onlineRows / $totalRows * 100, 1) : 100.0,
                'downtime_seconds' => $downtimeSeconds,
                'incidents' => $incidents,
            ];
        } catch (\Throwable $e) {
            error_log('[history] '.$e->getMessage());

            return ['series' => [], 'avg_uptime_pct' => 100.0, 'downtime_seconds' => 0, 'incidents' => 0];
        }
    }

    /**
     * Event router terbaru (join nama router).
     */
    public function getEvents(int $limit = 10): array
    {
        try {
            $pdo = \App\Core\Database::getInstance()->getConnection();
            $limit = max(1, min(50, $limit));

            $stmt = $pdo->prepare(
                'SELECT r.session_name AS router_name, r.hotspot_name,
                        e.event_type, e.created_at
                 FROM router_events e
                 JOIN routers r ON r.id = e.router_id
                 ORDER BY e.created_at DESC, e.id DESC
                 LIMIT :lim'
            );
            $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('[events] '.$e->getMessage());

            return [];
        }
    }

    private function cachePath(): string
    {
        return sys_get_temp_dir().'/mivo-router-health.json';
    }

    private function readCache(): ?array
    {
        $file = $this->cachePath();
        if (! file_exists($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (! is_array($data) || empty($data['checked_at'])) {
            return null;
        }

        if (time() - (int) $data['checked_at'] > self::CACHE_TTL) {
            return null;
        }

        return $data;
    }

    private function writeCache(array $payload): void
    {
        @file_put_contents($this->cachePath(), json_encode($payload));
    }
}
