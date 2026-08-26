<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Libraries\RouterOSAPI;
use App\Models\Config;

class ApiController extends Controller
{
    public function getInterfaces()
    {
        // Only allow POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);

            return;
        }

        // SECURITY: this endpoint must never be reachable unauthenticated.
        // It connects to attacker-controllable hosts and (in Edit Mode) uses
        // decrypted stored credentials — an open relay for credential theft.
        if (! isset($_SESSION['user_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);

            return;
        }

        // Get JSON Input
        $input = json_decode(file_get_contents('php://input'), true);

        $pass = $input['password'] ?? '';
        $id = $input['id'] ?? null;

        if (! empty($pass)) {
            // Add Mode: fresh credentials typed by the admin — use as given.
            $ip = trim($input['ip'] ?? '');
            $user = trim($input['user'] ?? '');
            $port = (int) ($input['port'] ?? 8728); // Default port
            $ssl = ! empty($input['ssl']); // api-ssl
        } elseif (! empty($id)) {
            // Edit Mode: password left blank means "use the stored one".
            // Connect ONLY to the address stored in the database for this
            // router id. Never send stored credentials to caller-supplied
            // ip/port — that was an unauthenticated credential exfiltration.
            $configModel = new Config;
            $session = $configModel->getSessionById($id);
            if (! $session || empty($session['password'])) {
                http_response_code(404);
                echo json_encode(['error' => 'Router not found or password not set']);

                return;
            }

            // Config::getSessionById already decrypts the password
            $pass = $session['password'];
            $ip = $session['ip_address'];
            $user = $session['username'];
            $port = (int) ($session['port'] ?: 8728);
            $ssl = ! empty($session['ssl']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Password is required for new routers']);

            return;
        }

        if (empty($ip) || empty($user)) {
            http_response_code(400);
            echo json_encode(['error' => 'IP Address and Username are required']);

            return;
        }

        $api = new RouterOSAPI;
        // $api->debug = true; // Enable for debugging
        $api->port = (int) $port;
        $api->ssl = $ssl;
        // Single attempt: this endpoint is for quick "Test Connection" in the Add Router form
        $api->attempts = 1;

        if ($api->connect($ip, $user, $pass)) {
            $api->write('/interface/print');
            $read = $api->read(false);
            $interfaces = $api->parseResponse($read);
            $api->disconnect();

            $list = [];
            foreach ($interfaces as $iface) {
                if (isset($iface['name'])) {
                    $list[] = $iface['name'];
                }
            }

            // Return success
            echo json_encode([
                'success' => true,
                'interfaces' => $list,
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => 'Connection failed. Check IP, User, Password, or connectivity.',
            ]);
        }
    }
}
