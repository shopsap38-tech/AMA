<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repository des actions correctives et de leurs commentaires.
 */
final class CorrectiveActionRepository extends BaseRepository
{
    protected string $table = 'corrective_actions';

    protected array $fillable = [
        'non_conformity_id', 'title', 'description', 'assignee_id',
        'due_date', 'priority', 'status', 'completed_at', 'created_by',
    ];

    /** @return array<int, array<string, mixed>> */
    public function forNonConformity(int $ncId): array
    {
        return $this->db->all(
            "SELECT ca.*,
                    CONCAT(a.first_name, ' ', a.last_name) AS assignee_name,
                    DATEDIFF(CURDATE(), ca.due_date) AS days_overdue
             FROM corrective_actions ca
             LEFT JOIN users a ON a.id = ca.assignee_id
             WHERE ca.non_conformity_id = ?
             ORDER BY ca.due_date ASC",
            [$ncId]
        );
    }

    /** Nombre d'actions correctives en retard (échues et non terminées). */
    public function countOverdue(): int
    {
        return (int) $this->db->scalar(
            "SELECT COUNT(*) FROM corrective_actions
             WHERE due_date < CURDATE() AND status NOT IN ('terminee', 'annulee')"
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function overdue(int $limit = 10): array
    {
        return $this->db->all(
            "SELECT ca.id, ca.title, ca.due_date, ca.priority, ca.status,
                    nc.reference AS nc_reference, nc.id AS nc_id,
                    CONCAT(a.first_name, ' ', a.last_name) AS assignee_name,
                    DATEDIFF(CURDATE(), ca.due_date) AS days_overdue
             FROM corrective_actions ca
             INNER JOIN non_conformities nc ON nc.id = ca.non_conformity_id
             LEFT JOIN users a ON a.id = ca.assignee_id
             WHERE ca.due_date < CURDATE() AND ca.status NOT IN ('terminee', 'annulee')
             ORDER BY ca.due_date ASC
             LIMIT {$limit}"
        );
    }

    public function markCompleted(int $id): void
    {
        $this->db->query(
            "UPDATE corrective_actions SET status = 'terminee', completed_at = NOW(), updated_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    // --- Commentaires -------------------------------------------------------

    public function addComment(int $actionId, int $userId, string $body): int
    {
        $this->db->query(
            'INSERT INTO corrective_action_comments (corrective_action_id, user_id, body, created_at)
             VALUES (?, ?, ?, NOW())',
            [$actionId, $userId, $body]
        );
        return $this->db->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function comments(int $actionId): array
    {
        return $this->db->all(
            "SELECT c.*, CONCAT(u.first_name, ' ', u.last_name) AS author
             FROM corrective_action_comments c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.corrective_action_id = ?
             ORDER BY c.created_at ASC",
            [$actionId]
        );
    }
}
