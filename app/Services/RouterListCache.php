<?php

namespace App\Services;

/**
 * Cache baca singkat untuk daftar yang diambil dari RouterOS.
 *
 * Latar: halaman-halaman daftar (vouchers, profiles, dll.) memanggil API
 * router secara sinkron pada setiap render — terasa berat bila jangkauan
 * ke router melewati WAN. Service ini menyimpan hasil pembacaan beberapa
 * detik agar navigasi terasa instan, dan wajib di-flush setiap kali ada
 * perubahan data agar tidak ada informasi basi setelah aksi tulis.
 */
class RouterListCache
{
    private const TTL = 45; // detik

    public static function key(string $name, string $session): string
    {
        return 'mivo-rc-'.md5($name.'|'.$session).'.json';
    }

    /** Ambil dari cache bila segar; selain itu jalankan $producer dan simpan. */
    public static function remember(string $name, string $session, int $ttl, callable $producer)
    {
        $file = sys_get_temp_dir().'/'.self::key($name, $session);
        $ttl = $ttl > 0 ? $ttl : self::TTL;

        if (is_file($file)) {
            $json = json_decode((string) file_get_contents($file), true);
            if (is_array($json) && isset($json['ts']) && (time() - $json['ts']) < $ttl) {
                return $json['payload'];
            }
        }

        $payload = $producer();
        @file_put_contents($file, json_encode(['ts' => time(), 'payload' => $payload]));

        return $payload;
    }

    /** Hapus seluruh cache daftar milik satu sesi (panggil SETELAH mutasi). */
    public static function flushSession(string $session): void
    {
        foreach (glob(sys_get_temp_dir().'/mivo-rc-*.json') ?: [] as $file) {
            @unlink($file);
        }
        unset($session); // saat ini flush global — aman & sederhana
    }
}
