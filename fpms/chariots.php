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
$sql .= ' ORDER BY id ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$chariots = $stmt->fetchAll();

$c        = stats_chariots($pdo);
$arret    = temps_arret_par_chariot($pdo);
$evo      = chariots_evolution($pdo, $periode);
$unites   = unites();
$parUnite = chariots_par_unite($pdo);

require_once __DIR__ . '/includes/header.php';
?>

<div class="section-head">
    <h2>Gestion des chariots</h2>
    <a class="btn" href="/fpms/chariot_form.php">+ Nouveau chariot</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">Total chariots</div>
        <div class="kpi-value"><?= $c['total'] ?></div>
    </div>
    <div class="kpi-card green">
        <div class="kpi-label">Opérationnels</div>
        <div class="kpi-value"><?= $c['disponible'] ?></div>
    </div>
    <div class="kpi-card red">
        <div class="kpi-label">En panne</div>
        <div class="kpi-value"><?= $c['panne'] ?></div>
    </div>
    <div class="kpi-card orange">
        <div class="kpi-label">Réparation</div>
        <div class="kpi-value"><?= $c['maintenance'] ?></div>
    </div>
    <div class="kpi-card purple">
        <div class="kpi-label">Électriques / Diesel</div>
        <div class="kpi-value"><?= $c['electrique'] ?> / <?= $c['diesel'] ?></div>
    </div>
</div>

<div class="charts-grid">
    <div class="card"><h3>Nombre de chariots par état</h3><canvas id="chEtat"></canvas></div>
    <div class="card"><h3>Nombre de chariots par type</h3><canvas id="chType"></canvas></div>
    <div class="card"><h3>Temps d'arrêt par chariot (h)</h3><canvas id="chArret"></canvas></div>
    <div class="card">
        <div class="card-head">
            <h3>Chariots mis en service</h3>
            <?= periode_selector($periode) ?>
        </div>
        <canvas id="chEvo"></canvas>
    </div>
</div>

<h3>Répartition des chariots par unité</h3>
<div class="card card-chart">
    <h3>Nombre de chariots par unité</h3>
    <canvas id="chUnite"></canvas>
</div>

<table class="table" style="margin-bottom:2rem">
    <thead>
        <tr><th>Unité</th><th>Nombre de chariots</th><th>Pourcentage</th></tr>
    </thead>
    <tbody>
        <?php foreach ($unites as $key => $label): $n = $parUnite['data'][$key]; ?>
            <tr>
                <td><strong><?= htmlspecialchars($label) ?></strong></td>
                <td><?= $n ?></td>
                <td><?= pct($n, $parUnite['total']) ?> %</td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td><strong>Total (affectés)</strong></td>
            <td><strong><?= $parUnite['total'] ?></strong></td>
            <td>100 %</td>
        </tr>
    </tbody>
</table>

<h3>Liste des chariots</h3>
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
            <option value="disponible" <?= $filtreEtat === 'disponible' ? 'selected' : '' ?>>Opérationnel</option>
            <option value="maintenance" <?= $filtreEtat === 'maintenance' ? 'selected' : '' ?>>Réparation</option>
            <option value="panne" <?= $filtreEtat === 'panne' ? 'selected' : '' ?>>En panne</option>
        </select>
    </label>
    <noscript><button class="btn btn-light" type="submit">Filtrer</button></noscript>
</form>

<table class="table">
    <thead>
        <tr>
            <th>Réf.</th>
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
                <td><strong>#<?= (int) $ch['id'] ?></strong></td>
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
const COL = { green:'#16a34a', orange:'#f59e0b', red:'#dc2626', blue:'#2563eb', purple:'#7c3aed' };

histogramme('chEtat', ['Opérationnel','Réparation','En panne'],
    [<?= $c['disponible'] ?>,<?= $c['maintenance'] ?>,<?= $c['panne'] ?>],
    [COL.green, COL.orange, COL.red], { titre: 'Chariots' });

histogramme('chType', ['Électrique','Diesel'],
    [<?= $c['electrique'] ?>,<?= $c['diesel'] ?>], [COL.purple, COL.orange], { titre: 'Chariots' });

histogramme('chArret', <?= json_encode(array_column($arret, 'ref')) ?>,
    <?= json_encode(array_column($arret, 'heures')) ?>, COL.red, { titre: 'Heures' });

histogramme('chEvo', <?= json_encode(array_keys($evo)) ?>,
    <?= json_encode(array_values($evo)) ?>, COL.blue, { titre: 'Chariots' });

const UNITE_COL = ['#2563eb','#16a34a','#f59e0b','#dc2626','#7c3aed','#0891b2','#db2777','#65a30d'];
histogramme('chUnite', <?= json_encode(array_values($unites)) ?>,
    <?= json_encode(array_values($parUnite['data'])) ?>, UNITE_COL,
    { titre: 'Chariots', aspectRatio: 3.4 });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
