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

/** Classe de badge selon le type d'article. */
function typeBadge(?string $type): string {
    $t = mb_strtoupper(trim((string) $type));
    if (strpos($t, 'ALCOOL') !== false)   return 'badge-type-alcool';
    if (strpos($t, 'SOLUTION') !== false) return 'badge-type-solution';
    if (strpos($t, 'GEL') !== false)      return 'badge-type-gel';
    return 'badge-type-autre';
}

require_once __DIR__ . '/includes/header.php';
$today = date('Y-m-d');
?>

<div class="toolbar">
    <h2>Désinfectant</h2>
    <a class="btn" href="/non_conforme/desinfectant_form.php">+ Ajouter une non-conformité</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>

<div class="table-scroll">
<table class="table table-clean">
    <thead>
        <tr>
            <th>Type</th>
            <th>Article</th>
            <th class="text-right">Stock</th>
            <th>Expiration</th>
            <th>Code UM</th>
            <th class="text-right">Pal.</th>
            <th class="text-right">Prix unit.</th>
            <th class="text-right">Total</th>
            <th class="text-right">Poids/carton</th>
            <th class="text-right">Poids total (kg)</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="12">Aucune non-conformité enregistrée.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
            $total = (float) $r['stock_mag'] * (float) $r['prix_unitaire'];
            $poids = (float) $r['stock_mag'] * (float) $r['poids_par_carton_kg'];
            $exp   = $r['date_expiration'];
            $isExpired = $exp && $exp < $today;
        ?>
            <tr>
                <td><span class="badge <?= typeBadge($r['type_article']) ?>"><?= htmlspecialchars($r['type_article'] ?? '—') ?></span></td>
                <td class="cell-article">
                    <span class="art-num"><?= htmlspecialchars($r['numero_article']) ?></span>
                    <span class="art-desc"><?= htmlspecialchars($r['description_article']) ?></span>
                </td>
                <td class="text-right num"><?= number_format((int) $r['stock_mag'], 0, ',', ' ') ?></td>
                <td class="nowrap">
                    <?php if (!$exp): ?>
                        <span class="muted">aucune date</span>
                    <?php elseif ($isExpired): ?>
                        <span class="date-expired"><?= htmlspecialchars(date('d/m/Y', strtotime($exp))) ?></span>
                    <?php else: ?>
                        <?= htmlspecialchars(date('d/m/Y', strtotime($exp))) ?>
                    <?php endif; ?>
                </td>
                <td class="nowrap"><?= htmlspecialchars($r['code_um'] ?? '') ?></td>
                <td class="text-right num"><?= (int) $r['nbr_palettes'] ?></td>
                <td class="text-right num"><?= number_format((float) $r['prix_unitaire'], 2, ',', ' ') ?></td>
                <td class="text-right num"><?= number_format($total, 2, ',', ' ') ?></td>
                <td class="text-right num"><?= number_format((float) $r['poids_par_carton_kg'], 3, ',', ' ') ?></td>
                <td class="text-right num"><?= number_format($poids, 2, ',', ' ') ?></td>
                <td class="nowrap">
                    <?php if ($r['motif']): ?><span class="badge badge-danger"><?= htmlspecialchars($r['motif']) ?></span><?php endif; ?>
                    <?php if ($r['responsable']): ?><span class="cell-sub"><?= htmlspecialchars($r['responsable']) ?></span><?php endif; ?>
                </td>
                <td class="actions nowrap">
                    <a href="/non_conforme/desinfectant_form.php?id=<?= (int) $r['id'] ?>">Modifier</a>
                    <a href="/non_conforme/desinfectant_delete.php?id=<?= (int) $r['id'] ?>"
                       onclick="return confirm('Supprimer cette ligne ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">TOTAL</td>
            <td class="text-right num"><?= $totPal ?></td>
            <td></td>
            <td class="text-right num"><?= number_format($totValeur, 2, ',', ' ') ?></td>
            <td></td>
            <td class="text-right num"><?= number_format($totKg, 2, ',', ' ') ?></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
