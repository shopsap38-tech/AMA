<?php
require __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 8');
$stmt->execute([$user['id']]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'unread' => unread_notifications_count(),
    'items'  => $stmt->fetchAll(),
], JSON_UNESCAPED_UNICODE);
