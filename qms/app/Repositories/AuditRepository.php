<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repository du journal d'audit (traçabilité complète).
 */
final class AuditRepository extends BaseRepository
{
    protected string $table = 'audit_logs';

    protected array $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    /**
     * @param array<string, mixed> $filters
     * @return array{data: array<int, array<string, mixed>>, total: int}
     */
    public function search(array $filters, int $page = 1, int $perPage = 25): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['entity_type'])) {
            $where[] = 'a.entity_type = :entity_type';
            $params[':entity_type'] = $filters['entity_type'];
        }
        if (!empty($filters['action'])) {
            $where[] = 'a.action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :user_id';
            $params[':user_id'] = (int) $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'a.created_at >= :date_from';
            $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'a.created_at <= :date_to';
            $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $total = (int) $this->db->scalar("SELECT COUNT(*) FROM audit_logs a{$whereSql}", $params);
        $offset = max(0, ($page - 1) * $perPage);

        $data = $this->db->all(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             {$whereSql}
             ORDER BY a.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return ['data' => $data, 'total' => $total];
    }

    /** @return array<int, array<string, mixed>> */
    public function forEntity(string $type, int $id): array
    {
        return $this->db->all(
            "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS user_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.entity_type = ? AND a.entity_id = ?
             ORDER BY a.id DESC",
            [$type, $id]
        );
    }
}
