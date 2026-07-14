<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /non_conforme/desinfectant.php');
    exit;
}

$id                  = (int) ($_POST['id'] ?? 0);
$type_article        = trim($_POST['type_article'] ?? '');
$numero_article      = trim($_POST['numero_article'] ?? '');
$description         = trim($_POST['description_article'] ?? '');
$stock_mag           = (int) ($_POST['stock_mag'] ?? 0);
$code_um             = trim($_POST['code_um'] ?? '');
$nbr_palettes        = (int) ($_POST['nbr_palettes'] ?? 0);
$prix_unitaire       = (float) ($_POST['prix_unitaire'] ?? 0);
$poids_par_carton_kg = (float) ($_POST['poids_par_carton_kg'] ?? 0);
$date_expiration     = trim($_POST['date_expiration'] ?? '') ?: null;
$date_blocage        = trim($_POST['date_blocage'] ?? '') ?: null;
$motif               = trim($_POST['motif'] ?? '');
$responsable         = trim($_POST['responsable'] ?? '');
$decision_cq         = trim($_POST['decision_cq'] ?? '');

if ($numero_article === '' || $description === '') {
    header('Location: /non_conforme/desinfectant_form.php?id=' . $id);
    exit;
}

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE nc_desinfectant SET type_article=?, numero_article=?, description_article=?,
                           stock_mag=?, date_expiration=?, code_um=?, nbr_palettes=?, prix_unitaire=?,
                           poids_par_carton_kg=?, date_blocage=?, motif=?, responsable=?, decision_cq=? WHERE id=?');
    $stmt->execute([$type_article, $numero_article, $description, $stock_mag, $date_expiration, $code_um,
                    $nbr_palettes, $prix_unitaire, $poids_par_carton_kg, $date_blocage, $motif, $responsable, $decision_cq, $id]);
    $msg = 'Non-conformité mise à jour.';
} else {
    $stmt = $pdo->prepare('INSERT INTO nc_desinfectant (type_article, numero_article, description_article,
                           stock_mag, date_expiration, code_um, nbr_palettes, prix_unitaire, poids_par_carton_kg,
                           date_blocage, motif, responsable, decision_cq) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$type_article, $numero_article, $description, $stock_mag, $date_expiration, $code_um,
                    $nbr_palettes, $prix_unitaire, $poids_par_carton_kg, $date_blocage, $motif, $responsable, $decision_cq]);
    $msg = 'Non-conformité ajoutée.';
}

header('Location: /non_conforme/desinfectant.php?success=' . urlencode($msg));
exit;
