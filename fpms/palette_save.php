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
$controleurId = (int) ($_POST['controleur_id'] ?? 0);
$controleurId = $controleurId > 0 ? $controleurId : null;

if ($code === '' || !in_array($etat, ['conforme', 'non_conforme', 'cassee'], true)) {
    $back = $id ? "?id=$id" : '';
    header('Location: /fpms/palette_form.php' . $back . '&error=' . urlencode('Veuillez remplir les champs obligatoires.'));
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE palettes SET code = ?, etat = ?, controleur_id = ?, commentaire = ? WHERE id = ?');
        $stmt->execute([$code, $etat, $controleurId, $commentaire, $id]);
        $msg = 'Palette mise à jour.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO palettes (code, etat, controleur_id, commentaire) VALUES (?, ?, ?, ?)');
        $stmt->execute([$code, $etat, $controleurId, $commentaire]);
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
