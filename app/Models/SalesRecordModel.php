<?php

namespace App\Models;

use App\Core\Database;

/**
 * SalesRecordModel — persistent denormalized snapshot voucher yang dijual.
 *
 * Tabel sales_records adalah SUMBER PRIMER untuk Sales Report.
 * - INSERT saat voucher dibuat (Generate / Quick Print / Add Voucher).
 * - Soft-delete (set deleted_at) saat voucher dihapus dari MikroTik,
 *   supaya row tetap ada di laporan walau voucher sudah hilang.
 * - Profile yang dihapus dari Data Plans TIDAK menghapus rows di sini
 *   karena profile_name & profile_price sudah di-snapshot saat INSERT.
 */
class SalesRecordModel
{
    /**
     * Insert satu sales record.
     *
     * @param array{
     *   router_id:int, voucher_name:string, voucher_password?:string,
     *   profile_name?:?string, profile_price?:int, server?:?string,
     *   comment?:?string, sale_type:string, price?:int, billable?:bool,
     *   datetime?:?string
     * } $data
     * @return int inserted row id
     */
    public function insert(array $data): int
    {
        $db = Database::getInstance();
        $sql = 'INSERT OR IGNORE INTO sales_records
                (router_id, voucher_name, voucher_password, profile_name, profile_price,
                 server, comment, sale_type, price, billable, datetime)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $db->query($sql, [
            (int) $data['router_id'],
            (string) $data['voucher_name'],
            (string) ($data['voucher_password'] ?? ''),
            (string) ($data['profile_name'] ?? ''),
            (int) ($data['profile_price'] ?? 0),
            (string) ($data['server'] ?? ''),
            (string) ($data['comment'] ?? ''),
            (string) $data['sale_type'],
            (int) ($data['price'] ?? 0),
            ! empty($data['billable']) ? 1 : 0,
            (string) ($data['datetime'] ?? ''),
        ]);
        // INSERT OR IGNORE: kalau duplicate (UNIQUE constraint), tidak ada
        // row baru, lastInsertId() returns 0. Ambil id existing untuk return.
        $newId = (int) $db->getConnection()->lastInsertId();
        if ($newId === 0) {
            $stmt = $db->query(
                'SELECT id FROM sales_records WHERE router_id = ? AND voucher_name = ? AND sale_type = ?',
                [(int) $data['router_id'], (string) $data['voucher_name'], (string) $data['sale_type']]
            );
            $existing = $stmt->fetch();
            return $existing ? (int) $existing['id'] : 0;
        }
        return $newId;
    }

    /**
     * Soft-delete voucher by name (dipanggil saat voucher dihapus dari MikroTik).
     * Hanya mark deleted_at = NOW(), row tetap ada untuk Sales Report.
     *
     * @return int jumlah row yang di-update
     */
    public function softDeleteByVoucherName(int $routerId, string $voucherName): int
    {
        $db = Database::getInstance();
        $sql = 'UPDATE sales_records
                SET deleted_at = datetime("now", "localtime")
                WHERE router_id = ? AND voucher_name = ? AND deleted_at IS NULL';
        $stmt = $db->query($sql, [$routerId, $voucherName]);
        return is_object($stmt) && method_exists($stmt, 'rowCount') ? $stmt->rowCount() : 0;
    }

    /**
     * Ambil semua sales records untuk satu router (untuk rebuild / view).
     * Default: exclude soft-deleted (deleted_at IS NOT NULL).
     *
     * @return array list of rows (assoc)
     */
    public function getByRouter(int $routerId, bool $includeDeleted = false): array
    {
        $db = Database::getInstance();
        $sql = 'SELECT * FROM sales_records WHERE router_id = ?';
        if (! $includeDeleted) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' ORDER BY sold_at DESC, id DESC';
        $stmt = $db->query($sql, [$routerId]);
        return $stmt->fetchAll();
    }

    /**
     * Hitung jumlah record per router — dipakai untuk deteksi
     * "apakah backfill sudah pernah dilakukan?" (heuristic: kalau
     * router_id ini punya 0 rows, lakukan backfill).
     */
    public function countByRouter(int $routerId): int
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT COUNT(*) AS c FROM sales_records WHERE router_id = ?', [$routerId]);
        $row = $stmt->fetch();
        return (int) ($row['c'] ?? 0);
    }

    /**
     * Cari router_id dari session_name (FK ke tabel routers).
     */
    public function routerIdBySession(string $session): ?int
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT id FROM routers WHERE session_name = ?', [$session]);
        $row = $stmt->fetch();
        return $row ? (int) $row['id'] : null;
    }
}
