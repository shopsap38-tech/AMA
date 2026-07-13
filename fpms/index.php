<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Tableau de bord';
$currentPage = 'dashboard';

$c = stats_chariots($pdo);
$p = stats_palettes($pdo);
$rep = reparations_par_jour($pdo, 14);

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

<h3>Tableaux de bord</h3>
<div class="charts-grid">
    <a class="card" style="text-decoration:none;color:inherit;border-left:4px solid #2b8a3e" href="/fpms/dashboard_palettes.php">
        <h3>📦 Dashboard Palettes</h3>
        <p class="hint">Conformité, états et contrôleurs (permanents / journaliers).</p>
    </a>
    <a class="card" style="text-decoration:none;color:inherit;border-left:4px solid #1971c2" href="/fpms/dashboard_chariots.php">
        <h3>🚜 Dashboard Chariots</h3>
        <p class="hint">Disponibilité, temps d'arrêt et opérateurs affectés.</p>
    </a>
    <a class="card" style="text-decoration:none;color:inherit;border-left:4px solid #f08c00" href="/fpms/dashboard_reparations.php">
        <h3>🔧 Dashboard Réparations</h3>
        <p class="hint">Taux de réussite et performance par type d'employé.</p>
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
        <div class="kpi-label">Palettes conformes</div>
        <div class="kpi-value"><?= $p['conforme'] ?></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">Palettes non conformes</div>
        <div class="kpi-value"><?= $p['non_conforme'] + $p['cassee'] ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Taux de disponibilité</div>
        <div class="kpi-value"><?= $c['taux_dispo'] ?><span class="kpi-unit">%</span></div>
    </div>
</div>

<h3>Graphiques interactifs</h3>
<div class="charts-grid">
    <div class="card"><h3>État de la flotte</h3><canvas id="chartEtat"></canvas></div>
    <div class="card"><h3>Chariots par type</h3><canvas id="chartType"></canvas></div>
    <div class="card"><h3>Palettes par état</h3><canvas id="chartPalettes"></canvas></div>
    <div class="card"><h3>Réparations / jour (14 j)</h3><canvas id="chartReparations"></canvas></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const COL = { green:'#2b8a3e', orange:'#f08c00', red:'#c92a2a', blue:'#1971c2', purple:'#7048e8', grey:'#adb5bd' };

new Chart(document.getElementById('chartEtat'), {
    type: 'doughnut',
    data: {
        labels: ['Disponible', 'Maintenance', 'Panne'],
        datasets: [{
            data: [<?= $c['disponible'] ?>, <?= $c['maintenance'] ?>, <?= $c['panne'] ?>],
            backgroundColor: [COL.green, COL.orange, COL.red]
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('chartType'), {
    type: 'bar',
    data: {
        labels: ['Électrique', 'Diesel'],
        datasets: [{
            label: 'Chariots',
            data: [<?= $c['electrique'] ?>, <?= $c['diesel'] ?>],
            backgroundColor: [COL.purple, COL.orange]
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

new Chart(document.getElementById('chartPalettes'), {
    type: 'doughnut',
    data: {
        labels: ['Conforme', 'Non conforme', 'Cassée'],
        datasets: [{
            data: [<?= $p['conforme'] ?>, <?= $p['non_conforme'] ?>, <?= $p['cassee'] ?>],
            backgroundColor: [COL.green, COL.orange, COL.red]
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('chartReparations'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('d/m', strtotime($d)), array_keys($rep))) ?>,
        datasets: [{
            label: 'Réparations',
            data: <?= json_encode(array_values($rep)) ?>,
            borderColor: COL.blue,
            backgroundColor: 'rgba(25,113,194,0.15)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
