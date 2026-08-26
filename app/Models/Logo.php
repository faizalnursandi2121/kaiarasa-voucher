<?php

namespace App\Models;

use App\Core\Database;
use Exception;
use PDO;

class Logo
{
    protected $db;

    protected $table = 'logos';

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->initTable();
    }

    // Connect method removed as we use shared instance
    private function initTable()
    {
        $query = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            path TEXT NOT NULL,
            type TEXT,
            size INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $this->db->query($query);
    }

    public function generateId($length = 6)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Semua logo. Dengan $sessionId: milik session tsb + global (fallback).
     * Flag is_global ditambahkan pada tiap baris.
     */
    public function getAll(?int $sessionId = null)
    {
        if ($sessionId !== null) {
            // Session scope: milik session ini + global (NULL) sebagai fallback
            $stmt = $this->db->query(
                "SELECT *, (session_id IS NULL) AS is_global FROM {$this->table} ".
                'WHERE session_id = :sid OR session_id IS NULL ORDER BY (session_id IS NULL) ASC, created_at DESC',
                ['sid' => $sessionId]
            );

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->db->query("SELECT *, 0 AS is_global FROM {$this->table} ORDER BY created_at DESC");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE id = :id", ['id' => $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add($file, ?int $sessionId = null)
    {
        // Security: Strict MIME Type Check
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            'image/webp' => 'webp',
        ];

        if (! array_key_exists($mimeType, $allowedMimes)) {
            throw new Exception('Invalid file type: '.$mimeType);
        }

        // Use extension mapped from MIME type or sanitize original
        // Better to trust MIME mapping for extensions to avoid double extension attacks
        $extension = $allowedMimes[$mimeType];

        // Generate Unique Short ID
        do {
            $id = $this->generateId();
            $exists = $this->getById($id);
        } while ($exists);

        $uploadDir = ROOT.'/public/uploads/logos/';
        if (! file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // SECURITY: SVG can carry active content (scripts, event handlers,
        // external references). Strip the dangerous constructs before the
        // file ever lands in the web root. Serving is additionally hardened
        // via nginx (no PHP handler, nosniff, CSP sandbox).
        if ($extension === 'svg') {
            $sanitized = self::sanitizeSvg(file_get_contents($file['tmp_name']));
            if ($sanitized === null) {
                throw new Exception('Invalid SVG content');
            }
            file_put_contents($file['tmp_name'], $sanitized);
        }

        $filename = $id.'.'.$extension;
        $targetPath = $uploadDir.$filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->db->query("INSERT INTO {$this->table} (id, name, path, type, size, session_id) VALUES (:id, :name, :path, :type, :size, :sid)", [
                'id' => $id,
                'name' => $file['name'],
                'path' => '/uploads/logos/'.$filename,
                'type' => $extension,
                'size' => $file['size'],
                'sid' => $sessionId,
            ]);

            return $id;
        }

        return false;
    }

    /**
     * Remove active content from an SVG document. Returns null when the
     * payload is not parseable XML or contains obviously hostile constructs
     * that cannot be safely stripped.
     */
    public static function sanitizeSvg(?string $svg): ?string
    {
        if ($svg === null || trim($svg) === '') {
            return null;
        }

        // Reject documents with constructs we do not whitelist away.
        if (preg_match('/<script|<foreignObject|<!ENTITY|javascript\s*:/i', $svg)) {
            // Strip <script> blocks first so benign files still pass.
            $svg = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $svg);
            $svg = preg_replace('#<script\b[^>]*/?>#i', '', $svg);
            $svg = preg_replace('#<foreignObject\b[^>]*>.*?</foreignObject>#is', '', $svg);
            $svg = preg_replace('/<!ENTITY[^>]*>/s', '', $svg);
            if (preg_match('/<script|<foreignObject|<!ENTITY|javascript\s*:/i', $svg)) {
                return null; // still present after stripping -> hostile
            }
        }

        // Drop inline event handlers (onclick=, onload=, ...)
        $svg = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg);

        // Neutralize javascript:/data:text/html URLs in href/xlink:href/src
        $svg = preg_replace(
            '/(href|xlink:href|src)\s*=\s*("|\')\s*(javascript:|data:text\/html)[^"\']*("|\')/i',
            '$1="#"',
            $svg
        );

        return $svg;
    }

    public function syncFiles()
    {
        // One-time sync: scan folder, if file not in DB, add it.
        $logoDir = ROOT.'/public/uploads/logos/';
        if (! file_exists($logoDir)) {
            return;
        }

        $files = [];
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
        foreach ($extensions as $ext) {
            $files = array_merge($files, glob($logoDir.'*.'.$ext));
        }

        foreach ($files as $file) {
            $filename = basename($file);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            // Check if file is registered (maybe by path match)
            $webPath = '/uploads/logos/'.$filename;
            $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE path = :path", ['path' => $webPath]);

            if ($stmt->fetchColumn() == 0) {
                // Not in DB, register it.
                // Ideally we'd rename it to a hashID, but since it's existing, let's generate an ID and map it.
                do {
                    $id = $this->generateId();
                    $exists = $this->getById($id);
                } while ($exists);

                $this->db->query("INSERT INTO {$this->table} (id, name, path, type, size) VALUES (:id, :name, :path, :type, :size)", [
                    'id' => $id,
                    'name' => $filename,
                    'path' => $webPath,
                    'type' => $extension,
                    'size' => filesize($file),
                ]);
            }
        }
    }

    public function delete($id)
    {
        $logo = $this->getById($id);
        if ($logo) {
            $filePath = ROOT.'/public'.$logo['path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $this->db->query("DELETE FROM {$this->table} WHERE id = :id", ['id' => $id]);

            return true;
        }

        return false;
    }
}
