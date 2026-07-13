<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Palettes';
$currentPage = 'palettes';

$filtreEtat = $_GET['etat'] ?? '';
$periode    = periode_valide($_GET['periode'] ?? null);

$sql = 'SELECT * FROM palettes';
$params = [];
if (in_array($filtreEtat, ['conforme', 'non_conforme', 'cassee'], true)) {
    $sql .= ' WHERE etat = :etat';
    $params[':etat'] = $filtreEtat;
}
$sql .= ' ORDER BY id ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$palettes = $stmt->fetchAll();

$p   = stats_palettes($pdo);
$evo = palettes_etat_evolution($pdo, $periode);

require_once __DIR__ . '/includes/header.php';
?>

<div class="section-head">
    <h2>Gestion des palettes</h2>
    <a class="btn" href="/fpms/palette_form.php">+ Nouvelle palette</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card green">
        <div class="kpi-label">Conformes (qté)</div>
        <div class="kpi-value"><?= $p['conforme'] ?></div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-label">Non conformes (qté)</div>
        <div class="kpi-value"><?= $p['non_conforme'] ?></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">Cassées (qté)</div>
        <div class="kpi-value"><?= $p['cassee'] ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">Quantité totale</div>
        <div class="kpi-value"><?= $p['total'] ?></div>
    </div>
</div>

<div class="card card-chart">
    <div class="card-head">
        <h3>Nombre de palettes par état</h3>
        <?= periode_selector($periode) ?>
    </div>
    <canvas id="chEtat"></canvas>
</div>

<form class="filters" method="get">
    <input type="hidden" name="periode" value="<?= htmlspecialchars($periode) ?>">
    <label>État
        <select name="etat" onchange="this.form.submit()">
            <option value="">Tous</option>
            <option value="conforme" <?= $filtreEtat === 'conforme' ? 'selected' : '' ?>>Conforme</option>
            <option value="non_conforme" <?= $filtreEtat === 'non_conforme' ? 'selected' : '' ?>>Non conforme</option>
            <option value="cassee" <?= $filtreEtat === 'cassee' ? 'selected' : '' ?>>Cassée</option>
        </select>
    </label>
    <noscript><button class="btn btn-light" type="submit">Filtrer</button></noscript>
</form>

<table class="table">
    <thead>
        <tr>
            <th>Réf.</th>
            <th>État</th>
            <th>Quantité</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($palettes)): ?>
            <tr><td colspan="5">Aucune palette enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($palettes as $pal): ?>
            <tr class="<?= $pal['etat'] === 'cassee' ? 'row-alert' : '' ?>">
                <td><strong>#<?= (int) $pal['id'] ?></strong></td>
                <td><span class="badge <?= etat_palette_badge($pal['etat']) ?>"><?= etat_palette_label($pal['etat']) ?></span></td>
                <td><?= (int) $pal['quantite'] ?></td>
                <td><?= !empty($pal['created_at']) ? date('d/m/Y', strtotime($pal['created_at'])) : '—' ?></td>
                <td class="actions">
                    <a href="/fpms/palette_form.php?id=<?= (int) $pal['id'] ?>">Modifier</a>
                    <a class="danger" href="/fpms/palette_delete.php?id=<?= (int) $pal['id'] ?>"
                       onclick="return confirm('Supprimer cette palette ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/fpms/assets/charts.js"></script>
<script>
histogrammeEmpile('chEtat', <?= json_encode($evo['labels']) ?>, [
    { label: 'Conforme',     data: <?= json_encode($evo['conforme']) ?>,     color: '#16a34a' },
    { label: 'Non conforme', data: <?= json_encode($evo['non_conforme']) ?>, color: '#f59e0b' },
    { label: 'Cassée',       data: <?= json_encode($evo['cassee']) ?>,       color: '#dc2626' }
], { aspectRatio: 3.0 });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
