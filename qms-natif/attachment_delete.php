<?php
require __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('nonconformites.php'); }
csrf_check();
require_permission('nonconformity.update');

$id = (int) ($_POST['id'] ?? 0);
$ncId = (int) ($_POST['nc_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM attachments WHERE id = ?');
$stmt->execute([$id]);
$att = $stmt->fetch();

if ($att) {
    $path = __DIR__ . '/uploads/' . $att['stored_name'];
    if (is_file($path)) { @unlink($path); }
    $pdo->prepare('DELETE FROM attachments WHERE id = ?')->execute([$id]);
    audit_log('delete', 'attachment', $id, $att, null);
    flash_set('success', 'Pièce jointe supprimée.');
}
redirect('nc_show.php?id=' . $ncId);
