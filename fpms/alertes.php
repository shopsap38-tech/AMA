<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Alertes';
$currentPage = 'alertes';

$p = stats_palettes($pdo);
$stockFaible = $p['conforme'] <= SEUIL_PALETTES;

$chariotsHS = $pdo->query(
    "SELECT * FROM chariots WHERE etat IN ('panne','maintenance') ORDER BY etat, id"
)->fetchAll();

$palettesHS = $pdo->query(
    "SELECT * FROM palettes WHERE etat IN ('non_conforme','cassee') ORDER BY etat, id"
)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Alertes</h2>

<div class="card" style="margin-bottom:1.5rem">
    <h3>Stock de palettes</h3>
    <?php if ($stockFaible): ?>
        <p class="alert alert-warning" style="margin:0">
            ⚠️ Stock faible : <strong><?= $p['conforme'] ?></strong> palette(s) conforme(s) disponible(s),
            seuil d'alerte fixé à <strong><?= SEUIL_PALETTES ?></strong>.
        </p>
    <?php else: ?>
        <p class="alert alert-success" style="margin:0">
            ✓ Stock suffisant : <?= $p['conforme'] ?> palettes conformes (seuil : <?= SEUIL_PALETTES ?>).
        </p>
    <?php endif; ?>
</div>

<h3>Chariots indisponibles (<?= count($chariotsHS) ?>)</h3>
<table class="table" style="margin-bottom:1.5rem">
    <thead>
        <tr><th>Réf.</th><th>Type</th><th>Unité</th><th>État</th></tr>
    </thead>
    <tbody>
        <?php if (empty($chariotsHS)): ?>
            <tr><td colspan="4">Tous les chariots sont opérationnels.</td></tr>
        <?php endif; ?>
        <?php foreach ($chariotsHS as $ch): ?>
            <tr class="row-alert">
                <td><strong>#<?= (int) $ch['id'] ?></strong></td>
                <td><?= $ch['type'] === 'electrique' ? 'Électrique' : 'Diesel' ?></td>
                <td><?= htmlspecialchars(unite_label($ch['unite'])) ?></td>
                <td><span class="badge <?= etat_chariot_badge($ch['etat']) ?>"><?= etat_chariot_label($ch['etat']) ?></span></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Palettes à traiter (<?= count($palettesHS) ?>)</h3>
<table class="table">
    <thead>
        <tr><th>Réf.</th><th>État</th><th>Quantité</th></tr>
    </thead>
    <tbody>
        <?php if (empty($palettesHS)): ?>
            <tr><td colspan="3">Aucune palette non conforme ou cassée.</td></tr>
        <?php endif; ?>
        <?php foreach ($palettesHS as $pal): ?>
            <tr class="row-alert">
                <td><strong>#<?= (int) $pal['id'] ?></strong></td>
                <td><span class="badge <?= etat_palette_badge($pal['etat']) ?>"><?= etat_palette_label($pal['etat']) ?></span></td>
                <td><?= (int) $pal['quantite'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
