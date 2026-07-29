<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Repositories\AuditRepository;
use App\Repositories\UserRepository;

/**
 * Consultation du journal d'audit (traçabilité complète).
 */
final class AuditController extends Controller
{
    public function __construct(
        private readonly AuditRepository $repository,
        private readonly UserRepository $users,
    ) {
    }

    public function index(Request $request): string
    {
        $filters = $request->only(['entity_type', 'action', 'user_id', 'date_from', 'date_to']);
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->repository->search($filters, $page, 30);

        return $this->view('audit.index', [
            'logs'    => $result['data'],
            'total'   => $result['total'],
            'page'    => $page,
            'perPage' => 30,
            'filters' => $filters,
            'users'   => $this->users->activeUsers(),
            'active'  => 'audit',
            'title'   => 'Journal d\'audit',
        ]);
    }
}
