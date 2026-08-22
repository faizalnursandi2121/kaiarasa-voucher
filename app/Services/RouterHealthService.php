<?php

namespace App\Services;

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
            $results[] = $this->probeRouter($session);
        }

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

        try {
            $API = RouterOSAPI::fromSession($session);
            $API->attempts = 1;
            $API->timeout = 3;
            $API->delay = 0;

            if (! $API->connect($session['ip_address'], $session['username'], $session['password'])) {
                $row['error'] = 'Connection failed';

                return $row;
            }

            $resource = $API->comm('/system/resource/print');
            $activeUsers = $API->comm('/ip/hotspot/active/print');
            $API->disconnect();

            if (! is_array($resource) || isset($resource['!trap']) || empty($resource[0])) {
                $row['status'] = 'error';
                $row['error'] = 'Resource query failed';

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

        return $row;
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
