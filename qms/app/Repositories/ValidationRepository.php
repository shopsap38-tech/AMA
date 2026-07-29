<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repository du workflow de validation (circuit de signatures).
 */
final class ValidationRepository extends BaseRepository
{
    protected string $table = 'validation_steps';

    protected array $fillable = [
        'non_conformity_id', 'step_order', 'role_required', 'step_label',
        'status', 'approver_id', 'signature', 'comment', 'acted_at',
    ];

    /** @return array<int, array<string, mixed>> */
    public function forNonConformity(int $ncId): array
    {
        return $this->db->all(
            "SELECT vs.*, CONCAT(u.first_name, ' ', u.last_name) AS approver_name
             FROM validation_steps vs
             LEFT JOIN users u ON u.id = vs.approver_id
             WHERE vs.non_conformity_id = ?
             ORDER BY vs.step_order ASC",
            [$ncId]
        );
    }

    /** Retourne la prochaine étape en attente. @return array<string, mixed>|null */
    public function currentStep(int $ncId): ?array
    {
        return $this->db->first(
            "SELECT * FROM validation_steps
             WHERE non_conformity_id = ? AND status = 'en_attente'
             ORDER BY step_order ASC LIMIT 1",
            [$ncId]
        );
    }

    public function act(int $stepId, string $status, int $approverId, string $signature, ?string $comment): void
    {
        $this->db->query(
            "UPDATE validation_steps
             SET status = ?, approver_id = ?, signature = ?, comment = ?, acted_at = NOW()
             WHERE id = ?",
            [$status, $approverId, $signature, $comment, $stepId]
        );
    }

    public function allApproved(int $ncId): bool
    {
        $pending = (int) $this->db->scalar(
            "SELECT COUNT(*) FROM validation_steps
             WHERE non_conformity_id = ? AND status <> 'approuve'",
            [$ncId]
        );
        return $pending === 0;
    }
}
