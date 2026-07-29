<?php
require __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('nonconformites.php'); }
csrf_check();
require_permission('nonconformity.delete');

$id = (int) ($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM non_conformities WHERE id = ?');
$stmt->execute([$id]);
$old = $stmt->fetch();

if ($old) {
    $pdo->prepare('DELETE FROM non_conformities WHERE id = ?')->execute([$id]);
    audit_log('delete', 'non_conformity', $id, $old, null);
    flash_set('success', 'Non-conformité supprimée.');
}
redirect('nonconformites.php');
