<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /inventaire/index.php');
    exit;
}

$id = $_POST['id'] ?? '';
$nom = trim($_POST['nom'] ?? '');
$description = trim($_POST['description'] ?? '');
$seuilAlerte = (int) ($_POST['seuil_alerte'] ?? 0);
$prixUnitaire = (float) ($_POST['prix_unitaire'] ?? 0);

if ($nom === '') {
    die('Le nom du produit est obligatoire.');
}

if ($id !== '') {
    // Modification : la quantité ne se change pas ici, uniquement via un mouvement de stock.
    $stmt = $pdo->prepare(
        'UPDATE produits SET nom = ?, description = ?, seuil_alerte = ?, prix_unitaire = ? WHERE id = ?'
    );
    $stmt->execute([$nom, $description, $seuilAlerte, $prixUnitaire, $id]);
    $message = 'Produit modifié avec succès.';
} else {
    $quantiteInitiale = (int) ($_POST['quantite'] ?? 0);

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO produits (nom, description, quantite, seuil_alerte, prix_unitaire) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nom, $description, $quantiteInitiale, $seuilAlerte, $prixUnitaire]);
        $produitId = $pdo->lastInsertId();

        if ($quantiteInitiale > 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO mouvements_stock (produit_id, type, quantite, motif) VALUES (?, "entree", ?, ?)'
            );
            $stmt->execute([$produitId, $quantiteInitiale, 'Stock initial']);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die('Erreur lors de la création du produit : ' . $e->getMessage());
    }

    $message = 'Produit créé avec succès.';
}

header('Location: /inventaire/index.php?success=' . urlencode($message));
exit;
