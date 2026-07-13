<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Tableau de bord';
$currentPage = 'dashboard';

$c = stats_chariots($pdo);
$p = stats_palettes($pdo);

$palettesConformes = $p['conforme'];

require_once __DIR__ . '/includes/header.php';
?>

<h2>Tableau de bord</h2>

<?php if ($palettesConformes <= SEUIL_PALETTES): ?>
    <p class="alert alert-warning">
        ⚠️ Stock de palettes conformes faible : <?= $palettesConformes ?> (seuil : <?= SEUIL_PALETTES ?>).
        Voir les <a href="/fpms/alertes.php">alertes</a>.
    </p>
<?php endif; ?>

<h3>Accès rapide</h3>
<div class="charts-grid">
    <a class="card card-link accent-green" href="/fpms/palettes.php">
        <h3>📦 Gestion des palettes</h3>
        <p class="hint">Quantités, conformité, évolution et saisie des palettes.</p>
    </a>
    <a class="card card-link accent-blue" href="/fpms/chariots.php">
        <h3>🚜 Gestion des chariots</h3>
        <p class="hint">Disponibilité, temps d'arrêt, répartition par unité et saisie.</p>
    </a>
</div>

<h3>KPI en temps réel</h3>
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Total chariots</div>
        <div class="kpi-value"><?= $c['total'] ?></div>
    </div>
    <div class="kpi-card purple">
        <div class="kpi-label">Chariots électriques</div>
        <div class="kpi-value"><?= $c['electrique'] ?></div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-label">Chariots diesel</div>
        <div class="kpi-value"><?= $c['diesel'] ?></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Chariots disponibles</div>
        <div class="kpi-value"><?= $c['disponible'] ?></div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-label">En maintenance</div>
        <div class="kpi-value"><?= $c['maintenance'] ?></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">En panne</div>
        <div class="kpi-value"><?= $c['panne'] ?></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Palettes conformes (qté)</div>
        <div class="kpi-value"><?= $p['conforme'] ?></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">Palettes non conformes (qté)</div>
        <div class="kpi-value"><?= $p['non_conforme'] + $p['cassee'] ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Taux de disponibilité</div>
        <div class="kpi-value"><?= $c['taux_dispo'] ?><span class="kpi-unit">%</span></div>
    </div>
</div>

<h3>Graphiques interactifs</h3>
<div class="charts-grid">
    <div class="card"><h3>Chariots par type</h3><canvas id="chartType"></canvas></div>
    <div class="card"><h3>Palettes par état (quantité)</h3><canvas id="chartPalettes"></canvas></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/fpms/assets/charts.js"></script>
<script>
const COL = { green:'#16a34a', orange:'#f59e0b', red:'#dc2626', blue:'#2563eb', purple:'#7c3aed' };

histogramme('chartType', ['Électrique', 'Diesel'],
    [<?= $c['electrique'] ?>, <?= $c['diesel'] ?>], [COL.purple, COL.orange], { titre: 'Chariots' });

histogramme('chartPalettes', ['Conforme', 'Non conforme', 'Cassée'],
    [<?= $p['conforme'] ?>, <?= $p['non_conforme'] ?>, <?= $p['cassee'] ?>],
    [COL.green, COL.orange, COL.red], { titre: 'Quantité' });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
