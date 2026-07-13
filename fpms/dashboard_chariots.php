<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Dashboard Chariots';
$currentPage = 'dash_chariots';

$c    = stats_chariots($pdo);
$parEmp = chariots_par_type_employe($pdo);
$arret  = temps_arret_par_chariot($pdo);

$chariots = $pdo->query(
    "SELECT c.*, e.nom, e.prenom, e.type AS emp_type
       FROM chariots c
       LEFT JOIN employes e ON e.id = c.operateur_id
   ORDER BY c.code"
)->fetchAll();

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
        <h3>Disponibilité selon le type d'employé</h3>
        <canvas id="chEmp"></canvas>
    </div>
</div>

<h3>Permanents vs journaliers (opérateurs)</h3>
<div class="two-col">
    <?php foreach (['permanent', 'journalier'] as $t): $d = $parEmp[$t]; ?>
        <div class="card">
            <h3><span class="emp-tag emp-<?= $t ?>"><?= type_employe_label($t) ?></span></h3>
            <p style="margin:.5rem 0 .25rem"><strong><?= $d['total'] ?></strong> chariot(s) affecté(s)
                &middot; taux de disponibilité <strong><?= $d['taux_dispo'] ?>%</strong></p>
            <div class="progress <?= $d['taux_dispo'] < 50 ? 'danger' : ($d['taux_dispo'] < 80 ? 'warn' : '') ?>">
                <span style="width: <?= $d['taux_dispo'] ?>%"></span>
            </div>
            <table class="table" style="margin-top:1rem">
                <tr><th>Disponibles</th><td><?= $d['disponible'] ?></td></tr>
                <tr><th>En maintenance</th><td><?= $d['maintenance'] ?></td></tr>
                <tr><th>En panne</th><td><?= $d['panne'] ?></td></tr>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<h3 style="margin-top:2rem">Détail de la flotte</h3>
<table class="table">
    <thead>
        <tr><th>Code</th><th>Marque / Modèle</th><th>Type</th><th>État</th><th>Opérateur</th><th>Statut employé</th></tr>
    </thead>
    <tbody>
        <?php foreach ($chariots as $ch): ?>
            <tr class="<?= $ch['etat'] === 'panne' ? 'row-alert' : '' ?>">
                <td><strong><?= htmlspecialchars($ch['code']) ?></strong></td>
                <td><?= htmlspecialchars($ch['marque'] . ' ' . $ch['modele']) ?></td>
                <td><?= $ch['type'] === 'electrique' ? 'Électrique' : 'Diesel' ?></td>
                <td><span class="badge <?= etat_chariot_badge($ch['etat']) ?>"><?= etat_chariot_label($ch['etat']) ?></span></td>
                <td><?= $ch['nom'] ? htmlspecialchars($ch['prenom'] . ' ' . $ch['nom']) : '—' ?></td>
                <td><?= $ch['emp_type'] ? '<span class="emp-tag emp-' . $ch['emp_type'] . '">' . type_employe_label($ch['emp_type']) . '</span>' : '—' ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const COL = { green:'#2b8a3e', orange:'#f08c00', red:'#c92a2a', blue:'#1971c2', purple:'#7048e8' };

new Chart(chEtat, {
    type: 'doughnut',
    data: { labels: ['Disponible','Maintenance','Panne'],
        datasets: [{ data: [<?= $c['disponible'] ?>,<?= $c['maintenance'] ?>,<?= $c['panne'] ?>],
            backgroundColor: [COL.green, COL.orange, COL.red] }] },
    options: { plugins: { legend: { position: 'bottom' } } }
});
new Chart(chType, {
    type: 'pie',
    data: { labels: ['Électrique','Diesel'],
        datasets: [{ data: [<?= $c['electrique'] ?>,<?= $c['diesel'] ?>],
            backgroundColor: [COL.purple, COL.orange] }] },
    options: { plugins: { legend: { position: 'bottom' } } }
});
new Chart(chArret, {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($arret, 'code')) ?>,
        datasets: [{ label:'Heures', data: <?= json_encode(array_column($arret, 'heures')) ?>,
            backgroundColor: COL.red }] },
    options: { indexAxis:'y', plugins:{ legend:{ display:false } }, scales:{ x:{ beginAtZero:true } } }
});
new Chart(chEmp, {
    type: 'bar',
    data: {
        labels: ['Permanent','Journalier'],
        datasets: [
            { label:'Disponibles', data:[<?= $parEmp['permanent']['disponible'] ?>,<?= $parEmp['journalier']['disponible'] ?>], backgroundColor: COL.green },
            { label:'Maintenance', data:[<?= $parEmp['permanent']['maintenance'] ?>,<?= $parEmp['journalier']['maintenance'] ?>], backgroundColor: COL.orange },
            { label:'Panne', data:[<?= $parEmp['permanent']['panne'] ?>,<?= $parEmp['journalier']['panne'] ?>], backgroundColor: COL.red }
        ]
    },
    options: { scales: { x: { stacked:true }, y: { stacked:true, beginAtZero:true, ticks:{ precision:0 } } } }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
