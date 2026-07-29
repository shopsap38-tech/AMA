<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CorrectiveActionRepository;
use App\Repositories\NonConformityRepository;

/**
 * Logique métier des actions correctives.
 */
final class CorrectiveActionService
{
    public function __construct(
        private readonly CorrectiveActionRepository $repository,
        private readonly NonConformityRepository $nc,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, int $authorId): int
    {
        $data['created_by'] = $authorId;
        $data['status'] = $data['status'] ?? 'a_faire';
        $id = $this->repository->create($data);

        $this->audit->log('create', 'corrective_action', $id, null, $data);

        // La NC passe automatiquement au statut « action corrective ».
        $nc = $this->nc->find((int) $data['non_conformity_id']);
        if ($nc !== null && in_array($nc['status'], ['ouverte', 'en_analyse'], true)) {
            $this->nc->changeStatus((int) $nc['id'], 'action_corrective');
        }

        if (!empty($data['assignee_id'])) {
            $this->notifications->notify(
                (int) $data['assignee_id'],
                'action_assigned',
                'Action corrective assignée',
                "Une action corrective « {$data['title']} » vous a été confiée (échéance : {$data['due_date']}).",
                'nonconformities/' . $data['non_conformity_id']
            );
        }

        return $id;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        $old = $this->repository->find($id);

        if (($data['status'] ?? null) === 'terminee' && ($old['status'] ?? null) !== 'terminee') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }

        $result = $this->repository->update($id, $data);
        $this->audit->log('update', 'corrective_action', $id, $old, $data);
        return $result;
    }

    public function addComment(int $actionId, int $userId, string $body): int
    {
        $id = $this->repository->addComment($actionId, $userId, $body);
        $this->audit->log('comment', 'corrective_action', $actionId, null, ['body' => $body]);
        return $id;
    }

    public function delete(int $id): bool
    {
        $old = $this->repository->find($id);
        $result = $this->repository->delete($id);
        $this->audit->log('delete', 'corrective_action', $id, $old, null);
        return $result;
    }
}
