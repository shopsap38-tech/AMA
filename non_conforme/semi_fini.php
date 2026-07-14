<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'PF Semi-fini-matic - Non-conformités';

$rows = $pdo->query('SELECT * FROM nc_semi_fini ORDER BY nbr_palettes DESC, description_article ASC')->fetchAll();

$totQte = 0; $totPal = 0; $totKg = 0.0;
foreach ($rows as $r) {
    $totQte += (int) $r['quantite'];
    $totPal += (int) $r['nbr_palettes'];
    $totKg  += (float) $r['poids_total_kg'];
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="toolbar">
    <h2>PF Semi-fini-matic</h2>
    <a class="btn" href="/non_conforme/semi_fini_form.php">+ Ajouter une non-conformité</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>

<div class="table-scroll">
<table class="table">
    <thead>
        <tr>
            <th>N° article</th>
            <th>Description article</th>
            <th class="text-right">Quantité</th>
            <th class="text-right">Nbr palettes</th>
            <th class="text-right">Poids total (kg)</th>
            <th class="text-right">Poids total (T)</th>
            <th>Motif</th>
            <th>Décision SQ</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="9">Aucune non-conformité enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['numero_article']) ?></td>
                <td><?= htmlspecialchars($r['description_article']) ?></td>
                <td class="text-right"><?= (int) $r['quantite'] ?></td>
                <td class="text-right"><?= (int) $r['nbr_palettes'] ?></td>
                <td class="text-right"><?= number_format((float) $r['poids_total_kg'], 2, ',', ' ') ?></td>
                <td class="text-right"><?= number_format((float) $r['poids_total_kg'] / 1000, 3, ',', ' ') ?></td>
                <td><?php if ($r['motif']): ?><span class="badge badge-warn"><?= htmlspecialchars($r['motif']) ?></span><?php endif; ?></td>
                <td><?= htmlspecialchars($r['decision_sq'] ?? '') ?></td>
                <td class="actions">
                    <a href="/non_conforme/semi_fini_form.php?id=<?= (int) $r['id'] ?>">Modifier</a>
                    <a href="/non_conforme/semi_fini_delete.php?id=<?= (int) $r['id'] ?>"
                       onclick="return confirm('Supprimer cette ligne ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">TOTAL</td>
            <td class="text-right"><?= $totQte ?></td>
            <td class="text-right"><?= $totPal ?></td>
            <td class="text-right"><?= number_format($totKg, 2, ',', ' ') ?></td>
            <td class="text-right"><?= number_format($totKg / 1000, 3, ',', ' ') ?></td>
            <td colspan="3"></td>
        </tr>
    </tfoot>
</table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
