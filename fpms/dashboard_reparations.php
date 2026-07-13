<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Dashboard Réparations';
$currentPage = 'dash_reparations';

$parJour = reparations_par_jour($pdo, 14);
$parEmp  = reparations_par_type_employe($pdo);
$top     = top_reparateurs($pdo, 8);

$totRep   = (int) $pdo->query('SELECT COUNT(*) FROM reparations')->fetchColumn();
$totOk    = (int) $pdo->query("SELECT COUNT(*) FROM reparations WHERE resultat = 'reparee'")->fetchColumn();
$totKo    = $totRep - $totOk;
$repJour  = (int) $pdo->query('SELECT COUNT(*) FROM reparations WHERE date_reparation = CURDATE()')->fetchColumn();
$tauxOk   = pct($totOk, $totRep);

$recentes = $pdo->query(
    "SELECT r.*, p.code AS palette_code, e.nom, e.prenom, e.type AS emp_type
       FROM reparations r
       JOIN palettes p ON p.id = r.palette_id
       LEFT JOIN employes e ON e.id = r.employe_id
   ORDER BY r.date_reparation DESC, r.id DESC
      LIMIT 15"
)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Dashboard — Réparations des palettes</h2>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Total réparations</div>
        <div class="kpi-value"><?= $totRep ?></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Réparées</div>
        <div class="kpi-value"><?= $totOk ?>
            <span class="kpi-unit">(<?= $tauxOk ?>%)</span></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">Irréparables</div>
        <div class="kpi-value"><?= $totKo ?>
            <span class="kpi-unit">(<?= pct($totKo, $totRep) ?>%)</span></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Taux de réussite</div>
        <div class="kpi-value"><?= $tauxOk ?><span class="kpi-unit">%</span></div>
    </div>
    <div class="kpi-card blue">
        <div class="kpi-label">Réparations aujourd'hui</div>
        <div class="kpi-value"><?= $repJour ?></div>
    </div>
</div>

<div class="charts-grid">
    <div class="card"><h3>Réparations par jour (14 j)</h3><canvas id="chJour"></canvas></div>
    <div class="card"><h3>Résultats par type d'employé</h3><canvas id="chEmp"></canvas></div>
    <div class="card"><h3>Taux de réussite par type d'employé</h3><canvas id="chTaux"></canvas></div>
</div>

<h3>Permanents vs journaliers (réparateurs)</h3>
<div class="two-col">
    <?php foreach (['permanent', 'journalier'] as $t): $d = $parEmp[$t]; ?>
        <div class="card">
            <h3><span class="emp-tag emp-<?= $t ?>"><?= type_employe_label($t) ?></span></h3>
            <p style="margin:.5rem 0 .25rem"><strong><?= $d['total'] ?></strong> réparation(s)
                &middot; réussite <strong><?= $d['taux_reussite'] ?>%</strong></p>
            <div class="progress <?= $d['taux_reussite'] < 50 ? 'danger' : ($d['taux_reussite'] < 80 ? 'warn' : '') ?>">
                <span style="width: <?= $d['taux_reussite'] ?>%"></span>
            </div>
            <table class="table" style="margin-top:1rem">
                <tr><th>Réparées</th><td><?= $d['reparee'] ?></td></tr>
                <tr><th>Irréparables</th><td><?= $d['irreparable'] ?></td></tr>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<div class="two-col" style="margin-top:2rem">
    <div>
        <h3>Classement des réparateurs</h3>
        <table class="table">
            <thead><tr><th>Employé</th><th>Statut</th><th>Total</th><th>Réparées</th><th>Réussite</th></tr></thead>
            <tbody>
                <?php if (empty($top)): ?><tr><td colspan="5">Aucune réparation enregistrée.</td></tr><?php endif; ?>
                <?php foreach ($top as $e): $tx = pct((float) $e['reparees'], (float) $e['total']); ?>
                    <tr>
                        <td><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></td>
                        <td><span class="emp-tag emp-<?= $e['type'] ?>"><?= type_employe_label($e['type']) ?></span></td>
                        <td><?= (int) $e['total'] ?></td>
                        <td><?= (int) $e['reparees'] ?></td>
                        <td><?= $tx ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div>
        <h3>Réparations récentes</h3>
        <table class="table">
            <thead><tr><th>Date</th><th>Palette</th><th>Réparateur</th><th>Résultat</th></tr></thead>
            <tbody>
                <?php if (empty($recentes)): ?><tr><td colspan="4">Aucune réparation.</td></tr><?php endif; ?>
                <?php foreach ($recentes as $r): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($r['date_reparation'])) ?></td>
                        <td><?= htmlspecialchars($r['palette_code']) ?></td>
                        <td><?= $r['nom'] ? htmlspecialchars($r['prenom'] . ' ' . $r['nom']) : '—' ?></td>
                        <td>
                            <?php if ($r['resultat'] === 'reparee'): ?>
                                <span class="badge badge-ok">Réparée</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Irréparable</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const COL = { green:'#2b8a3e', orange:'#f08c00', red:'#c92a2a', blue:'#1971c2' };

new Chart(chJour, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d) => date('d/m', strtotime($d)), array_keys($parJour))) ?>,
        datasets: [{ label:'Réparations', data: <?= json_encode(array_values($parJour)) ?>,
            borderColor: COL.blue, backgroundColor:'rgba(25,113,194,0.15)', fill:true, tension:0.3 }]
    },
    options: { plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
});
new Chart(chEmp, {
    type: 'bar',
    data: {
        labels: ['Permanent','Journalier'],
        datasets: [
            { label:'Réparées', data:[<?= $parEmp['permanent']['reparee'] ?>,<?= $parEmp['journalier']['reparee'] ?>], backgroundColor: COL.green },
            { label:'Irréparables', data:[<?= $parEmp['permanent']['irreparable'] ?>,<?= $parEmp['journalier']['irreparable'] ?>], backgroundColor: COL.red }
        ]
    },
    options: { scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true, ticks:{ precision:0 } } } }
});
new Chart(chTaux, {
    type: 'bar',
    data: { labels: ['Permanent','Journalier'],
        datasets: [{ label:'Taux de réussite (%)',
            data:[<?= $parEmp['permanent']['taux_reussite'] ?>,<?= $parEmp['journalier']['taux_reussite'] ?>],
            backgroundColor: [COL.blue, COL.orange] }] },
    options: { plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, max:100 } } }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
