<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /non_conforme/semi_fini.php');
    exit;
}

$id                = (int) ($_POST['id'] ?? 0);
$numero_article    = trim($_POST['numero_article'] ?? '');
$description       = trim($_POST['description_article'] ?? '');
$quantite          = (int) ($_POST['quantite'] ?? 0);
$nbr_palettes      = (int) ($_POST['nbr_palettes'] ?? 0);
$poids_total_kg    = (float) ($_POST['poids_total_kg'] ?? 0);
$motif             = trim($_POST['motif'] ?? '');
$decision_sq       = trim($_POST['decision_sq'] ?? '');

if ($numero_article === '' || $description === '') {
    header('Location: /non_conforme/semi_fini_form.php?id=' . $id);
    exit;
}

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE nc_semi_fini SET numero_article=?, description_article=?, quantite=?,
                           nbr_palettes=?, poids_total_kg=?, motif=?, decision_sq=? WHERE id=?');
    $stmt->execute([$numero_article, $description, $quantite, $nbr_palettes, $poids_total_kg, $motif, $decision_sq, $id]);
    $msg = 'Non-conformité mise à jour.';
} else {
    $stmt = $pdo->prepare('INSERT INTO nc_semi_fini (numero_article, description_article, quantite,
                           nbr_palettes, poids_total_kg, motif, decision_sq) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$numero_article, $description, $quantite, $nbr_palettes, $poids_total_kg, $motif, $decision_sq]);
    $msg = 'Non-conformité ajoutée.';
}

header('Location: /non_conforme/semi_fini.php?success=' . urlencode($msg));
exit;
