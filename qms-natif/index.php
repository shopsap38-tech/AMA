<?php
require __DIR__ . '/includes/functions.php';
require_login();
require __DIR__ . '/includes/dashboard_data.php';

$data = dashboard_data($pdo);
$kpis = $data['kpis'];
$charts = $data['charts'];

// Non-conformités récentes
$recent = $pdo->query(
    "SELECT nc.id, nc.reference, nc.product, nc.severity, nc.status, nc.occurred_on, d.name AS department_name
     FROM non_conformities nc LEFT JOIN departments d ON d.id = nc.department_id
     ORDER BY nc.id DESC LIMIT 8"
)->fetchAll();

// Actions correctives en retard
$overdue = $pdo->query(
    "SELECT ca.title, ca.non_conformity_id AS nc_id, nc.reference AS nc_reference, DATEDIFF(CURDATE(), ca.due_date) AS days_overdue
     FROM corrective_actions ca INNER JOIN non_conformities nc ON nc.id = ca.non_conformity_id
     WHERE ca.due_date < CURDATE() AND ca.status NOT IN ('terminee','annulee')
     ORDER BY ca.due_date ASC LIMIT 10"
)->fetchAll();

$cards = [
    ['label' => 'Total non-conformités', 'value' => $kpis['total'], 'icon' => 'fa-layer-group', 'tone' => 'primary', 'sub' => $kpis['closed'] . ' clôturées'],
    ['label' => 'NC ouvertes', 'value' => $kpis['open'], 'icon' => 'fa-folder-open', 'tone' => 'info', 'sub' => 'À traiter'],
    ['label' => 'NC critiques', 'value' => $kpis['critical'], 'icon' => 'fa-fire', 'tone' => 'danger', 'sub' => 'Priorité maximale'],
    ['label' => 'Actions en retard', 'value' => $kpis['overdue'], 'icon' => 'fa-clock-rotate-left', 'tone' => 'warning', 'sub' => 'Échéance dépassée'],
    ['label' => 'Taux de résolution', 'value' => $kpis['resolution'] . ' %', 'icon' => 'fa-chart-pie', 'tone' => 'success', 'sub' => 'NC clôturées / total'],
    ['label' => 'Temps moyen', 'value' => $kpis['avg_days'] . ' j', 'icon' => 'fa-stopwatch', 'tone' => 'secondary', 'sub' => "De l'ouverture à la clôture"],
];

$pageTitle = 'Tableau de bord';
$activeMenu = 'dashboard';
$pageScripts = '<script>window.QMS && window.QMS.initDashboard && window.QMS.initDashboard();</script>';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div><p class="page-eyebrow">Vue d'ensemble</p><h2 class="page-title">Performance qualité</h2></div>
    <div class="page-head-actions">
        <button class="btn btn-outline-secondary" id="refreshDashboard"><i class="fa-solid fa-arrows-rotate me-2"></i>Actualiser</button>
        <?php if (can('nonconformity.create')): ?>
            <a href="nc_form.php" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Déclarer une NC</a>
        <?php endif; ?>
    </div>
</div>

<div class="kpi-grid">
    <?php foreach ($cards as $c): ?>
        <div class="kpi-card tone-<?= e($c['tone']) ?>">
            <div class="kpi-icon"><i class="fa-solid <?= e($c['icon']) ?>"></i></div>
            <div class="kpi-body">
                <span class="kpi-label"><?= e($c['label']) ?></span>
                <span class="kpi-value"><?= e($c['value']) ?></span>
                <span class="kpi-sub"><?= e($c['sub']) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="chart-grid">
    <div class="panel col-span-2">
        <div class="panel-head"><h3>Évolution mensuelle</h3><span class="panel-hint">12 derniers mois · déclarées vs clôturées</span></div>
        <div class="panel-body"><canvas id="chartTrend" height="120"></canvas></div>
    </div>
    <div class="panel"><div class="panel-head"><h3>Répartition par statut</h3></div><div class="panel-body"><canvas id="chartStatus" height="200"></canvas></div></div>
    <div class="panel"><div class="panel-head"><h3>Par gravité</h3></div><div class="panel-body"><canvas id="chartSeverity" height="200"></canvas></div></div>
    <div class="panel"><div class="panel-head"><h3>Par origine</h3></div><div class="panel-body"><canvas id="chartOrigin" height="200"></canvas></div></div>
    <div class="panel"><div class="panel-head"><h3>Performance par service</h3></div><div class="panel-body"><canvas id="chartDepartment" height="200"></canvas></div></div>
    <div class="panel col-span-3">
        <div class="panel-head"><h3>Carte de chaleur — Gravité par service</h3><span class="panel-hint">Concentration des non-conformités</span></div>
        <div class="panel-body"><div id="heatmap" class="heatmap"></div></div>
    </div>
</div>

<div class="chart-grid">
    <div class="panel col-span-2">
        <div class="panel-head"><h3>Non-conformités récentes</h3><a href="nonconformites.php" class="btn-link-sm">Tout voir</a></div>
        <div class="panel-body p-0">
            <table class="table-clean mb-0" style="width:100%">
                <thead><tr><th>Référence</th><th>Produit</th><th>Service</th><th>Gravité</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr onclick="window.location='nc_show.php?id=<?= (int) $r['id'] ?>'" style="cursor:pointer">
                        <td><span class="ref-tag"><?= e($r['reference']) ?></span></td>
                        <td><?= e($r['product']) ?></td>
                        <td><?= e($r['department_name'] ?? '—') ?></td>
                        <td><span class="badge <?= e(ui_badge('severity', $r['severity'])) ?>"><?= e(ui_label('severity', $r['severity'])) ?></span></td>
                        <td><span class="badge <?= e(ui_badge('status', $r['status'])) ?>"><?= e(ui_label('status', $r['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($recent === []): ?><tr><td colspan="5" class="text-center text-muted py-4">Aucune donnée</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="panel">
        <div class="panel-head"><h3>Actions correctives en retard</h3></div>
        <div class="panel-body p-0">
            <ul class="overdue-list">
                <?php foreach ($overdue as $a): ?>
                    <li><a href="nc_show.php?id=<?= (int) $a['nc_id'] ?>">
                        <div class="overdue-title"><?= e($a['title']) ?></div>
                        <div class="overdue-meta"><span class="ref-tag sm"><?= e($a['nc_reference']) ?></span><span class="overdue-days"><i class="fa-solid fa-triangle-exclamation"></i> +<?= (int) $a['days_overdue'] ?> j</span></div>
                    </a></li>
                <?php endforeach; ?>
                <?php if ($overdue === []): ?><li class="text-muted p-3">Aucune action en retard 🎉</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<script id="dashboard-data" type="application/json"><?= json_encode($charts, JSON_UNESCAPED_UNICODE) ?></script>

<?php require __DIR__ . '/includes/footer.php'; ?>
