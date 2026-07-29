<?php
/**
 * Calcule les KPI et jeux de données des graphiques du tableau de bord.
 * Retourne un tableau ['kpis' => [...], 'charts' => [...]].
 * Nécessite $pdo (via functions.php).
 */

function dashboard_data(PDO $pdo): array
{
    $scalar = fn (string $sql, array $p = []) => (function () use ($pdo, $sql, $p) {
        $s = $pdo->prepare($sql); $s->execute($p); return $s->fetchColumn();
    })();

    $total    = (int) $scalar('SELECT COUNT(*) FROM non_conformities');
    $open     = (int) $scalar("SELECT COUNT(*) FROM non_conformities WHERE status <> 'cloturee'");
    $critical = (int) $scalar("SELECT COUNT(*) FROM non_conformities WHERE severity = 'critique'");
    $closed   = (int) $scalar("SELECT COUNT(*) FROM non_conformities WHERE status = 'cloturee'");
    $analysis = (int) $scalar("SELECT COUNT(*) FROM non_conformities WHERE status = 'en_analyse'");
    $overdue  = (int) $scalar("SELECT COUNT(*) FROM corrective_actions WHERE due_date < CURDATE() AND status NOT IN ('terminee','annulee')");
    $avgDays  = $scalar("SELECT AVG(DATEDIFF(closed_at, created_at)) FROM non_conformities WHERE status = 'cloturee' AND closed_at IS NOT NULL");

    $kpis = [
        'total'       => $total,
        'open'        => $open,
        'critical'    => $critical,
        'overdue'     => $overdue,
        'resolution'  => $total > 0 ? round(($closed / $total) * 100, 1) : 0,
        'avg_days'    => $avgDays !== null ? round((float) $avgDays, 1) : 0,
        'closed'      => $closed,
        'in_analysis' => $analysis,
    ];

    // Helper : distribution étiquetée
    $labeled = function (string $column, array $labels) use ($pdo): array {
        $rows = $pdo->query("SELECT {$column} AS k, COUNT(*) AS total FROM non_conformities GROUP BY {$column}")->fetchAll();
        $map = array_column($rows, 'total', 'k');
        $out = ['labels' => [], 'values' => []];
        foreach ($labels as $key => $label) {
            $out['labels'][] = $label;
            $out['values'][] = (int) ($map[$key] ?? 0);
        }
        return $out;
    };

    $severity = $labeled('severity', ['critique' => 'Critique', 'majeure' => 'Majeure', 'mineure' => 'Mineure']);
    $status   = $labeled('status', ['ouverte' => 'Ouverte', 'en_analyse' => 'En analyse', 'action_corrective' => 'Action corrective', 'validation' => 'Validation', 'cloturee' => 'Clôturée']);
    $origin   = $labeled('origin', ['production' => 'Production', 'stock' => 'Stock', 'reception' => 'Réception', 'expedition' => 'Expédition', 'client' => 'Client', 'fournisseur' => 'Fournisseur']);

    // Performance par service
    $deptRows = $pdo->query(
        "SELECT d.name AS department, COUNT(nc.id) AS total, SUM(nc.status='cloturee') AS closed
         FROM departments d LEFT JOIN non_conformities nc ON nc.department_id = d.id
         GROUP BY d.id, d.name ORDER BY total DESC"
    )->fetchAll();
    $department = [
        'labels' => array_column($deptRows, 'department'),
        'total'  => array_map('intval', array_column($deptRows, 'total')),
        'closed' => array_map('intval', array_column($deptRows, 'closed')),
    ];

    // Évolution mensuelle (12 mois)
    $trendRows = $pdo->query(
        "SELECT DATE_FORMAT(occurred_on, '%Y-%m') AS period, COUNT(*) AS total, SUM(status='cloturee') AS closed
         FROM non_conformities WHERE occurred_on >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
         GROUP BY period ORDER BY period"
    )->fetchAll();
    $months = ['01'=>'Jan','02'=>'Fév','03'=>'Mar','04'=>'Avr','05'=>'Mai','06'=>'Juin','07'=>'Juil','08'=>'Août','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Déc'];
    $trend = ['labels' => [], 'total' => [], 'closed' => []];
    foreach ($trendRows as $r) {
        [$y, $m] = explode('-', $r['period']);
        $trend['labels'][] = ($months[$m] ?? $m) . ' ' . substr($y, 2);
        $trend['total'][]  = (int) $r['total'];
        $trend['closed'][] = (int) $r['closed'];
    }

    // Heatmap gravité × service
    $hmRows = $pdo->query(
        "SELECT d.name AS department, nc.severity, COUNT(*) AS total
         FROM non_conformities nc INNER JOIN departments d ON d.id = nc.department_id
         GROUP BY d.name, nc.severity"
    )->fetchAll();
    $severities = ['critique', 'majeure', 'mineure'];
    $depts = []; $hmMap = [];
    foreach ($hmRows as $r) {
        $depts[$r['department']] = true;
        $hmMap[$r['department']][$r['severity']] = (int) $r['total'];
    }
    $matrix = [];
    foreach (array_keys($depts) as $d) {
        $line = [];
        foreach ($severities as $s) { $line[] = $hmMap[$d][$s] ?? 0; }
        $matrix[] = $line;
    }
    $heatmap = [
        'departments' => array_keys($depts),
        'severities'  => ['Critique', 'Majeure', 'Mineure'],
        'matrix'      => $matrix,
    ];

    return [
        'kpis'   => $kpis,
        'charts' => compact('severity', 'status', 'origin', 'department', 'trend', 'heatmap'),
    ];
}
