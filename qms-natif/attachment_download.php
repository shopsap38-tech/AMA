<?php
require __DIR__ . '/includes/functions.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM attachments WHERE id = ?');
$stmt->execute([$id]);
$att = $stmt->fetch();

if (!$att) { http_response_code(404); die('Fichier introuvable.'); }
$path = __DIR__ . '/uploads/' . $att['stored_name'];
if (!is_file($path)) { http_response_code(404); die('Fichier absent du disque.'); }

header('Content-Type: ' . $att['mime_type']);
header('Content-Disposition: attachment; filename="' . rawurlencode($att['original_name']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
