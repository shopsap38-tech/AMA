<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Rapports';
$currentPage = 'rapports';

$type = $_GET['type'] ?? 'journalier';
if (!in_array($type, ['journalier', 'mensuel', 'annuel'], true)) {
    $type = 'journalier';
}

$defaults = ['journalier' => date('Y-m-d'), 'mensuel' => date('Y-m'), 'annuel' => date('Y')];
$ref = $_GET['ref'] ?? $defaults[$type];

$data = rapport_data($pdo, $type, $ref);
$qs = http_build_query(['type' => $type, 'ref' => $ref]);

require_once __DIR__ . '/includes/header.php';
?>

<h2>Rapports</h2>

<form class="filters toolbar no-print" method="get">
    <label>Type
        <select name="type" onchange="ajusterRef(this.value)">
            <option value="journalier" <?= $type === 'journalier' ? 'selected' : '' ?>>Journalier</option>
            <option value="mensuel" <?= $type === 'mensuel' ? 'selected' : '' ?>>Mensuel</option>
            <option value="annuel" <?= $type === 'annuel' ? 'selected' : '' ?>>Annuel</option>
        </select>
    </label>
    <label>Période
        <?php if ($type === 'journalier'): ?>
            <input type="date" name="ref" value="<?= htmlspecialchars($ref) ?>">
        <?php elseif ($type === 'mensuel'): ?>
            <input type="month" name="ref" value="<?= htmlspecialchars($ref) ?>">
        <?php else: ?>
            <input type="number" name="ref" min="2000" max="2100" value="<?= htmlspecialchars($ref) ?>">
        <?php endif; ?>
    </label>
    <button class="btn" type="submit">Générer</button>
    <a class="btn btn-light" href="/fpms/export_excel.php?<?= htmlspecialchars($qs) ?>">Export Excel</a>
    <a class="btn btn-light" href="/fpms/export_pdf.php?<?= htmlspecialchars($qs) ?>" target="_blank">Export PDF</a>
</form>

<div class="card">
    <h3><?= htmlspecialchars($data['libelle']) ?></h3>

    <div class="kpi-grid">
        <div class="kpi-card orange">
            <div class="kpi-label">Changements d'état chariots</div>
            <div class="kpi-value"><?= $data['changements_etat'] ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Nouvelles palettes</div>
            <div class="kpi-value"><?= $data['nouvelles_palettes'] ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Nouveaux chariots</div>
            <div class="kpi-value"><?= $data['nouveaux_chariots'] ?></div>
        </div>
    </div>

    <h4>Situation actuelle</h4>
    <table class="table">
        <tbody>
            <tr><th>Total chariots</th><td><?= $data['chariots']['total'] ?></td></tr>
            <tr><th>Disponibles</th><td><?= $data['chariots']['disponible'] ?></td></tr>
            <tr><th>En maintenance</th><td><?= $data['chariots']['maintenance'] ?></td></tr>
            <tr><th>En panne</th><td><?= $data['chariots']['panne'] ?></td></tr>
            <tr><th>Taux de disponibilité</th><td><?= $data['chariots']['taux_dispo'] ?> %</td></tr>
            <tr><th>Palettes conformes</th><td><?= $data['palettes']['conforme'] ?></td></tr>
            <tr><th>Palettes non conformes</th><td><?= $data['palettes']['non_conforme'] ?></td></tr>
            <tr><th>Palettes cassées</th><td><?= $data['palettes']['cassee'] ?></td></tr>
        </tbody>
    </table>
</div>

<script>
function ajusterRef(type) {
    // Recharge la page avec le type choisi pour afficher le bon sélecteur de période.
    const url = new URL(window.location.href);
    url.searchParams.set('type', type);
    url.searchParams.delete('ref');
    window.location.href = url.toString();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
