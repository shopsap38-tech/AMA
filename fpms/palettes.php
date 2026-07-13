<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Palettes';
$currentPage = 'palettes';

$filtreEtat = $_GET['etat'] ?? '';

$sql = 'SELECT * FROM palettes';
$params = [];
if (in_array($filtreEtat, ['conforme', 'non_conforme', 'cassee'], true)) {
    $sql .= ' WHERE etat = :etat';
    $params[':etat'] = $filtreEtat;
}
$sql .= ' ORDER BY code ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$palettes = $stmt->fetchAll();

$p = stats_palettes($pdo);

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
    <div class="kpi-card blue">
        <div class="kpi-label">Total réparations</div>
        <div class="kpi-value"><?= $p['reparations'] ?></div>
    </div>
</div>

<form class="filters" method="get">
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
            <th>Code</th>
            <th>État</th>
            <th>Quantité</th>
            <th>Réparations</th>
            <th>Commentaire</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($palettes)): ?>
            <tr><td colspan="6">Aucune palette enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($palettes as $pal): ?>
            <tr class="<?= $pal['etat'] === 'cassee' ? 'row-alert' : '' ?>">
                <td><strong><?= htmlspecialchars($pal['code']) ?></strong></td>
                <td><span class="badge <?= etat_palette_badge($pal['etat']) ?>"><?= etat_palette_label($pal['etat']) ?></span></td>
                <td><?= (int) $pal['quantite'] ?></td>
                <td><?= (int) $pal['nb_reparations'] ?></td>
                <td><?= htmlspecialchars($pal['commentaire'] ?? '') ?></td>
                <td class="actions">
                    <?php if ($pal['etat'] !== 'conforme'): ?>
                        <a href="/fpms/reparations.php?palette_id=<?= (int) $pal['id'] ?>">Réparer</a>
                    <?php endif; ?>
                    <a href="/fpms/palette_form.php?id=<?= (int) $pal['id'] ?>">Modifier</a>
                    <a class="danger" href="/fpms/palette_delete.php?id=<?= (int) $pal['id'] ?>"
                       onclick="return confirm('Supprimer cette palette ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
