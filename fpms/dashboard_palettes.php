<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Dashboard Palettes';
$currentPage = 'dash_palettes';

$periode = periode_valide($_GET['periode'] ?? null);
$p       = stats_palettes($pdo);
$evo     = palettes_evolution($pdo, $periode);
$tauxConformite = pct($p['conforme'], $p['total']);

$palettes = $pdo->query('SELECT * FROM palettes ORDER BY code')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Dashboard — Palettes</h2>

<?php if ($p['conforme'] <= SEUIL_PALETTES): ?>
    <p class="alert alert-warning">⚠️ Stock de palettes conformes faible : <?= $p['conforme'] ?> (seuil : <?= SEUIL_PALETTES ?>).</p>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Quantité totale</div>
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
    <div class="kpi-card">
        <div class="kpi-label">Nombre de lots</div>
        <div class="kpi-value"><?= $p['lots'] ?></div>
    </div>
</div>

<div class="charts-grid">
    <div class="card"><h3>Quantité par état</h3><canvas id="chEtat"></canvas></div>
    <div class="card">
        <div class="card-head">
            <h3>Palettes créées (quantité)</h3>
            <?= periode_selector($periode) ?>
        </div>
        <canvas id="chEvo"></canvas>
    </div>
</div>

<h3>Détail des palettes</h3>
<table class="table">
    <thead>
        <tr><th>Code</th><th>État</th><th>Quantité</th><th>Commentaire</th></tr>
    </thead>
    <tbody>
        <?php foreach ($palettes as $pal): ?>
            <tr class="<?= $pal['etat'] === 'cassee' ? 'row-alert' : '' ?>">
                <td><strong><?= htmlspecialchars($pal['code']) ?></strong></td>
                <td><span class="badge <?= etat_palette_badge($pal['etat']) ?>"><?= etat_palette_label($pal['etat']) ?></span></td>
                <td><?= (int) $pal['quantite'] ?></td>
                <td><?= htmlspecialchars($pal['commentaire'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/fpms/assets/charts.js"></script>
<script>
const COL = { green:'#16a34a', orange:'#f59e0b', red:'#dc2626', blue:'#2563eb' };

histogramme('chEtat', ['Conforme','Non conforme','Cassée'],
    [<?= $p['conforme'] ?>,<?= $p['non_conforme'] ?>,<?= $p['cassee'] ?>],
    [COL.green, COL.orange, COL.red], { titre: 'Quantité' });

histogramme('chEvo', <?= json_encode(array_keys($evo)) ?>,
    <?= json_encode(array_values($evo)) ?>, COL.blue, { titre: 'Quantité' });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
