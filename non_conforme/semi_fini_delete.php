<?php
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM nc_semi_fini WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: /non_conforme/semi_fini.php?success=' . urlencode('Non-conformité supprimée.'));
exit;
