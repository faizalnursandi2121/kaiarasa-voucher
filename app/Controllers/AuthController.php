<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Helpers\FlashHelper;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }

        return $this->view('login');
    }

    public function login()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $username = trim($_POST['username'] ?? '');

        // SECURITY (CWE-307): rate limit/lockout sebelum apa pun.
        if (\App\Services\LoginRateLimiter::tooManyAttempts($ip, $username)) {
            FlashHelper::set('error', 'Terlalu Banyak Percobaan', 'Coba lagi dalam 15 menit.');
            header('Location: /login');
            exit;
        }

        // Cloudflare Turnstile — mesin keadaan fail-closed:
        //   misconfigured -> blokir total; enabled -> verifikasi ketat;
        //   disabled      -> lewati (sudah di-warning-log oleh SiteConfig).
        $state = \App\Config\SiteConfig::turnstileState();
        if ($state === 'misconfigured') {
            FlashHelper::set('error', 'Konfigurasi Error', 'Turnstile setengah-konfigurasi. Lengkapi atau kosongkan kedua kunci.');
            header('Location: /login');
            exit;
        }
        if ($state === 'enabled') {
            $token = $_POST['cf-turnstile-response'] ?? '';

            // SECURITY (CWE-837): token hanya boleh dipakai sekali.
            $hash = hash('sha256', (string) $token);
            $_SESSION['_ts_used'] = $_SESSION['_ts_used'] ?? [];
            foreach ($_SESSION['_ts_used'] as $h => $t) { // buang > 10 menit
                if ((time() - $t) > 600) {
                    unset($_SESSION['_ts_used'][$h]);
                }
            }
            if ($hash !== '' && isset($_SESSION['_ts_used'][$hash])) {
                FlashHelper::set('error', 'Verifikasi Gagal', 'Token verifikasi sudah terpakai. Muat ulang halaman.');
                header('Location: /login');
                exit;
            }

            $ts = \App\Services\TurnstileService::verify($token);
            if (! $ts['success']) {
                // Gagal verifikasi (termasuk error jaringan) = tidak bisa login.
                $_SESSION['_ts_used'][$hash] = time();
                FlashHelper::set('error', 'Verifikasi Gagal', 'Centang verifikasi Cloudflare terlebih dahulu.');
                header('Location: /login');
                exit;
            }
            $_SESSION['_ts_used'][$hash] = time(); // konsumsi token
        }

        $password = $_POST['password'] ?? '';

        $userModel = new User;
        $user = $userModel->attempt($username, $password);

        if ($user) {
            // SECURITY (CWE-384): session fixation — ID wajib baru saat naik
            // privilege anonim -> terautentikasi.
            session_regenerate_id(true);
            \App\Helpers\CsrfHelper::rotate();

            \App\Services\LoginRateLimiter::clear($ip, $username);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            FlashHelper::set('success', 'Welcome Back', 'Login successful.');
            header('Location: /');
            exit;
        } else {
            \App\Services\LoginRateLimiter::recordFailure($ip, $username);
            FlashHelper::set('error', 'Login Failed', 'Invalid credentials');
            header('Location: /login');
            exit;
        }
    }

    public function logout()
    {
        session_destroy();
        header('Location: /login');
        exit;
    }
}
