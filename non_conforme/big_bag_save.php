<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /non_conforme/big_bag.php');
    exit;
}

$id            = (int) ($_POST['id'] ?? 0);
$date_nc       = trim($_POST['date_nc'] ?? '');
$total_big_bag = (int) ($_POST['total_big_bag'] ?? 0);
$tonnage       = (float) ($_POST['tonnage'] ?? 0);

if ($date_nc === '') {
    header('Location: /non_conforme/big_bag_form.php?id=' . $id);
    exit;
}

if ($id > 0) {
    $stmt = $pdo->prepare('UPDATE nc_big_bag SET date_nc=?, total_big_bag=?, tonnage=? WHERE id=?');
    $stmt->execute([$date_nc, $total_big_bag, $tonnage, $id]);
    $msg = 'Non-conformité mise à jour.';
} else {
    $stmt = $pdo->prepare('INSERT INTO nc_big_bag (date_nc, total_big_bag, tonnage) VALUES (?, ?, ?)');
    $stmt->execute([$date_nc, $total_big_bag, $tonnage]);
    $msg = 'Non-conformité ajoutée.';
}

header('Location: /non_conforme/big_bag.php?success=' . urlencode($msg));
exit;
