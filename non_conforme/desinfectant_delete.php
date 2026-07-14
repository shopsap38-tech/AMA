<?php
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM nc_desinfectant WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: /non_conforme/desinfectant.php?success=' . urlencode('Non-conformité supprimée.'));
exit;
