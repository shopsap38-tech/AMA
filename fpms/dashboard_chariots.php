<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Dashboard Chariots';
$currentPage = 'dash_chariots';

$periode = periode_valide($_GET['periode'] ?? null);
$c       = stats_chariots($pdo);
$arret   = temps_arret_par_chariot($pdo);
$evo     = chariots_evolution($pdo, $periode);
$unites  = unites();
$parUnite = chariots_par_unite($pdo);

$chariots = $pdo->query('SELECT * FROM chariots ORDER BY code')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Dashboard — Chariots élévateurs</h2>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Total chariots</div>
        <div class="kpi-value"><?= $c['total'] ?></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Disponibles</div>
        <div class="kpi-value"><?= $c['disponible'] ?>
            <span class="kpi-unit">(<?= $c['taux_dispo'] ?>%)</span></div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-label">En maintenance</div>
        <div class="kpi-value"><?= $c['maintenance'] ?>
            <span class="kpi-unit">(<?= pct($c['maintenance'], $c['total']) ?>%)</span></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">En panne</div>
        <div class="kpi-value"><?= $c['panne'] ?>
            <span class="kpi-unit">(<?= pct($c['panne'], $c['total']) ?>%)</span></div>
    </div>
    <div class="kpi-card purple">
        <div class="kpi-label">Électriques / Diesel</div>
        <div class="kpi-value"><?= $c['electrique'] ?> / <?= $c['diesel'] ?></div>
    </div>
</div>

<div class="charts-grid">
    <div class="card"><h3>Répartition par état</h3><canvas id="chEtat"></canvas></div>
    <div class="card"><h3>Répartition par type</h3><canvas id="chType"></canvas></div>
    <div class="card"><h3>Temps d'arrêt par chariot (h)</h3><canvas id="chArret"></canvas></div>
    <div class="card">
        <div class="card-head">
            <h3>Chariots mis en service</h3>
            <?= periode_selector($periode) ?>
        </div>
        <canvas id="chEvo"></canvas>
    </div>
</div>

<h3>Répartition des chariots par unité</h3>
<div class="card card-chart">
    <h3>Nombre de chariots par unité</h3>
    <canvas id="chUnite"></canvas>
</div>

<table class="table" style="margin-bottom:2rem">
    <thead>
        <tr><th>Unité</th><th>Nombre de chariots</th><th>Pourcentage</th></tr>
    </thead>
    <tbody>
        <?php foreach ($unites as $key => $label): $n = $parUnite['data'][$key]; ?>
            <tr>
                <td><strong><?= htmlspecialchars($label) ?></strong></td>
                <td><?= $n ?></td>
                <td><?= pct($n, $parUnite['total']) ?> %</td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td><strong>Total (affectés)</strong></td>
            <td><strong><?= $parUnite['total'] ?></strong></td>
            <td>100 %</td>
        </tr>
    </tbody>
</table>

<h3>Détail de la flotte</h3>
<table class="table">
    <thead>
        <tr><th>Code</th><th>Marque</th><th>Type</th><th>Unité</th><th>État</th><th>Mise en service</th></tr>
    </thead>
    <tbody>
        <?php foreach ($chariots as $ch): ?>
            <tr class="<?= $ch['etat'] === 'panne' ? 'row-alert' : '' ?>">
                <td><strong><?= htmlspecialchars($ch['code']) ?></strong></td>
                <td><?= htmlspecialchars($ch['marque']) ?></td>
                <td><?= $ch['type'] === 'electrique' ? 'Électrique' : 'Diesel' ?></td>
                <td><?= htmlspecialchars(unite_label($ch['unite'])) ?></td>
                <td><span class="badge <?= etat_chariot_badge($ch['etat']) ?>"><?= etat_chariot_label($ch['etat']) ?></span></td>
                <td><?= $ch['date_mise_service'] ? date('d/m/Y', strtotime($ch['date_mise_service'])) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/fpms/assets/charts.js"></script>
<script>
const COL = { green:'#16a34a', orange:'#f59e0b', red:'#dc2626', blue:'#2563eb', purple:'#7c3aed' };

histogramme('chEtat', ['Disponible','Maintenance','Panne'],
    [<?= $c['disponible'] ?>,<?= $c['maintenance'] ?>,<?= $c['panne'] ?>],
    [COL.green, COL.orange, COL.red], { titre: 'Chariots' });

histogramme('chType', ['Électrique','Diesel'],
    [<?= $c['electrique'] ?>,<?= $c['diesel'] ?>], [COL.purple, COL.orange], { titre: 'Chariots' });

histogramme('chArret', <?= json_encode(array_column($arret, 'code')) ?>,
    <?= json_encode(array_column($arret, 'heures')) ?>, COL.red, { titre: 'Heures' });

histogramme('chEvo', <?= json_encode(array_keys($evo)) ?>,
    <?= json_encode(array_values($evo)) ?>, COL.blue, { titre: 'Chariots' });

const UNITE_COL = ['#2563eb','#16a34a','#f59e0b','#dc2626','#7c3aed','#0891b2','#db2777','#65a30d'];
histogramme('chUnite', <?= json_encode(array_values($unites)) ?>,
    <?= json_encode(array_values($parUnite['data'])) ?>, UNITE_COL,
    { titre: 'Chariots', aspectRatio: 3.4 });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
