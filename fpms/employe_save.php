<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /fpms/employes.php');
    exit;
}

$id        = (int) ($_POST['id'] ?? 0);
$matricule = trim($_POST['matricule'] ?? '');
$nom       = trim($_POST['nom'] ?? '');
$prenom    = trim($_POST['prenom'] ?? '');
$type      = $_POST['type'] ?? '';
$poste     = trim($_POST['poste'] ?? '');
$poste     = $poste !== '' ? $poste : null;
$actif     = (int) ($_POST['actif'] ?? 1) === 1 ? 1 : 0;

if ($matricule === '' || $nom === '' || $prenom === '' || !in_array($type, ['permanent', 'journalier'], true)) {
    $back = $id ? "?id=$id" : '';
    header('Location: /fpms/employe_form.php' . $back . '&error=' . urlencode('Veuillez remplir les champs obligatoires.'));
    exit;
}

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE employes SET matricule=?, nom=?, prenom=?, type=?, poste=?, actif=? WHERE id=?');
        $stmt->execute([$matricule, $nom, $prenom, $type, $poste, $actif, $id]);
        $msg = 'Employé mis à jour.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO employes (matricule, nom, prenom, type, poste, actif) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$matricule, $nom, $prenom, $type, $poste, $actif]);
        $msg = 'Employé ajouté.';
    }
} catch (PDOException $e) {
    $back = $id ? "?id=$id" : '';
    $err = str_contains($e->getMessage(), 'Duplicate') ? 'Ce matricule existe déjà.' : 'Erreur : ' . $e->getMessage();
    header('Location: /fpms/employe_form.php' . $back . '&error=' . urlencode($err));
    exit;
}

header('Location: /fpms/employes.php?success=' . urlencode($msg));
exit;
