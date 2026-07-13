<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /fpms/palettes.php');
    exit;
}

$id          = (int) ($_POST['id'] ?? 0);
$code        = trim($_POST['code'] ?? '');
$etat        = $_POST['etat'] ?? '';
$commentaire = trim($_POST['commentaire'] ?? '');
$commentaire = $commentaire !== '' ? $commentaire : null;
$quantite = max(0, (int) ($_POST['quantite'] ?? 1));
$date = $_POST['date'] ?? '';
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');

if ($code === '' || !in_array($etat, ['conforme', 'non_conforme', 'cassee'], true)) {
    $back = $id ? "?id=$id" : '';
    header('Location: /fpms/palette_form.php' . $back . '&error=' . urlencode('Veuillez remplir les champs obligatoires.'));
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE palettes SET code = ?, etat = ?, quantite = ?, commentaire = ?, created_at = ? WHERE id = ?');
        $stmt->execute([$code, $etat, $quantite, $commentaire, $date, $id]);
        $msg = 'Palette mise à jour.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO palettes (code, etat, quantite, commentaire, created_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$code, $etat, $quantite, $commentaire, $date]);
        $msg = 'Palette ajoutée.';
    }
} catch (PDOException $e) {
    $back = $id ? "?id=$id" : '';
    $err = str_contains($e->getMessage(), 'Duplicate')
        ? 'Ce code palette existe déjà.'
        : 'Erreur : ' . $e->getMessage();
    header('Location: /fpms/palette_form.php' . $back . '&error=' . urlencode($err));
    exit;
}

header('Location: /fpms/palettes.php?success=' . urlencode($msg));
exit;
