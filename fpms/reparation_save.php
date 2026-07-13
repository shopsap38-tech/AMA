<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /fpms/reparations.php');
    exit;
}

$paletteId   = (int) ($_POST['palette_id'] ?? 0);
$date        = $_POST['date_reparation'] ?? '';
$resultat    = $_POST['resultat'] ?? '';
$description = trim($_POST['description'] ?? '');
$description = $description !== '' ? $description : null;
$employeId   = (int) ($_POST['employe_id'] ?? 0);
$employeId   = $employeId > 0 ? $employeId : null;

if ($paletteId <= 0 || $date === '' || !in_array($resultat, ['reparee', 'irreparable'], true)) {
    header('Location: /fpms/reparations.php?error=' . urlencode('Données de réparation invalides.'));
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO reparations (palette_id, employe_id, description, resultat, date_reparation)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$paletteId, $employeId, $description, $resultat, $date]);

    // Mettre à jour l'état de la palette selon le résultat.
    $nouvelEtat = $resultat === 'reparee' ? 'conforme' : 'cassee';
    $upd = $pdo->prepare('UPDATE palettes SET etat = ? WHERE id = ?');
    $upd->execute([$nouvelEtat, $paletteId]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    header('Location: /fpms/reparations.php?error=' . urlencode('Erreur : ' . $e->getMessage()));
    exit;
}

header('Location: /fpms/reparations.php?success=' . urlencode('Réparation enregistrée.'));
exit;
