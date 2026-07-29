<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Validator;
use App\Services\CorrectiveActionService;

/**
 * Gestion des actions correctives rattachées à une non-conformité.
 */
final class CorrectiveActionController extends Controller
{
    private const PRIORITIES = ['basse', 'normale', 'haute', 'urgente'];
    private const STATUSES = ['a_faire', 'en_cours', 'terminee', 'annulee'];

    public function __construct(private readonly CorrectiveActionService $service)
    {
    }

    public function store(Request $request, int $ncId): never
    {
        $data = $request->only(['title', 'description', 'assignee_id', 'due_date', 'priority']);

        $validator = new Validator($data, [
            'title'       => 'required|max:255',
            'due_date'    => 'required|date',
            'priority'    => 'required|in:' . implode(',', self::PRIORITIES),
            'assignee_id' => 'integer',
        ], ['title' => 'Titre', 'due_date' => 'Date limite', 'priority' => 'Priorité']);

        if ($validator->fails()) {
            $this->back('nonconformities/' . $ncId, $validator->errors(), $data);
        }

        $data['non_conformity_id'] = $ncId;
        $data['assignee_id'] = !empty($data['assignee_id']) ? (int) $data['assignee_id'] : null;

        $this->service->create($data, (int) auth()->id());
        $this->withSuccess('nonconformities/' . $ncId, 'Action corrective ajoutée.');
    }

    public function update(Request $request, int $ncId, int $id): never
    {
        $data = $request->only(['title', 'description', 'assignee_id', 'due_date', 'priority', 'status']);
        $data['assignee_id'] = !empty($data['assignee_id']) ? (int) $data['assignee_id'] : null;

        $validator = new Validator($data, [
            'status'   => 'required|in:' . implode(',', self::STATUSES),
            'priority' => 'in:' . implode(',', self::PRIORITIES),
        ], ['status' => 'Statut']);

        if ($validator->fails()) {
            $this->back('nonconformities/' . $ncId, $validator->errors(), $data);
        }

        $this->service->update($id, $data);
        $this->withSuccess('nonconformities/' . $ncId, 'Action corrective mise à jour.');
    }

    public function comment(Request $request, int $ncId, int $id): never
    {
        $body = trim((string) $request->input('body'));
        if ($body === '') {
            $this->withError('nonconformities/' . $ncId, 'Le commentaire ne peut pas être vide.');
        }

        $this->service->addComment($id, (int) auth()->id(), $body);
        $this->withSuccess('nonconformities/' . $ncId, 'Commentaire ajouté.');
    }

    public function destroy(int $ncId, int $id): never
    {
        $this->service->delete($id);
        $this->withSuccess('nonconformities/' . $ncId, 'Action corrective supprimée.');
    }
}
