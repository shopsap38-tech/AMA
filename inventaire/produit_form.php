<?php
require_once __DIR__ . '/config/database.php';

$produit = [
    'id' => '',
    'nom' => '',
    'description' => '',
    'quantite' => 0,
    'seuil_alerte' => 5,
    'prix_unitaire' => 0,
];

$isEdit = false;

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM produits WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($found) {
        $produit = $found;
        $isEdit = true;
    }
}

$pageTitle = $isEdit ? 'Modifier le produit' : 'Ajouter un produit';

require_once __DIR__ . '/includes/header.php';
?>

<h2><?= $isEdit ? 'Modifier le produit' : 'Ajouter un produit' ?></h2>

<form action="/inventaire/produit_save.php" method="post" class="form">
    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $produit['id']) ?>">

    <label for="nom">Nom du produit</label>
    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($produit['nom']) ?>" required>

    <label for="description">Description</label>
    <textarea id="description" name="description"><?= htmlspecialchars($produit['description'] ?? '') ?></textarea>

    <label for="quantite">Quantité initiale</label>
    <input type="number" id="quantite" name="quantite" value="<?= (int) $produit['quantite'] ?>" min="0" required <?= $isEdit ? 'readonly' : '' ?>>
    <?php if ($isEdit): ?>
        <p class="hint">Pour modifier la quantité, utilisez un mouvement de stock (entrée/sortie).</p>
    <?php endif; ?>

    <label for="seuil_alerte">Seuil d'alerte</label>
    <input type="number" id="seuil_alerte" name="seuil_alerte" value="<?= (int) $produit['seuil_alerte'] ?>" min="0" required>

    <label for="prix_unitaire">Prix unitaire (€)</label>
    <input type="number" id="prix_unitaire" name="prix_unitaire" value="<?= htmlspecialchars((string) $produit['prix_unitaire']) ?>" step="0.01" min="0" required>

    <button type="submit"><?= $isEdit ? 'Enregistrer les modifications' : 'Créer le produit' ?></button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
