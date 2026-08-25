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
        // Cloudflare Turnstile (anti-bot): tolak lebih dulu bila verifikasi
        // gagal — kredensial tidak disentuh sama sekali.
        if (\App\Config\SiteConfig::turnstileEnabled()) {
            $ts = \App\Services\TurnstileService::verify($_POST['cf-turnstile-response'] ?? '');
            if (! $ts['success']) {
                FlashHelper::set('error', 'Verifikasi Gagal', 'Centang verifikasi Cloudflare terlebih dahulu.');
                header('Location: /login');
                exit;
            }
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $userModel = new User;
        $user = $userModel->attempt($username, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            FlashHelper::set('success', 'Welcome Back', 'Login successful.');
            header('Location: /');
            exit;
        } else {
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
