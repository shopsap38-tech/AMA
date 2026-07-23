<?php
require_once __DIR__ . '/../config/config.php';
require_role(['administrateur']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/users/index.php');
}
check_csrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id === (int) current_user()['id']) {
    set_flash('warning', 'Vous ne pouvez pas supprimer votre propre compte.');
    redirect('/users/index.php');
}

try {
    $stmt = $pdo->prepare('DELETE FROM utilisateurs WHERE id = ?');
    $stmt->execute([$id]);
    set_flash('success', 'Utilisateur supprimé.');
} catch (Throwable $ex) {
    // Contrainte FK : l'utilisateur a des demandes rattachées → désactivation.
    $stmt = $pdo->prepare('UPDATE utilisateurs SET actif = 0 WHERE id = ?');
    $stmt->execute([$id]);
    set_flash('warning', 'Utilisateur lié à des demandes : le compte a été désactivé au lieu d\'être supprimé.');
}

redirect('/users/index.php');
