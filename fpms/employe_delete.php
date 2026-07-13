<?php
require_once __DIR__ . '/config/database.php';

// La suppression met à NULL les affectations (chariots / palettes / réparations)
// grâce aux clés étrangères ON DELETE SET NULL.
$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM employes WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: /fpms/employes.php?success=' . urlencode('Employé supprimé.'));
exit;
