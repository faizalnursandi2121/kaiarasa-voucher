<?php

namespace App\Models;

use App\Core\Database;

class VoucherTemplateModel
{
    public function getAll(?int $sessionId = null)
    {
        $db = Database::getInstance();
        if ($sessionId !== null) {
            // Session scope: rows owned by this session + global (NULL) rows
            $stmt = $db->query(
                'SELECT * FROM voucher_templates WHERE session_id = ? OR session_id IS NULL ORDER BY (session_id IS NULL) ASC, id DESC',
                [$sessionId]
            );
        } else {
            $stmt = $db->query('SELECT * FROM voucher_templates');
        }

        return array_map(function ($row) {
            $row['is_global'] = (($row['session_id'] ?? null) === null);

            return $row;
        }, $stmt->fetchAll());
    }

    public function getAllByRouterId($routerId)
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM voucher_templates WHERE router_id = ?', [$routerId]);

        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT * FROM voucher_templates WHERE id = ?', [$id]);

        return $stmt->fetch();
    }

    public function add($data)
    {
        $db = Database::getInstance();
        $sql = 'INSERT INTO voucher_templates (router_id, session_name, name, content, session_id) VALUES (?, ?, ?, ?, ?)';

        return $db->query($sql, [
            $data['router_id'],
            $data['session_name'],
            $data['name'],
            $data['content'],
            $data['session_id'] ?? null,
        ]);
    }

    /**
     * Assign a template to a session (NULL = global).
     */
    public function setSession($id, ?int $sessionId)
    {
        $db = Database::getInstance();

        return $db->query('UPDATE voucher_templates SET session_id = ? WHERE id = ?', [$sessionId, $id]);
    }

    public function update($id, $data)
    {
        $db = Database::getInstance();
        $sql = 'UPDATE voucher_templates SET name=?, content=?, updated_at=CURRENT_TIMESTAMP WHERE id=?';

        return $db->query($sql, [
            $data['name'],
            $data['content'],
            $id,
        ]);
    }

    public function delete($id)
    {
        $db = Database::getInstance();

        return $db->query('DELETE FROM voucher_templates WHERE id = ?', [$id]);
    }
}
