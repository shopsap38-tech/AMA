<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\DashboardService;

/**
 * Tableau de bord : KPI et graphiques.
 */
final class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function index(): string
    {
        return $this->view('dashboard.index', [
            'kpis'    => $this->dashboard->kpis(),
            'charts'  => $this->dashboard->charts(),
            'recent'  => $this->dashboard->recentNonConformities(),
            'overdue' => $this->dashboard->overdueActions(),
            'active'  => 'dashboard',
            'title'   => 'Tableau de bord',
        ]);
    }

    /** Endpoint API REST : données des graphiques (rafraîchissement asynchrone). */
    public function data(Request $request): never
    {
        $this->json([
            'kpis'   => $this->dashboard->kpis(),
            'charts' => $this->dashboard->charts(),
        ]);
    }
}
