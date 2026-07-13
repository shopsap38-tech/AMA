<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Statistiques';
$currentPage = 'statistiques';

$c = stats_chariots($pdo);
$arret = temps_arret_par_chariot($pdo);
$totalArret = array_sum(array_column($arret, 'heures'));

require_once __DIR__ . '/includes/header.php';
?>

<h2>Statistiques</h2>

<div class="kpi-grid">
    <div class="kpi-card green">
        <div class="kpi-label">Taux de disponibilité de la flotte</div>
        <div class="kpi-value"><?= $c['taux_dispo'] ?><span class="kpi-unit">%</span></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Chariots opérationnels</div>
        <div class="kpi-value"><?= $c['disponible'] ?> / <?= $c['total'] ?></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">Temps d'arrêt cumulé</div>
        <div class="kpi-value"><?= number_format($totalArret, 1, ',', ' ') ?><span class="kpi-unit"> h</span></div>
    </div>
</div>

<div class="charts-grid">
    <div class="card">
        <h3>Disponibilité des chariots</h3>
        <canvas id="chartDispo"></canvas>
    </div>
    <div class="card">
        <h3>Temps d'arrêt par chariot (heures)</h3>
        <canvas id="chartArret"></canvas>
    </div>
</div>

<h3>Détail du temps d'arrêt</h3>
<table class="table">
    <thead>
        <tr><th>Chariot</th><th>État actuel</th><th>Temps d'arrêt (h)</th></tr>
    </thead>
    <tbody>
        <?php foreach ($arret as $a): ?>
            <tr>
                <td><strong><?= htmlspecialchars($a['ref']) ?></strong></td>
                <td><span class="badge <?= etat_chariot_badge($a['etat']) ?>"><?= etat_chariot_label($a['etat']) ?></span></td>
                <td><?= number_format($a['heures'], 1, ',', ' ') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/fpms/assets/charts.js"></script>
<script>
const COL = { green:'#2b8a3e', orange:'#f08c00', red:'#c92a2a', blue:'#1971c2' };

histogramme('chartDispo', ['Opérationnel', 'Réparation', 'En panne'],
    [<?= $c['disponible'] ?>, <?= $c['maintenance'] ?>, <?= $c['panne'] ?>],
    [COL.green, COL.orange, COL.red], { titre: 'Chariots' });

histogramme('chartArret', <?= json_encode(array_column($arret, 'ref')) ?>,
    <?= json_encode(array_column($arret, 'heures')) ?>, COL.red,
    { titre: 'Heures', horizontal: true });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
