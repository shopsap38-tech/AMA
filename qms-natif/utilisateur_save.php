<?php
require __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('utilisateurs.php'); }
csrf_check();
require_permission('user.manage');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$isEdit = $id > 0;

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$password  = $_POST['password'] ?? '';
$roleId    = (int) ($_POST['role_id'] ?? 0);
$deptId    = ($_POST['department_id'] ?? '') !== '' ? (int) $_POST['department_id'] : null;
$jobTitle  = trim($_POST['job_title'] ?? '') ?: null;
$phone     = trim($_POST['phone'] ?? '') ?: null;
$isActive  = !empty($_POST['is_active']) ? 1 : 0;

$errors = [];
if ($firstName === '') { $errors[] = 'Le prénom est obligatoire.'; }
if ($lastName === '') { $errors[] = 'Le nom est obligatoire.'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "L'email est invalide."; }
if ($roleId <= 0) { $errors[] = 'Le rôle est obligatoire.'; }
if (!$isEdit && strlen($password) < 8) { $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.'; }

// Unicité de l'email
$chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?' . ($isEdit ? ' AND id <> ?' : ''));
$chk->execute($isEdit ? [$email, $id] : [$email]);
if ((int) $chk->fetchColumn() > 0) { $errors[] = 'Cet email est déjà utilisé.'; }

if ($errors) {
    $_SESSION['_old'] = $_POST;
    flash_set('error', implode(' ', $errors));
    redirect($isEdit ? 'utilisateur_form.php?id=' . $id : 'utilisateur_form.php');
}

if ($isEdit) {
    $pdo->prepare('UPDATE users SET first_name=?, last_name=?, email=?, role_id=?, department_id=?, job_title=?, phone=?, is_active=?, updated_at=NOW() WHERE id=?')
        ->execute([$firstName, $lastName, $email, $roleId, $deptId, $jobTitle, $phone, $isActive, $id]);
    if ($password !== '') {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
    audit_log('update', 'user', $id, null, ['email' => $email]);
    flash_set('success', 'Utilisateur mis à jour.');
} else {
    $pdo->prepare('INSERT INTO users (role_id, department_id, first_name, last_name, email, password_hash, job_title, phone, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())')
        ->execute([$roleId, $deptId, $firstName, $lastName, $email, password_hash($password, PASSWORD_DEFAULT), $jobTitle, $phone]);
    $newId = (int) $pdo->lastInsertId();
    audit_log('create', 'user', $newId, null, ['email' => $email]);
    flash_set('success', 'Utilisateur créé avec succès.');
}
redirect('utilisateurs.php');
