<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Désinfectant - Non-conformités';

$rows = $pdo->query('SELECT * FROM nc_desinfectant ORDER BY stock_mag DESC')->fetchAll();

$totPal = 0; $totValeur = 0.0; $totKg = 0.0;
foreach ($rows as $r) {
    $totPal    += (int) $r['nbr_palettes'];
    $totValeur += (float) $r['stock_mag'] * (float) $r['prix_unitaire'];
    $totKg     += (float) $r['stock_mag'] * (float) $r['poids_par_carton_kg'];
}

function fmtDate($d) {
    return $d ? date('d/m/Y', strtotime($d)) : 'aucune date';
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="toolbar">
    <h2>Désinfectant</h2>
    <a class="btn" href="/non_conforme/desinfectant_form.php">+ Ajouter une non-conformité</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>

<div class="table-scroll">
<table class="table">
    <thead>
        <tr>
            <th>Type</th>
            <th>N° article</th>
            <th>Description</th>
            <th class="text-right">Stock MAG</th>
            <th>Expiration</th>
            <th>Code UM</th>
            <th class="text-right">Palettes</th>
            <th class="text-right">Prix unit.</th>
            <th class="text-right">Total</th>
            <th class="text-right">Poids/carton</th>
            <th class="text-right">Poids total (kg)</th>
            <th>Motif</th>
            <th>Responsable</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="14">Aucune non-conformité enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
            $total = (float) $r['stock_mag'] * (float) $r['prix_unitaire'];
            $poids = (float) $r['stock_mag'] * (float) $r['poids_par_carton_kg'];
        ?>
            <tr>
                <td><?= htmlspecialchars($r['type_article'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['numero_article']) ?></td>
                <td><?= htmlspecialchars($r['description_article']) ?></td>
                <td class="text-right"><?= (int) $r['stock_mag'] ?></td>
                <td><?= htmlspecialchars(fmtDate($r['date_expiration'])) ?></td>
                <td><?= htmlspecialchars($r['code_um'] ?? '') ?></td>
                <td class="text-right"><?= (int) $r['nbr_palettes'] ?></td>
                <td class="text-right"><?= number_format((float) $r['prix_unitaire'], 2, ',', ' ') ?></td>
                <td class="text-right"><?= number_format($total, 2, ',', ' ') ?></td>
                <td class="text-right"><?= number_format((float) $r['poids_par_carton_kg'], 3, ',', ' ') ?></td>
                <td class="text-right"><?= number_format($poids, 2, ',', ' ') ?></td>
                <td><?php if ($r['motif']): ?><span class="badge badge-danger"><?= htmlspecialchars($r['motif']) ?></span><?php endif; ?></td>
                <td><?= htmlspecialchars($r['responsable'] ?? '') ?></td>
                <td class="actions">
                    <a href="/non_conforme/desinfectant_form.php?id=<?= (int) $r['id'] ?>">Modifier</a>
                    <a href="/non_conforme/desinfectant_delete.php?id=<?= (int) $r['id'] ?>"
                       onclick="return confirm('Supprimer cette ligne ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">TOTAL</td>
            <td class="text-right"><?= $totPal ?></td>
            <td></td>
            <td class="text-right"><?= number_format($totValeur, 2, ',', ' ') ?></td>
            <td></td>
            <td class="text-right"><?= number_format($totKg, 2, ',', ' ') ?></td>
            <td colspan="3"></td>
        </tr>
    </tfoot>
</table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
