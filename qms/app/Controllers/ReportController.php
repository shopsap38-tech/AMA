<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\DepartmentRepository;
use App\Services\ReportService;

/**
 * Génération et export des rapports (CSV, Excel, impression PDF).
 */
final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly DepartmentRepository $departments,
    ) {
    }

    public function index(Request $request): string
    {
        return $this->view('reports.index', [
            'departments' => $this->departments->options(),
            'active'      => 'reports',
            'title'       => 'Rapports',
        ]);
    }

    public function csv(Request $request): never
    {
        $content = $this->reports->toCsv($request->all());
        Response::stream($content, 'non-conformites_' . date('Ymd') . '.csv', 'text/csv');
    }

    public function excel(Request $request): never
    {
        $content = $this->reports->toExcel($request->all());
        Response::stream($content, 'non-conformites_' . date('Ymd') . '.xls', 'application/vnd.ms-excel');
    }

    public function print(Request $request): string
    {
        return $this->view('reports.print', [
            'rows'    => $this->reports->printable($request->all()),
            'filters' => $request->all(),
            'title'   => 'Rapport des non-conformités',
        ]);
    }
}
