<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Big Bag - Non-conformités';

$rows = $pdo->query('SELECT * FROM nc_big_bag ORDER BY date_nc DESC')->fetchAll();

$totBags = 0; $totTonnage = 0.0;
foreach ($rows as $r) {
    $totBags    += (int) $r['total_big_bag'];
    $totTonnage += (float) $r['tonnage'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="toolbar">
    <h2>Big Bag</h2>
    <a class="btn" href="/non_conforme/big_bag_form.php">+ Ajouter une non-conformité</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>

<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th class="text-right">Total big bags</th>
            <th class="text-right">Tonnage (kg)</th>
            <th class="text-right">Tonnage (T)</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="5">Aucune non-conformité enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($r['date_nc']))) ?></td>
                <td class="text-right"><?= (int) $r['total_big_bag'] ?></td>
                <td class="text-right"><?= number_format((float) $r['tonnage'], 2, ',', ' ') ?></td>
                <td class="text-right"><?= number_format((float) $r['tonnage'] / 1000, 3, ',', ' ') ?></td>
                <td class="actions">
                    <a href="/non_conforme/big_bag_form.php?id=<?= (int) $r['id'] ?>">Modifier</a>
                    <a href="/non_conforme/big_bag_delete.php?id=<?= (int) $r['id'] ?>"
                       onclick="return confirm('Supprimer cette ligne ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL</td>
            <td class="text-right"><?= $totBags ?></td>
            <td class="text-right"><?= number_format($totTonnage, 2, ',', ' ') ?></td>
            <td class="text-right"><?= number_format($totTonnage / 1000, 3, ',', ' ') ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
