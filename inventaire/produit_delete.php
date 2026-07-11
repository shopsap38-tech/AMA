<?php
require_once __DIR__ . '/config/database.php';

$id = $_GET['id'] ?? null;

if ($id === null) {
    header('Location: /inventaire/index.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM produits WHERE id = ?');
$stmt->execute([$id]);

header('Location: /inventaire/index.php?success=' . urlencode('Produit supprimé.'));
exit;
