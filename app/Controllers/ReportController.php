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
        ];

        $report = $svc->getReport($filters, isset($_GET['refresh']));
        $packages = [];
        if (! isset($report['__unreachable'])) {
            $packages = array_map(fn ($p) => $p['name'], $report['by_package']);
        }

        return $this->view('reports/sales', [
            'session' => $session,
            'filters' => $filters,
            'packages' => $packages,
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
            fputcsv($out, ['Date', 'Package', 'Quantity', 'Unit Price', 'Total', 'Sale Type', 'Used']);
            foreach ($report['list'] as $row) {
                fputcsv($out, [
                    $row['date'], $row['package'], $row['quantity'], $row['unit_price'],
                    $row['total'], $row['sale_type'], $row['used_count'],
                ]);
            }
            fclose($out);
            exit;
        }

        echo json_encode($report['list']);
        exit;
    }
}
