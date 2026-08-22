<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\RouterHealthService;

class RouterHealthController extends Controller
{
    public function index()
    {
        header('Content-Type: application/json');

        // ?refresh=1 memaksa probe ulang, abaikan cache
        $force = isset($_GET['refresh']);

        echo json_encode((new RouterHealthService)->getHealth($force));
    }

    public function history()
    {
        header('Content-Type: application/json');
        $hours = isset($_GET['hours']) ? max(1, min(168, (int) $_GET['hours'])) : 24;

        echo json_encode((new RouterHealthService)->getHistory($hours));
    }

    public function events()
    {
        header('Content-Type: application/json');
        $limit = isset($_GET['limit']) ? max(1, min(50, (int) $_GET['limit'])) : 10;

        echo json_encode((new RouterHealthService)->getEvents($limit));
    }
}
