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
            'status' => 'offline',
            'cpu_load' => null,
            'uptime' => null,
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
            $row['uptime'] = $resource[0]['uptime'] ?? null;
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
                        $this->insertEvent($pdo, $routerId, 'went_offline');
                    } elseif ($prev !== 'online' && $row['status'] === 'online') {
                        $this->insertEvent($pdo, $routerId, 'connected');
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
