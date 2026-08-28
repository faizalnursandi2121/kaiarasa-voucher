<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SalesReportService;

class ReportController extends Controller
{
    /**
     * Halaman Sales Report (menggantikan Financial Report).
     * Filter GET: start, end (Y-m-d), package, sale_type, refresh.
     */
    public function sales($session)
    {
        $svc = new SalesReportService($session);
        $filters = [
            'start' => $_GET['start'] ?? null,
            'end' => $_GET['end'] ?? null,
            'package' => ($_GET['package'] ?? '') !== '' ? $_GET['package'] : null,
            'sale_type' => ($_GET['sale_type'] ?? '') !== '' ? $_GET['sale_type'] : null,
            'server' => ($_GET['server'] ?? '') !== '' ? $_GET['server'] : null,
        ];

        $report = $svc->getReport($filters, isset($_GET['refresh']));
        $packages = [];
        $servers = [];
        if (! isset($report['__unreachable'])) {
            $packages = array_map(fn ($p) => $p['name'], $report['by_package']);
            $servers = array_map(fn ($s) => $s['name'], $report['by_server']);
        }

        return $this->view('reports/sales', [
            'session' => $session,
            'filters' => $filters,
            'packages' => $packages,
            'servers' => $servers,
            'report' => $report,
        ]);
    }

    /** Route legacy /reports/financial → redirect ke Sales Report. */
    public function index($session)
    {
        header('Location: /'.rawurlencode($session).'/reports/sales');
        exit;
    }

    /** Export: csv | json. */
    public function sellingExport($session, $type)
    {
        $svc = new SalesReportService($session);
        $filters = [
            'start' => $_GET['start'] ?? null,
            'end' => $_GET['end'] ?? null,
            'package' => ($_GET['package'] ?? '') !== '' ? $_GET['package'] : null,
            'sale_type' => ($_GET['sale_type'] ?? '') !== '' ? $_GET['sale_type'] : null,
            'server' => ($_GET['server'] ?? '') !== '' ? $_GET['server'] : null,
        ];
        $report = $svc->getReport($filters);

        header('Content-Type: application/json');
        if (isset($report['__unreachable'])) {
            echo json_encode(['error' => 'Router unreachable']);
            exit;
        }

        if ($type === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sales-report.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Generated', 'Code', 'Package', 'Server', 'Sale Type', 'Batch ID', 'Price']);
            foreach ($report['list'] as $row) {
                fputcsv($out, [
                    $row['date'].($row['time'] ? ' '.$row['time'] : ''),
                    $row['code'], $row['package'], $row['server'],
                    $row['sale_type'], $row['batch_id'], $row['price'],
                ]);
            }
            fclose($out);
            exit;
        }

        echo json_encode($report['list']);
        exit;
    }
}
