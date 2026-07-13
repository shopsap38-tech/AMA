<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Dashboard Palettes';
$currentPage = 'dash_palettes';

$p      = stats_palettes($pdo);
$parEmp = palettes_par_type_employe($pdo);
$tauxConformite = pct($p['conforme'], $p['total']);

$palettes = $pdo->query(
    "SELECT p.*, e.nom, e.prenom, e.type AS emp_type
       FROM palettes p
       LEFT JOIN employes e ON e.id = p.controleur_id
   ORDER BY p.code"
)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Dashboard — Palettes</h2>

<?php if ($p['conforme'] <= SEUIL_PALETTES): ?>
    <p class="alert alert-warning">⚠️ Stock de palettes conformes faible : <?= $p['conforme'] ?> (seuil : <?= SEUIL_PALETTES ?>).</p>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Total palettes</div>
        <div class="kpi-value"><?= $p['total'] ?></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Conformes</div>
        <div class="kpi-value"><?= $p['conforme'] ?>
            <span class="kpi-unit">(<?= $tauxConformite ?>%)</span></div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-label">Non conformes</div>
        <div class="kpi-value"><?= $p['non_conforme'] ?>
            <span class="kpi-unit">(<?= pct($p['non_conforme'], $p['total']) ?>%)</span></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">Cassées</div>
        <div class="kpi-value"><?= $p['cassee'] ?>
            <span class="kpi-unit">(<?= pct($p['cassee'], $p['total']) ?>%)</span></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Taux de conformité</div>
        <div class="kpi-value"><?= $tauxConformite ?><span class="kpi-unit">%</span></div>
    </div>
</div>

<div class="charts-grid">
    <div class="card"><h3>Répartition par état</h3><canvas id="chEtat"></canvas></div>
    <div class="card"><h3>État par type d'employé (contrôleur)</h3><canvas id="chEmp"></canvas></div>
    <div class="card"><h3>Taux de conformité par type d'employé</h3><canvas id="chTaux"></canvas></div>
</div>

<h3>Permanents vs journaliers (contrôleurs)</h3>
<div class="two-col">
    <?php foreach (['permanent', 'journalier'] as $t): $d = $parEmp[$t]; ?>
        <div class="card">
            <h3><span class="emp-tag emp-<?= $t ?>"><?= type_employe_label($t) ?></span></h3>
            <p style="margin:.5rem 0 .25rem"><strong><?= $d['total'] ?></strong> palette(s) contrôlée(s)
                &middot; conformité <strong><?= $d['taux_conformite'] ?>%</strong></p>
            <div class="progress <?= $d['taux_conformite'] < 50 ? 'danger' : ($d['taux_conformite'] < 80 ? 'warn' : '') ?>">
                <span style="width: <?= $d['taux_conformite'] ?>%"></span>
            </div>
            <table class="table" style="margin-top:1rem">
                <tr><th>Conformes</th><td><?= $d['conforme'] ?></td></tr>
                <tr><th>Non conformes</th><td><?= $d['non_conforme'] ?></td></tr>
                <tr><th>Cassées</th><td><?= $d['cassee'] ?></td></tr>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<h3 style="margin-top:2rem">Détail des palettes</h3>
<table class="table">
    <thead>
        <tr><th>Code</th><th>État</th><th>Contrôleur</th><th>Statut employé</th><th>Commentaire</th></tr>
    </thead>
    <tbody>
        <?php foreach ($palettes as $pal): ?>
            <tr class="<?= $pal['etat'] === 'cassee' ? 'row-alert' : '' ?>">
                <td><strong><?= htmlspecialchars($pal['code']) ?></strong></td>
                <td><span class="badge <?= etat_palette_badge($pal['etat']) ?>"><?= etat_palette_label($pal['etat']) ?></span></td>
                <td><?= $pal['nom'] ? htmlspecialchars($pal['prenom'] . ' ' . $pal['nom']) : '—' ?></td>
                <td><?= $pal['emp_type'] ? '<span class="emp-tag emp-' . $pal['emp_type'] . '">' . type_employe_label($pal['emp_type']) . '</span>' : '—' ?></td>
                <td><?= htmlspecialchars($pal['commentaire'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const COL = { green:'#2b8a3e', orange:'#f08c00', red:'#c92a2a', blue:'#1971c2' };

new Chart(chEtat, {
    type: 'doughnut',
    data: { labels: ['Conforme','Non conforme','Cassée'],
        datasets: [{ data: [<?= $p['conforme'] ?>,<?= $p['non_conforme'] ?>,<?= $p['cassee'] ?>],
            backgroundColor: [COL.green, COL.orange, COL.red] }] },
    options: { plugins: { legend: { position: 'bottom' } } }
});
new Chart(chEmp, {
    type: 'bar',
    data: {
        labels: ['Permanent','Journalier'],
        datasets: [
            { label:'Conformes', data:[<?= $parEmp['permanent']['conforme'] ?>,<?= $parEmp['journalier']['conforme'] ?>], backgroundColor: COL.green },
            { label:'Non conformes', data:[<?= $parEmp['permanent']['non_conforme'] ?>,<?= $parEmp['journalier']['non_conforme'] ?>], backgroundColor: COL.orange },
            { label:'Cassées', data:[<?= $parEmp['permanent']['cassee'] ?>,<?= $parEmp['journalier']['cassee'] ?>], backgroundColor: COL.red }
        ]
    },
    options: { scales: { x: { stacked:true }, y: { stacked:true, beginAtZero:true, ticks:{ precision:0 } } } }
});
new Chart(chTaux, {
    type: 'bar',
    data: { labels: ['Permanent','Journalier'],
        datasets: [{ label:'Taux de conformité (%)',
            data:[<?= $parEmp['permanent']['taux_conformite'] ?>,<?= $parEmp['journalier']['taux_conformite'] ?>],
            backgroundColor: [COL.blue, COL.orange] }] },
    options: { plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, max:100 } } }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
