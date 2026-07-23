<?php
require_once __DIR__ . '/../config/config.php';
require_role(['administrateur']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/users/index.php');
}
check_csrf();

$id       = (int) ($_POST['id'] ?? 0);
$nom      = trim($_POST['nom'] ?? '');
$email    = trim($_POST['email'] ?? '');
$role     = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';
$actif    = isset($_POST['actif']) ? 1 : 0;

$errors = [];
if ($nom === '')                                        { $errors[] = 'Le nom est obligatoire.'; }
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Adresse e-mail invalide.'; }
if (!array_key_exists($role, $GLOBALS['ROLES']))        { $errors[] = 'Rôle invalide.'; }
if ($id === 0 && $password === '')                      { $errors[] = 'Le mot de passe est obligatoire pour un nouvel utilisateur.'; }
if ($password !== '' && strlen($password) < 6)          { $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.'; }

// Unicité de l'e-mail
$stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id <> ?');
$stmt->execute([$email, $id]);
if ($stmt->fetch()) {
    $errors[] = 'Cette adresse e-mail est déjà utilisée.';
}

if ($errors) {
    set_flash('danger', implode(' ', $errors));
    redirect($id > 0 ? '/users/form.php?id=' . $id : '/users/form.php');
}

try {
    if ($id > 0) {
        if ($password !== '') {
            $stmt = $pdo->prepare(
                'UPDATE utilisateurs SET nom = ?, email = ?, role = ?, actif = ?, mot_de_passe = ? WHERE id = ?'
            );
            $stmt->execute([$nom, $email, $role, $actif, password_hash($password, PASSWORD_BCRYPT), $id]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE utilisateurs SET nom = ?, email = ?, role = ?, actif = ? WHERE id = ?'
            );
            $stmt->execute([$nom, $email, $role, $actif, $id]);
        }
        set_flash('success', 'Utilisateur mis à jour.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO utilisateurs (nom, email, role, actif, mot_de_passe) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nom, $email, $role, $actif, password_hash($password, PASSWORD_BCRYPT)]);
        set_flash('success', 'Utilisateur créé.');
    }
} catch (Throwable $ex) {
    set_flash('danger', "Erreur lors de l'enregistrement de l'utilisateur.");
}

redirect('/users/index.php');
