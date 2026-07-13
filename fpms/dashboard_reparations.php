<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Dashboard Réparations';
$currentPage = 'dash_reparations';

$parJour = reparations_par_jour($pdo, 14);

$totRep = (int) $pdo->query('SELECT COUNT(*) FROM reparations')->fetchColumn();
$totOk  = (int) $pdo->query("SELECT COUNT(*) FROM reparations WHERE resultat = 'reparee'")->fetchColumn();
$totKo  = $totRep - $totOk;
$repJour = (int) $pdo->query('SELECT COUNT(*) FROM reparations WHERE date_reparation = CURDATE()')->fetchColumn();
$tauxOk = pct($totOk, $totRep);

$recentes = $pdo->query(
    "SELECT r.*, p.code AS palette_code
       FROM reparations r
       JOIN palettes p ON p.id = r.palette_id
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
    <div class="card"><h3>Résultats des réparations</h3><canvas id="chRes"></canvas></div>
</div>

<h3>Réparations récentes</h3>
<table class="table">
    <thead><tr><th>Date</th><th>Palette</th><th>Résultat</th><th>Description</th></tr></thead>
    <tbody>
        <?php if (empty($recentes)): ?><tr><td colspan="4">Aucune réparation enregistrée.</td></tr><?php endif; ?>
        <?php foreach ($recentes as $r): ?>
            <tr>
                <td><?= date('d/m/Y', strtotime($r['date_reparation'])) ?></td>
                <td><?= htmlspecialchars($r['palette_code']) ?></td>
                <td>
                    <?php if ($r['resultat'] === 'reparee'): ?>
                        <span class="badge badge-ok">Réparée</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Irréparable</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['description'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/fpms/assets/charts.js"></script>
<script>
const COL = { green:'#2b8a3e', red:'#c92a2a', blue:'#1971c2' };

histogramme('chJour', <?= json_encode(array_map(fn($d) => date('d/m', strtotime($d)), array_keys($parJour))) ?>,
    <?= json_encode(array_values($parJour)) ?>, COL.blue, { titre: 'Réparations' });

histogramme('chRes', ['Réparées','Irréparables'],
    [<?= $totOk ?>,<?= $totKo ?>], [COL.green, COL.red], { titre: 'Réparations' });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
