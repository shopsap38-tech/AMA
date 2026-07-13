<?php
require_once __DIR__ . '/config/database.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare('DELETE FROM chariots WHERE id = ?');
    $stmt->execute([$id]);
}

header('Location: /fpms/chariots.php?success=' . urlencode('Chariot supprimé.'));
exit;
