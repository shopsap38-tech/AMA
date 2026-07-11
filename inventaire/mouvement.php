<?php
require_once __DIR__ . '/config/database.php';

$produitId = $_GET['produit_id'] ?? null;

if (!$produitId) {
    header('Location: /inventaire/index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM produits WHERE id = ?');
$stmt->execute([$produitId]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produit) {
    die('Produit introuvable.');
}

$pageTitle = 'Mouvement de stock - ' . $produit['nom'];

require_once __DIR__ . '/includes/header.php';
?>

<h2>Mouvement de stock : <?= htmlspecialchars($produit['nom']) ?></h2>
<p>Quantité actuelle : <strong><?= (int) $produit['quantite'] ?></strong></p>

<?php if (isset($_GET['error'])): ?>
    <p class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></p>
<?php endif; ?>

<form action="/inventaire/mouvement_save.php" method="post" class="form">
    <input type="hidden" name="produit_id" value="<?= (int) $produit['id'] ?>">

    <label for="type">Type de mouvement</label>
    <select id="type" name="type" required>
        <option value="entree">Entrée (réception de stock)</option>
        <option value="sortie">Sortie (vente / utilisation)</option>
    </select>

    <label for="quantite">Quantité</label>
    <input type="number" id="quantite" name="quantite" min="1" required>

    <label for="motif">Motif (optionnel)</label>
    <input type="text" id="motif" name="motif" placeholder="Ex : Livraison fournisseur, Vente client...">

    <button type="submit">Enregistrer le mouvement</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
