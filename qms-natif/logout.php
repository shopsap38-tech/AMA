<?php
require __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    audit_log('logout', 'auth', (int) current_user()['id']);
}
$_SESSION = [];
session_destroy();
session_start();
flash_set('success', 'Vous avez été déconnecté.');
redirect('login.php');
