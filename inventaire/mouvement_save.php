<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /inventaire/index.php');
    exit;
}

$produitId = (int) ($_POST['produit_id'] ?? 0);
$type = $_POST['type'] ?? '';
$quantite = (int) ($_POST['quantite'] ?? 0);
$motif = trim($_POST['motif'] ?? '');

if (!in_array($type, ['entree', 'sortie'], true) || $quantite <= 0 || $produitId <= 0) {
    header('Location: /inventaire/mouvement.php?produit_id=' . $produitId . '&error=' . urlencode('Données invalides.'));
    exit;
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('SELECT quantite FROM produits WHERE id = ? FOR UPDATE');
    $stmt->execute([$produitId]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        throw new Exception('Produit introuvable.');
    }

    if ($type === 'sortie' && $quantite > $produit['quantite']) {
        throw new Exception('Quantité en stock insuffisante (' . $produit['quantite'] . ' disponible).');
    }

    $nouvelleQuantite = $type === 'entree'
        ? $produit['quantite'] + $quantite
        : $produit['quantite'] - $quantite;

    $stmt = $pdo->prepare('UPDATE produits SET quantite = ? WHERE id = ?');
    $stmt->execute([$nouvelleQuantite, $produitId]);

    $stmt = $pdo->prepare(
        'INSERT INTO mouvements_stock (produit_id, type, quantite, motif) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$produitId, $type, $quantite, $motif !== '' ? $motif : null]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: /inventaire/mouvement.php?produit_id=' . $produitId . '&error=' . urlencode($e->getMessage()));
    exit;
}

header('Location: /inventaire/index.php?success=' . urlencode('Mouvement de stock enregistré.'));
exit;
