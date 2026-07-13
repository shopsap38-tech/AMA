<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Chariots';
$currentPage = 'chariots';

$filtreType = $_GET['type'] ?? '';
$filtreEtat = $_GET['etat'] ?? '';
$periode    = periode_valide($_GET['periode'] ?? null);

$sql = 'SELECT * FROM chariots';
$where = [];
$params = [];
if (in_array($filtreType, ['electrique', 'diesel'], true)) {
    $where[] = 'type = :type';
    $params[':type'] = $filtreType;
}
if (in_array($filtreEtat, ['disponible', 'maintenance', 'panne'], true)) {
    $where[] = 'etat = :etat';
    $params[':etat'] = $filtreEtat;
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY code ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$chariots = $stmt->fetchAll();

$evo = chariots_evolution($pdo, $periode);

require_once __DIR__ . '/includes/header.php';
?>

<div class="section-head">
    <h2>Gestion des chariots</h2>
    <a class="btn" href="/fpms/chariot_form.php">+ Nouveau chariot</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>

<div class="card" style="margin-bottom:1.5rem">
    <div class="card-head">
        <h3>Chariots mis en service</h3>
        <?= periode_selector($periode) ?>
    </div>
    <canvas id="chEvo"></canvas>
</div>

<form class="filters" method="get">
    <input type="hidden" name="periode" value="<?= htmlspecialchars($periode) ?>">
    <label>Type
        <select name="type" onchange="this.form.submit()">
            <option value="">Tous</option>
            <option value="electrique" <?= $filtreType === 'electrique' ? 'selected' : '' ?>>Électrique</option>
            <option value="diesel" <?= $filtreType === 'diesel' ? 'selected' : '' ?>>Diesel</option>
        </select>
    </label>
    <label>État
        <select name="etat" onchange="this.form.submit()">
            <option value="">Tous</option>
            <option value="disponible" <?= $filtreEtat === 'disponible' ? 'selected' : '' ?>>Disponible</option>
            <option value="maintenance" <?= $filtreEtat === 'maintenance' ? 'selected' : '' ?>>En maintenance</option>
            <option value="panne" <?= $filtreEtat === 'panne' ? 'selected' : '' ?>>En panne</option>
        </select>
    </label>
    <noscript><button class="btn btn-light" type="submit">Filtrer</button></noscript>
</form>

<table class="table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Marque</th>
            <th>Type</th>
            <th>Unité</th>
            <th>État</th>
            <th>Mise en service</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($chariots)): ?>
            <tr><td colspan="7">Aucun chariot enregistré.</td></tr>
        <?php endif; ?>
        <?php foreach ($chariots as $ch): ?>
            <tr class="<?= $ch['etat'] === 'panne' ? 'row-alert' : '' ?>">
                <td><strong><?= htmlspecialchars($ch['code']) ?></strong></td>
                <td><?= htmlspecialchars($ch['marque']) ?></td>
                <td><?= $ch['type'] === 'electrique' ? 'Électrique' : 'Diesel' ?></td>
                <td><?= htmlspecialchars(unite_label($ch['unite'])) ?></td>
                <td><span class="badge <?= etat_chariot_badge($ch['etat']) ?>"><?= etat_chariot_label($ch['etat']) ?></span></td>
                <td><?= $ch['date_mise_service'] ? date('d/m/Y', strtotime($ch['date_mise_service'])) : '—' ?></td>
                <td class="actions">
                    <a href="/fpms/chariot_historique.php?id=<?= (int) $ch['id'] ?>">Historique</a>
                    <a href="/fpms/chariot_form.php?id=<?= (int) $ch['id'] ?>">Modifier</a>
                    <a class="danger" href="/fpms/chariot_delete.php?id=<?= (int) $ch['id'] ?>"
                       onclick="return confirm('Supprimer ce chariot ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="/fpms/assets/charts.js"></script>
<script>
histogramme('chEvo', <?= json_encode(array_keys($evo)) ?>,
    <?= json_encode(array_values($evo)) ?>, '#2563eb', { titre: 'Chariots' });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
