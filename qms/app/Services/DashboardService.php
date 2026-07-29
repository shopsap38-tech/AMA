<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CorrectiveActionRepository;
use App\Repositories\NonConformityRepository;

/**
 * Agrège les indicateurs (KPI) et jeux de données des graphiques du tableau de
 * bord.
 */
final class DashboardService
{
    private const SEVERITY_LABELS = [
        'critique' => 'Critique',
        'majeure'  => 'Majeure',
        'mineure'  => 'Mineure',
    ];

    private const STATUS_LABELS = [
        'ouverte'           => 'Ouverte',
        'en_analyse'        => 'En analyse',
        'action_corrective' => 'Action corrective',
        'validation'        => 'Validation',
        'cloturee'          => 'Clôturée',
    ];

    private const ORIGIN_LABELS = [
        'production'  => 'Production',
        'stock'       => 'Stock',
        'reception'   => 'Réception',
        'expedition'  => 'Expédition',
        'client'      => 'Client',
        'fournisseur' => 'Fournisseur',
    ];

    public function __construct(
        private readonly NonConformityRepository $nc,
        private readonly CorrectiveActionRepository $actions,
    ) {
    }

    /** @return array<string, mixed> */
    public function kpis(): array
    {
        return [
            'total'          => $this->nc->count(),
            'open'           => $this->nc->countOpen(),
            'critical'       => $this->nc->countBySeverity('critique'),
            'overdue'        => $this->actions->countOverdue(),
            'resolution'     => $this->nc->resolutionRate(),
            'avg_days'       => $this->nc->averageResolutionDays(),
            'closed'         => $this->nc->countByStatus('cloturee'),
            'in_analysis'    => $this->nc->countByStatus('en_analyse'),
        ];
    }

    /** @return array<string, mixed> */
    public function charts(): array
    {
        return [
            'severity'   => $this->labeled($this->nc->distributionBySeverity(), self::SEVERITY_LABELS),
            'status'     => $this->labeled($this->nc->distributionByStatus(), self::STATUS_LABELS),
            'origin'     => $this->labeled($this->nc->distributionByOrigin(), self::ORIGIN_LABELS),
            'department' => $this->departmentPerformance(),
            'trend'      => $this->trend(),
            'heatmap'    => $this->heatmap(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function recentNonConformities(): array
    {
        return $this->nc->recent();
    }

    /** @return array<int, array<string, mixed>> */
    public function overdueActions(): array
    {
        return $this->actions->overdue();
    }

    /**
     * @param array<string, int>    $data
     * @param array<string, string> $labels
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function labeled(array $data, array $labels): array
    {
        $out = ['labels' => [], 'values' => []];
        foreach ($labels as $key => $label) {
            $out['labels'][] = $label;
            $out['values'][] = (int) ($data[$key] ?? 0);
        }
        return $out;
    }

    /** @return array{labels: array<int, string>, total: array<int, int>, closed: array<int, int>} */
    private function departmentPerformance(): array
    {
        $rows = $this->nc->performanceByDepartment();
        return [
            'labels' => array_column($rows, 'department'),
            'total'  => array_map('intval', array_column($rows, 'total')),
            'closed' => array_map('intval', array_column($rows, 'closed')),
        ];
    }

    /** @return array{labels: array<int, string>, total: array<int, int>, closed: array<int, int>} */
    private function trend(): array
    {
        $rows = $this->nc->monthlyTrend();
        $labels = [];
        foreach ($rows as $row) {
            $labels[] = $this->formatPeriod($row['period']);
        }
        return [
            'labels' => $labels,
            'total'  => array_map('intval', array_column($rows, 'total')),
            'closed' => array_map('intval', array_column($rows, 'closed')),
        ];
    }

    /** @return array{departments: array<int, string>, severities: array<int, string>, matrix: array<int, array<int, int>>} */
    private function heatmap(): array
    {
        $rows = $this->nc->heatmapSeverityByDepartment();
        $departments = [];
        $severities = ['critique', 'majeure', 'mineure'];
        $map = [];

        foreach ($rows as $row) {
            $departments[$row['department']] = true;
            $map[$row['department']][$row['severity']] = (int) $row['total'];
        }

        $deptList = array_keys($departments);
        $matrix = [];
        foreach ($deptList as $dept) {
            $line = [];
            foreach ($severities as $severity) {
                $line[] = $map[$dept][$severity] ?? 0;
            }
            $matrix[] = $line;
        }

        return [
            'departments' => $deptList,
            'severities'  => array_map(static fn (string $s): string => self::SEVERITY_LABELS[$s], $severities),
            'matrix'      => $matrix,
        ];
    }

    private function formatPeriod(string $period): string
    {
        $months = ['01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr', '05' => 'Mai', '06' => 'Juin',
                   '07' => 'Juil', '08' => 'Août', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc'];
        [$year, $month] = explode('-', $period);
        return ($months[$month] ?? $month) . ' ' . substr($year, 2);
    }
}
