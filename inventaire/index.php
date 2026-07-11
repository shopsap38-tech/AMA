<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Inventaire - Liste des produits';

$stmt = $pdo->query('SELECT * FROM produits ORDER BY nom ASC');
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<h2>Produits en stock</h2>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>

<table class="table">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Description</th>
            <th>Quantité</th>
            <th>Prix unitaire</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($produits)): ?>
            <tr><td colspan="6">Aucun produit enregistré pour le moment.</td></tr>
        <?php endif; ?>
        <?php foreach ($produits as $produit): ?>
            <tr class="<?= $produit['quantite'] <= $produit['seuil_alerte'] ? 'row-alert' : '' ?>">
                <td><?= htmlspecialchars($produit['nom']) ?></td>
                <td><?= htmlspecialchars($produit['description'] ?? '') ?></td>
                <td><?= (int) $produit['quantite'] ?></td>
                <td><?= number_format((float) $produit['prix_unitaire'], 2, ',', ' ') ?> €</td>
                <td>
                    <?php if ($produit['quantite'] <= $produit['seuil_alerte']): ?>
                        <span class="badge badge-danger">Stock bas</span>
                    <?php else: ?>
                        <span class="badge badge-ok">OK</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a href="/inventaire/mouvement.php?produit_id=<?= (int) $produit['id'] ?>">Mouvement</a>
                    <a href="/inventaire/produit_form.php?id=<?= (int) $produit['id'] ?>">Modifier</a>
                    <a href="/inventaire/produit_delete.php?id=<?= (int) $produit['id'] ?>"
                       onclick="return confirm('Supprimer ce produit ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
