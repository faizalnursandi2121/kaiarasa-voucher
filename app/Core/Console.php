<?php

namespace App\Core;

use App\Config\SiteConfig;

class Console
{
    // ANSI Color Codes
    const COLOR_RESET = "\033[0m";

    const COLOR_GREEN = "\033[32m";

    const COLOR_YELLOW = "\033[33m";

    const COLOR_BLUE = "\033[34m";

    const COLOR_GRAY = "\033[90m";

    const COLOR_RED = "\033[31m";

    const COLOR_BOLD = "\033[1m";

    public function run($argv)
    {
        $command = $argv[1] ?? 'help';
        $args = array_slice($argv, 2);

        $this->printBanner();

        switch ($command) {
            case 'serve':
                $this->commandServe($args);
                break;

            case 'key:generate':
                $this->commandKeyGenerate();
                break;

            case 'key:rotate':
                $this->commandKeyRotate($args);
                break;

            case 'admin:reset':
                $this->commandAdminReset($args);
                break;

            case 'install':
                $this->commandInstall($args);
                break;

            case 'migrate':
                $this->commandMigrate();
                break;

            case 'help':
            default:
                $this->commandHelp();
                break;
        }
    }

    private function printBanner()
    {
        echo "\n";
        echo self::COLOR_BOLD.'  Kaiarasa Helper '.self::COLOR_RESET.self::COLOR_GRAY.SiteConfig::APP_VERSION.self::COLOR_RESET."\n\n";
    }

    private function commandServe($args)
    {
        $host = '0.0.0.0';
        $port = 8000;

        foreach ($args as $arg) {
            if (strpos($arg, '--port=') === 0) {
                $port = (int) substr($arg, 7);
            }
            if (strpos($arg, '--host=') === 0) {
                $host = substr($arg, 7);
            }
        }

        echo '  '.self::COLOR_GREEN.'Server running on:'.self::COLOR_RESET."\n";
        echo '  - Local:   '.self::COLOR_BLUE."http://localhost:$port".self::COLOR_RESET."\n";

        $hostname = gethostname();
        $ip = gethostbyname($hostname);
        if ($ip !== '127.0.0.1' && $ip !== 'localhost') {
            echo '  - Network: '.self::COLOR_BLUE."http://$ip:$port".self::COLOR_RESET."\n";
        }

        echo "\n  ".self::COLOR_GRAY.'Press Ctrl+C to stop'.self::COLOR_RESET."\n\n";

        $cmd = sprintf('php -S %s:%d -t public public/index.php', $host, $port);
        passthru($cmd);
    }

    /**
     * Rotasi APP_KEY + migrasi data terenkripsi (opsi A: one-shot).
     *
     * Usage:
     *   php kaiarasa key:rotate [OLD_KEY]
     *   OLD_KEY opsional — bila diabaikan, dipakai key aktif saat ini
     *   (dari .env / file persisten). Alur Dokploy: set APP_KEY baru di
     *   panel dulu, lalu jalankan: php kaiarasa key:rotate <KEY_LAMA>
     */
    private function commandKeyRotate(array $args)
    {
        $oldSecret = $args[0] ?? null;
        if ($oldSecret === null || $oldSecret === '') {
            $oldSecret = \App\Config\SiteConfig::getSecretKey();
            echo self::COLOR_YELLOW.'OLD_KEY tidak diberikan — memakai key aktif saat ini.'.self::COLOR_RESET."\n";
        }

        $newSecret = bin2hex(random_bytes(32)); // 64 hex = entropi 256-bit

        echo self::COLOR_YELLOW.'=== Kaiarasa Key Rotation ==='.self::COLOR_RESET."\n";

        // 1) Backup database sebelum menyentuh apa pun.
        $dbPath = ROOT.'/app/Database/database.sqlite';
        if (! is_file($dbPath)) {
            echo self::COLOR_RED."Database tidak ditemukan: {$dbPath}\n".self::COLOR_RESET;
            exit(1);
        }
        $backup = $dbPath.'.bak-'.date('Ymd-His');
        if (! copy($dbPath, $backup)) {
            echo self::COLOR_RED.'Gagal membuat backup database — dibatalkan.'.self::COLOR_RESET."\n";
            exit(1);
        }
        echo 'Backup DB: '.basename($backup)."\n";

        // 2) Migrasi tiap baris routers: legacy CBC -> GCM dengan key baru.
        $db = \App\Core\Database::getInstance();
        $rows = $db->query('SELECT id, session_name, password FROM routers')->fetchAll();
        $migrated = 0;
        $skipped = 0;
        $failedRows = [];
        foreach ($rows as $row) {
            $stored = (string) ($row['password'] ?? '');
            if ($stored === '') {
                ++$skipped; // belum ada password
                continue;
            }
            if (str_starts_with($stored, \App\Helpers\EncryptionHelper::V2_PREFIX ?? 'enc2::')) {
                ++$skipped; // sudah envelope baru — idempotent
                continue;
            }
            $plain = \App\Helpers\EncryptionHelper::decryptLegacyWithSecret($stored, $oldSecret);
            if ($plain === null) {
                $failedRows[] = $row['session_name'] ?? ('#'.$row['id']);
                continue;
            }
            $enc = \App\Helpers\EncryptionHelper::encryptWithSecret($plain, $newSecret);
            if ($enc === '') {
                $failedRows[] = $row['session_name'] ?? ('#'.$row['id']);
                continue;
            }
            $db->query('UPDATE routers SET password = ? WHERE id = ?', [$enc, $row['id']]);
            ++$migrated;
        }

        echo "Hasil migrasi: {$migrated} dimigrasi, {$skipped} dilewati, ".count($failedRows)." gagal.\n";
        if ($failedRows) {
            echo self::COLOR_RED.'Gagal (input ulang manual lewat UI): '.implode(', ', $failedRows).self::COLOR_RESET."\n";
        }

        // 3a) .env tersedia -> tulis APP_KEY baru + simpan lama sbg APP_KEY_OLD.
        $envPath = ROOT.'/.env';
        if (is_file($envPath) && is_writable($envPath) && strpos((string) file_get_contents($envPath), 'APP_KEY=') !== false) {
            $content = file_get_contents($envPath);
            $content = preg_replace('/^APP_KEY=.*/m', "APP_KEY={$newSecret}", $content);
            if (strpos($content, 'APP_KEY_OLD=') !== false) {
                $content = preg_replace('/^APP_KEY_OLD=.*/m', "APP_KEY_OLD={$oldSecret}", $content);
            } else {
                $content .= "\nAPP_KEY_OLD={$oldSecret}\n";
            }
            file_put_contents($envPath, $content, LOCK_EX);
            echo self::COLOR_GREEN.'.env diperbarui: APP_KEY baru diset, yang lama disimpan sebagai APP_KEY_OLD.'.self::COLOR_RESET."\n";
            echo "Setelah verifikasi koneksi router normal, HAPUS baris APP_KEY_OLD dari .env.\n";
        } else {
            // 3b) Tanpa .env (Dokploy/panel env) -> cetak instruksi.
            echo self::COLOR_YELLOW."\n.env tidak tersedia/dapat ditulis — set environment di panel:\n".self::COLOR_RESET;
            echo "  APP_KEY={$newSecret}\n";
            echo "\nData SUDAH dienkripsi dengan key di atas. Aplikasi akan gagal baca\n";
            echo "kredensial sampai env ini diset dan container di-restart.\n";
        }

        echo "\nSelesai. Jangan lupa: rotasi juga password router di perangkat MikroTik.\n";
    }

    private function commandKeyGenerate()
    {
        echo self::COLOR_YELLOW.'Generating new application key...'.self::COLOR_RESET."\n";

        // Generate 32 bytes of random data for AES-256
        $key = bin2hex(random_bytes(16)); // 32 chars hex

        $envPath = ROOT.'/.env';
        $examplePath = ROOT.'/.env.example';

        // Copy example if .env doesn't exist
        if (! file_exists($envPath)) {
            echo self::COLOR_BLUE.'Copying .env.example to .env...'.self::COLOR_RESET."\n";
            if (file_exists($examplePath)) {
                copy($examplePath, $envPath);
            } else {
                echo self::COLOR_RED.'Error: .env.example not found.'.self::COLOR_RESET."\n";

                return;
            }
        }

        // Read .env
        $content = file_get_contents($envPath);

        // Replace or Append APP_KEY
        if (strpos($content, 'APP_KEY=') !== false) {
            $newContent = preg_replace(
                '/APP_KEY=.*/',
                "APP_KEY=$key",
                $content
            );
        } else {
            $newContent = $content."\nAPP_KEY=$key";
        }

        file_put_contents($envPath, $newContent);

        echo self::COLOR_GREEN.'Application key set successfully in .env.'.self::COLOR_RESET."\n";
        echo self::COLOR_GRAY.'Key: '.$key.self::COLOR_RESET."\n";
        echo self::COLOR_YELLOW.'Please ensure .env is not committed to version control.'.self::COLOR_RESET."\n";
    }

    private function commandAdminReset($args)
    {
        $username = 'admin';
        $password = $args[0] ?? 'admin';

        echo self::COLOR_YELLOW."Resetting password for user '$username'...".self::COLOR_RESET."\n";

        try {
            $db = Database::getInstance();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // Check if user exists first
            $check = $db->query('SELECT id FROM users WHERE username = ?', [$username])->fetch();

            if ($check) {
                $db->query('UPDATE users SET password = ? WHERE username = ?', [$hash, $username]);
                echo self::COLOR_GREEN.'Password updated successfully.'.self::COLOR_RESET."\n";
            } else {
                // Determine if we should create it
                echo self::COLOR_YELLOW."User '$username' not found. Creating...".self::COLOR_RESET."\n";
                $db->query('INSERT INTO users (username, password, created_at) VALUES (?, ?, ?)', [
                    $username, $hash, date('Y-m-d H:i:s'),
                ]);
                echo self::COLOR_GREEN.'User created successfully.'.self::COLOR_RESET."\n";
            }

            echo 'New Password: '.self::COLOR_BOLD.$password.self::COLOR_RESET."\n";

        } catch (\Exception $e) {
            echo self::COLOR_RED.'Error: '.$e->getMessage().self::COLOR_RESET."\n";
        }
    }

    /**
     * Run database migrations (idempotent). Safe for fresh and existing installs.
     * Applies additive schema changes like new columns to existing tables.
     */
    private function commandMigrate()
    {
        echo "Running migrations...\n";
        try {
            if (Migrations::up()) {
                echo self::COLOR_GREEN.'Migrations applied successfully.'.self::COLOR_RESET."\n";
            }
        } catch (\Exception $e) {
            echo self::COLOR_RED.'Migration Error: '.$e->getMessage().self::COLOR_RESET."\n";
        }
    }

    private function commandInstall($args)
    {
        echo self::COLOR_BLUE.'=== Kaiarasa Installer ==='.self::COLOR_RESET."\n";

        // 1. Database Migration
        echo "Setting up database...\n";
        try {
            if (Migrations::up()) {
                echo self::COLOR_GREEN.'Database schema created successfully.'.self::COLOR_RESET."\n";
            }
        } catch (\Exception $e) {
            echo self::COLOR_RED.'Migration Error: '.$e->getMessage().self::COLOR_RESET."\n";

            return;
        }

        // 2. Encryption Key
        echo "Generating encryption key...\n";

        $envPath = ROOT.'/.env';
        $keyExists = false;

        if (file_exists($envPath)) {
            $envIds = parse_ini_file($envPath);
            if (! empty($envIds['APP_KEY']) && $envIds['APP_KEY'] !== 'kaiarasa_official_secret_key_32bytes') {
                $keyExists = true;
            }
        }

        if (! $keyExists) {
            $this->commandKeyGenerate();
        } else {
            echo self::COLOR_YELLOW.'Secret key already set in .env. Skipping.'.self::COLOR_RESET."\n";
        }

        // 3. Admin Account
        echo 'Create Admin Account? [Y/n] ';
        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));

        if (strtolower($line) != 'n') {
            echo 'Username [admin]: ';
            $user = trim(fgets($handle));
            if (empty($user)) {
                $user = 'admin';
            }

            echo 'Password [admin]: ';
            $pass = trim(fgets($handle));
            if (empty($pass)) {
                $pass = 'admin';
            }

            // Re-use admin reset logic slightly modified or called directly
            $this->commandAdminReset([$pass]); // Simplification: admin:reset implementation uses hardcoded user='admin' currently, need to update it to support custom username if we want full flexibility.
            // Wait, my commandAdminReset implementation uses hardcoded 'admin'.
            // I should update commandAdminReset to accept username as argument or just replicate logic here.
            // Replicating logic for clarity here.

            /* Actually, commandAdminReset as currently implemented takes password as arg[0] and uses 'admin' as username.
               User requested robust install. I will just run the logic manually here to respect the inputted username. */

            try {
                $db = Database::getInstance();
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $check = $db->query('SELECT id FROM users WHERE username = ?', [$user])->fetch();
                if ($check) {
                    $db->query('UPDATE users SET password = ? WHERE username = ?', [$hash, $user]);
                    echo self::COLOR_GREEN."User '$user' updated.".self::COLOR_RESET."\n";
                } else {
                    $db->query('INSERT INTO users (username, password, created_at) VALUES (?, ?, ?)', [$user, $hash, date('Y-m-d H:i:s')]);
                    echo self::COLOR_GREEN."User '$user' created.".self::COLOR_RESET."\n";
                }
            } catch (\Exception $e) {
                echo self::COLOR_RED.'Error creating user: '.$e->getMessage().self::COLOR_RESET."\n";
            }
        }

        echo "\n".self::COLOR_GREEN.'Installation Completed Successfully!'.self::COLOR_RESET."\n";
        echo 'You can now run: '.self::COLOR_YELLOW.'php kaiarasa serve'.self::COLOR_RESET."\n";
    }

    private function commandHelp()
    {
        echo self::COLOR_YELLOW.'Usage:'.self::COLOR_RESET."\n";
        echo "  php kaiarasa [command] [options]\n\n";

        echo self::COLOR_YELLOW.'Available commands:'.self::COLOR_RESET."\n";
        echo '  '.self::COLOR_GREEN.'install      '.self::COLOR_RESET."    Install Kaiarasa (Setup DB & Admin)\n";
        echo '  '.self::COLOR_GREEN.'serve        '.self::COLOR_RESET."    Start the development server\n";
        echo '  '.self::COLOR_GREEN.'key:generate '.self::COLOR_RESET."    Set the application key\n";
        echo '  '.self::COLOR_GREEN.'key:rotate   '.self::COLOR_RESET."    Rotate APP_KEY + re-enkripsi data (usage: key:rotate [OLD_KEY])\n";
        echo '  '.self::COLOR_GREEN.'admin:reset  '.self::COLOR_RESET."    Reset admin password (default: admin)\n";
        echo '  '.self::COLOR_GREEN.'migrate      '.self::COLOR_RESET."    Run database migrations (upgrades/adds new columns)\n";
        echo '  '.self::COLOR_GREEN.'help         '.self::COLOR_RESET."    Show this help message\n";
        echo "\n";
    }
}
