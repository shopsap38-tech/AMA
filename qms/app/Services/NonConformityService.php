<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\NonConformityRepository;
use App\Repositories\UserRepository;
use App\Repositories\ValidationRepository;

/**
 * Logique métier des non-conformités : création avec numérotation automatique,
 * initialisation du circuit de validation, mise à jour et journalisation.
 */
final class NonConformityService
{
    /**
     * Circuit de validation standard (ordre, rôle requis, libellé).
     *
     * @var array<int, array{role: string, label: string}>
     */
    private const WORKFLOW = [
        ['role' => 'employe',                'label' => 'Déclaration (Employé)'],
        ['role' => 'chef_equipe',            'label' => "Revue Chef d'équipe"],
        ['role' => 'responsable_qualite',    'label' => 'Validation Responsable Qualité'],
        ['role' => 'responsable_production', 'label' => 'Validation Responsable Production'],
        ['role' => 'direction',              'label' => 'Approbation Direction'],
    ];

    public function __construct(
        private readonly Database $db,
        private readonly NonConformityRepository $repository,
        private readonly ValidationRepository $validations,
        private readonly UserRepository $users,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {
    }

    /**
     * Crée une non-conformité, son circuit de validation et notifie le responsable.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $authorId): int
    {
        return $this->db->transaction(function () use ($data, $authorId): int {
            $data['reference']  = $this->repository->nextReference((int) date('Y'));
            $data['status']     = 'ouverte';
            $data['created_by'] = $authorId;

            $id = $this->repository->create($data);
            $this->initWorkflow($id);

            $this->audit->log('create', 'non_conformity', $id, null, $data);

            if (!empty($data['responsible_id'])) {
                $this->notifications->notify(
                    (int) $data['responsible_id'],
                    'nc_assigned',
                    'Nouvelle non-conformité assignée',
                    "La non-conformité {$data['reference']} vous a été assignée.",
                    'nonconformities/' . $id
                );
            }

            return $id;
        });
    }

    /**
     * Met à jour une non-conformité en conservant l'ancien état pour l'audit.
     *
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $old = $this->repository->find($id);
        $result = $this->repository->update($id, $data);
        $this->audit->log('update', 'non_conformity', $id, $old, $data);
        return $result;
    }

    public function delete(int $id): bool
    {
        $old = $this->repository->find($id);
        $result = $this->repository->delete($id);
        $this->audit->log('delete', 'non_conformity', $id, $old, null);
        return $result;
    }

    private function initWorkflow(int $ncId): void
    {
        foreach (self::WORKFLOW as $order => $step) {
            $this->validations->create([
                'non_conformity_id' => $ncId,
                'step_order'        => $order + 1,
                'role_required'     => $step['role'],
                'step_label'        => $step['label'],
                'status'            => 'en_attente',
            ]);
        }
    }
}
