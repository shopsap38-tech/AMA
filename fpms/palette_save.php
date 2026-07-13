<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /fpms/palettes.php');
    exit;
}

$id       = (int) ($_POST['id'] ?? 0);
$etat     = $_POST['etat'] ?? '';
$quantite = max(0, (int) ($_POST['quantite'] ?? 1));
$date = $_POST['date'] ?? '';
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');

if (!in_array($etat, ['conforme', 'non_conforme', 'cassee'], true)) {
    $back = $id ? "?id=$id" : '';
    header('Location: /fpms/palette_form.php' . $back . '&error=' . urlencode('Veuillez remplir les champs obligatoires.'));
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE palettes SET etat = ?, quantite = ?, created_at = ? WHERE id = ?');
        $stmt->execute([$etat, $quantite, $date, $id]);
        $msg = 'Palette mise à jour.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO palettes (etat, quantite, created_at) VALUES (?, ?, ?)');
        $stmt->execute([$etat, $quantite, $date]);
        $msg = 'Palette ajoutée.';
    }
} catch (PDOException $e) {
    $back = $id ? "?id=$id" : '';
    header('Location: /fpms/palette_form.php' . $back . '&error=' . urlencode('Erreur : ' . $e->getMessage()));
    exit;
}

header('Location: /fpms/palettes.php?success=' . urlencode($msg));
exit;
