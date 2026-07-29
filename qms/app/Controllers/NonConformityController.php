<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Repositories\AttachmentRepository;
use App\Repositories\AuditRepository;
use App\Repositories\CorrectiveActionRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\NonConformityRepository;
use App\Repositories\UserRepository;
use App\Repositories\ValidationRepository;
use App\Services\NonConformityService;

/**
 * Gestion des non-conformités (CRUD, recherche, fiche détaillée).
 */
final class NonConformityController extends Controller
{
    private const SEVERITIES = ['critique', 'majeure', 'mineure'];
    private const ORIGINS = ['production', 'stock', 'reception', 'expedition', 'client', 'fournisseur'];
    private const STATUSES = ['ouverte', 'en_analyse', 'action_corrective', 'validation', 'cloturee'];

    public function __construct(
        private readonly NonConformityRepository $repository,
        private readonly NonConformityService $service,
        private readonly DepartmentRepository $departments,
        private readonly UserRepository $users,
        private readonly CorrectiveActionRepository $actions,
        private readonly ValidationRepository $validations,
        private readonly AttachmentRepository $attachments,
        private readonly AuditRepository $audit,
    ) {
    }

    public function index(Request $request): string
    {
        $filters = $request->only([
            'keyword', 'severity', 'status', 'origin',
            'department_id', 'responsible_id', 'product', 'date_from', 'date_to',
        ]);
        // DataTables assure la pagination côté client : on charge le jeu filtré.
        $result = $this->repository->search($filters, 1, 2000);

        return $this->view('nonconformities.index', [
            'items'       => $result['data'],
            'total'       => $result['total'],
            'filters'     => $filters,
            'departments' => $this->departments->options(),
            'users'       => $this->users->activeUsers(),
            'severities'  => self::SEVERITIES,
            'origins'     => self::ORIGINS,
            'statuses'    => self::STATUSES,
            'active'      => 'nonconformities',
            'title'       => 'Non-conformités',
        ]);
    }

    /** Recherche JSON pour DataTables / API REST. */
    public function apiSearch(Request $request): never
    {
        $filters = $request->all();
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->repository->search($filters, $page, (int) $request->query('perPage', 25));
        $this->json($result);
    }

    public function create(): string
    {
        return $this->view('nonconformities.form', [
            'nc'          => null,
            'departments' => $this->departments->options(),
            'users'       => $this->users->activeUsers(),
            'severities'  => self::SEVERITIES,
            'origins'     => self::ORIGINS,
            'active'      => 'nonconformities',
            'title'       => 'Nouvelle non-conformité',
        ]);
    }

    public function store(Request $request): never
    {
        $data = $this->validated($request);
        $id = $this->service->create($data, (int) auth()->id());
        $this->withSuccess('nonconformities/' . $id, 'Non-conformité créée avec succès.');
    }

    public function show(int $id): string
    {
        $nc = $this->repository->findDetailed($id);
        if ($nc === null) {
            $this->withError('nonconformities', 'Non-conformité introuvable.');
        }

        return $this->view('nonconformities.show', [
            'nc'          => $nc,
            'actions'     => $this->actions->forNonConformity($id),
            'validations' => $this->validations->forNonConformity($id),
            'attachments' => $this->attachments->forEntity('non_conformity', $id),
            'history'     => $this->audit->forEntity('non_conformity', $id),
            'users'       => $this->users->activeUsers(),
            'active'      => 'nonconformities',
            'title'       => $nc['reference'],
        ]);
    }

    public function edit(int $id): string
    {
        $nc = $this->repository->find($id);
        if ($nc === null) {
            $this->withError('nonconformities', 'Non-conformité introuvable.');
        }

        return $this->view('nonconformities.form', [
            'nc'          => $nc,
            'departments' => $this->departments->options(),
            'users'       => $this->users->activeUsers(),
            'severities'  => self::SEVERITIES,
            'origins'     => self::ORIGINS,
            'active'      => 'nonconformities',
            'title'       => 'Modifier ' . $nc['reference'],
        ]);
    }

    public function update(Request $request, int $id): never
    {
        $data = $this->validated($request);
        unset($data['reference']); // La référence n'est jamais modifiable.
        $this->service->update($id, $data);
        $this->withSuccess('nonconformities/' . $id, 'Non-conformité mise à jour.');
    }

    public function destroy(int $id): never
    {
        $this->service->delete($id);
        $this->withSuccess('nonconformities', 'Non-conformité supprimée.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->only([
            'occurred_on', 'occurred_at', 'department_id', 'workshop', 'location',
            'product', 'product_reference', 'batch', 'quantity', 'severity',
            'observation', 'origin', 'description', 'root_cause_analysis',
            'impact', 'responsible_id',
        ]);

        $validator = new Validator($data, [
            'occurred_on'    => 'required|date',
            'department_id'  => 'required|integer',
            'product'        => 'required|max:255',
            'severity'       => 'required|in:' . implode(',', self::SEVERITIES),
            'origin'         => 'required|in:' . implode(',', self::ORIGINS),
            'description'    => 'required|min:5',
            'quantity'       => 'numeric',
            'responsible_id' => 'integer',
        ], [
            'occurred_on'   => 'Date',
            'department_id' => 'Service',
            'product'       => 'Produit',
            'severity'      => 'Gravité',
            'origin'        => 'Origine',
            'description'   => 'Description',
        ]);

        if ($validator->fails()) {
            $back = $request->input('_id') ? 'nonconformities/' . $request->input('_id') . '/edit' : 'nonconformities/create';
            $this->back($back, $validator->errors(), $data);
        }

        // Normalisation des champs optionnels vides.
        $data['department_id'] = (int) $data['department_id'];
        $data['responsible_id'] = !empty($data['responsible_id']) ? (int) $data['responsible_id'] : null;
        $data['quantity'] = $data['quantity'] !== '' ? (float) $data['quantity'] : null;
        $data['occurred_at'] = $data['occurred_at'] ?: null;

        return $data;
    }
}
