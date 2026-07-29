<?php
require __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('notifications.php'); }
csrf_check();
require_login();

$user = current_user();
if (!empty($_POST['all'])) {
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$user['id']]);
    flash_set('success', 'Toutes les notifications ont été marquées comme lues.');
} elseif (!empty($_POST['id'])) {
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([(int) $_POST['id'], $user['id']]);
}
redirect('notifications.php');
