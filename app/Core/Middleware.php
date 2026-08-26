<?php

namespace App\Core;

class Middleware
{
    public static function auth()
    {
        // Assume session is started in index.php
        if (! isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    public static function cors()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (empty($origin)) {
            return;
        }

        // Cache proxy tidak boleh menyajikan respons CORS satu origin
        // kepada origin lain.
        header('Vary: Origin');

        $db = Database::getInstance();
        // SECURITY (CWE-942): hanya origin eksplisit yang diizinkan. Aturan
        // wildcard '*' tidak lagi dipakai untuk refleksi — dengan cookie
        // sesi, refleksi origin apa pun membuka data lintas situs.
        $stmt = $db->query('SELECT * FROM api_cors WHERE origin = ? LIMIT 1', [$origin]);
        $rule = $stmt->fetch();

        if ($rule) {
            header('Access-Control-Allow-Origin: '.$rule['origin']);

            $methods = json_decode($rule['methods'], true) ?: ['GET', 'POST'];
            header('Access-Control-Allow-Methods: '.implode(', ', $methods));

            $headers = json_decode($rule['headers'], true) ?: ['*'];
            header('Access-Control-Allow-Headers: '.implode(', ', $headers));

            header('Access-Control-Max-Age: '.($rule['max_age'] ?? 3600));

            // Handle preflight requests
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                http_response_code(200);
                exit();
            }
        }
    }
}
