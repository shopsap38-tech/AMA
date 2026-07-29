<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Repository des non-conformités : CRUD, recherche multicritère et agrégations
 * pour le tableau de bord.
 */
final class NonConformityRepository extends BaseRepository
{
    protected string $table = 'non_conformities';

    protected array $fillable = [
        'reference', 'occurred_on', 'occurred_at', 'department_id', 'workshop',
        'location', 'product', 'product_reference', 'batch', 'quantity',
        'severity', 'observation', 'origin', 'description', 'root_cause_analysis',
        'impact', 'responsible_id', 'status', 'created_by', 'closed_at',
    ];

    /** @return array<string, mixed>|null */
    public function findDetailed(int $id): ?array
    {
        return $this->db->first(
            "SELECT nc.*,
                    d.name AS department_name,
                    CONCAT(r.first_name, ' ', r.last_name) AS responsible_name,
                    CONCAT(c.first_name, ' ', c.last_name) AS created_by_name
             FROM non_conformities nc
             LEFT JOIN departments d ON d.id = nc.department_id
             LEFT JOIN users r ON r.id = nc.responsible_id
             LEFT JOIN users c ON c.id = nc.created_by
             WHERE nc.id = ?",
            [$id]
        );
    }

    /**
     * Recherche multicritère paginée.
     *
     * @param array<string, mixed> $filters
     * @return array{data: array<int, array<string, mixed>>, total: int}
     */
    public function search(array $filters, int $page = 1, int $perPage = 15): array
    {
        [$where, $params] = $this->buildFilters($filters);
        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

        $total = (int) $this->db->scalar(
            "SELECT COUNT(*) FROM non_conformities nc{$whereSql}",
            $params
        );

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT nc.id, nc.reference, nc.occurred_on, nc.severity, nc.status,
                       nc.product, nc.origin, nc.quantity,
                       d.name AS department_name,
                       CONCAT(r.first_name, ' ', r.last_name) AS responsible_name
                FROM non_conformities nc
                LEFT JOIN departments d ON d.id = nc.department_id
                LEFT JOIN users r ON r.id = nc.responsible_id
                {$whereSql}
                ORDER BY nc.id DESC
                LIMIT {$perPage} OFFSET {$offset}";

        return [
            'data'  => $this->db->all($sql, $params),
            'total' => $total,
        ];
    }

    /**
     * Construit les clauses WHERE dynamiques à partir des filtres fournis.
     *
     * @param array<string, mixed> $filters
     * @return array{0: array<int, string>, 1: array<string, mixed>}
     */
    private function buildFilters(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['keyword'])) {
            $where[] = '(nc.reference LIKE :kw OR nc.product LIKE :kw OR nc.description LIKE :kw OR nc.batch LIKE :kw)';
            $params[':kw'] = '%' . $filters['keyword'] . '%';
        }
        if (!empty($filters['severity'])) {
            $where[] = 'nc.severity = :severity';
            $params[':severity'] = $filters['severity'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'nc.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['origin'])) {
            $where[] = 'nc.origin = :origin';
            $params[':origin'] = $filters['origin'];
        }
        if (!empty($filters['department_id'])) {
            $where[] = 'nc.department_id = :department_id';
            $params[':department_id'] = (int) $filters['department_id'];
        }
        if (!empty($filters['responsible_id'])) {
            $where[] = 'nc.responsible_id = :responsible_id';
            $params[':responsible_id'] = (int) $filters['responsible_id'];
        }
        if (!empty($filters['product'])) {
            $where[] = 'nc.product LIKE :product';
            $params[':product'] = '%' . $filters['product'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'nc.occurred_on >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'nc.occurred_on <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }

        return [$where, $params];
    }

    public function nextReference(int $year): string
    {
        $prefix = "NC-{$year}-";
        $last = $this->db->scalar(
            "SELECT reference FROM non_conformities
             WHERE reference LIKE ? ORDER BY reference DESC LIMIT 1",
            [$prefix . '%']
        );

        $sequence = 1;
        if (is_string($last)) {
            $sequence = (int) substr($last, strlen($prefix)) + 1;
        }

        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    // --- Agrégations tableau de bord ---------------------------------------

    public function countByStatus(string $status): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM non_conformities WHERE status = ?', [$status]);
    }

    public function countBySeverity(string $severity): int
    {
        return (int) $this->db->scalar('SELECT COUNT(*) FROM non_conformities WHERE severity = ?', [$severity]);
    }

    public function countOpen(): int
    {
        return (int) $this->db->scalar(
            "SELECT COUNT(*) FROM non_conformities WHERE status <> 'cloturee'"
        );
    }

    public function resolutionRate(): float
    {
        $total = $this->count();
        if ($total === 0) {
            return 0.0;
        }
        $closed = $this->countByStatus('cloturee');
        return round(($closed / $total) * 100, 1);
    }

    /** Temps moyen de traitement (jours) sur les NC clôturées. */
    public function averageResolutionDays(): float
    {
        $value = $this->db->scalar(
            "SELECT AVG(DATEDIFF(closed_at, created_at))
             FROM non_conformities
             WHERE status = 'cloturee' AND closed_at IS NOT NULL"
        );
        return $value === null ? 0.0 : round((float) $value, 1);
    }

    /** @return array<string, int> */
    public function distributionBySeverity(): array
    {
        $rows = $this->db->all(
            'SELECT severity, COUNT(*) AS total FROM non_conformities GROUP BY severity'
        );
        return array_column($rows, 'total', 'severity');
    }

    /** @return array<string, int> */
    public function distributionByStatus(): array
    {
        $rows = $this->db->all(
            'SELECT status, COUNT(*) AS total FROM non_conformities GROUP BY status'
        );
        return array_column($rows, 'total', 'status');
    }

    /** @return array<string, int> */
    public function distributionByOrigin(): array
    {
        $rows = $this->db->all(
            'SELECT origin, COUNT(*) AS total FROM non_conformities GROUP BY origin'
        );
        return array_column($rows, 'total', 'origin');
    }

    /** Performance par service. @return array<int, array<string, mixed>> */
    public function performanceByDepartment(): array
    {
        return $this->db->all(
            "SELECT d.name AS department,
                    COUNT(nc.id) AS total,
                    SUM(nc.status = 'cloturee') AS closed,
                    SUM(nc.status <> 'cloturee') AS open
             FROM departments d
             LEFT JOIN non_conformities nc ON nc.department_id = d.id
             GROUP BY d.id, d.name
             ORDER BY total DESC"
        );
    }

    /** Évolution mensuelle sur les 12 derniers mois. @return array<int, array<string, mixed>> */
    public function monthlyTrend(int $months = 12): array
    {
        return $this->db->all(
            "SELECT DATE_FORMAT(occurred_on, '%Y-%m') AS period,
                    COUNT(*) AS total,
                    SUM(status = 'cloturee') AS closed
             FROM non_conformities
             WHERE occurred_on >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY period
             ORDER BY period",
            [$months]
        );
    }

    /**
     * Heatmap gravité x service (nombre de NC par croisement).
     * @return array<int, array<string, mixed>>
     */
    public function heatmapSeverityByDepartment(): array
    {
        return $this->db->all(
            "SELECT d.name AS department, nc.severity, COUNT(*) AS total
             FROM non_conformities nc
             INNER JOIN departments d ON d.id = nc.department_id
             GROUP BY d.name, nc.severity"
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function recent(int $limit = 8): array
    {
        return $this->db->all(
            "SELECT nc.id, nc.reference, nc.product, nc.severity, nc.status, nc.occurred_on,
                    d.name AS department_name
             FROM non_conformities nc
             LEFT JOIN departments d ON d.id = nc.department_id
             ORDER BY nc.id DESC LIMIT {$limit}"
        );
    }

    public function changeStatus(int $id, string $status, ?string $closedAt = null): void
    {
        $this->db->query(
            'UPDATE non_conformities SET status = ?, closed_at = ?, updated_at = NOW() WHERE id = ?',
            [$status, $closedAt, $id]
        );
    }
}
