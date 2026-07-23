<?php
require_once __DIR__ . '/../config/config.php';
require_role(['administrateur']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/users/index.php');
}
check_csrf();

$id       = (int) ($_POST['id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$nom      = trim($_POST['nom'] ?? '');
$email    = trim($_POST['email'] ?? '');
$email    = $email === '' ? null : $email;
$role     = $_POST['role'] ?? '';
$password = $_POST['password'] ?? '';
$actif    = isset($_POST['actif']) ? 1 : 0;

$errors = [];
if ($username === '')                                   { $errors[] = 'Le nom d\'utilisateur est obligatoire.'; }
if ($username !== '' && !preg_match('/^[\p{L}0-9._-]{2,60}$/u', $username)) {
    $errors[] = 'Nom d\'utilisateur invalide (2 à 60 caractères : lettres, chiffres, . _ -).';
}
if ($nom === '')                                        { $errors[] = 'Le nom complet est obligatoire.'; }
if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Adresse e-mail invalide.'; }
if (!array_key_exists($role, $GLOBALS['ROLES']))        { $errors[] = 'Rôle invalide.'; }
if ($id === 0 && $password === '')                      { $errors[] = 'Le mot de passe est obligatoire pour un nouvel utilisateur.'; }
if ($password !== '' && strlen($password) < 4)          { $errors[] = 'Le mot de passe doit contenir au moins 4 caractères.'; }

// Unicité du nom d'utilisateur (insensible à la casse)
$stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE LOWER(username) = LOWER(?) AND id <> ?');
$stmt->execute([$username, $id]);
if ($stmt->fetch()) {
    $errors[] = 'Ce nom d\'utilisateur est déjà utilisé.';
}

// Unicité de l'e-mail (si renseigné)
if ($email !== null) {
    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id <> ?');
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        $errors[] = 'Cette adresse e-mail est déjà utilisée.';
    }
}

if ($errors) {
    set_flash('danger', implode(' ', $errors));
    redirect($id > 0 ? '/users/form.php?id=' . $id : '/users/form.php');
}

try {
    if ($id > 0) {
        if ($password !== '') {
            $stmt = $pdo->prepare(
                'UPDATE utilisateurs SET username = ?, nom = ?, email = ?, role = ?, actif = ?, mot_de_passe = ? WHERE id = ?'
            );
            $stmt->execute([$username, $nom, $email, $role, $actif, password_hash($password, PASSWORD_BCRYPT), $id]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE utilisateurs SET username = ?, nom = ?, email = ?, role = ?, actif = ? WHERE id = ?'
            );
            $stmt->execute([$username, $nom, $email, $role, $actif, $id]);
        }
        set_flash('success', 'Utilisateur mis à jour.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO utilisateurs (username, nom, email, role, actif, mot_de_passe) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$username, $nom, $email, $role, $actif, password_hash($password, PASSWORD_BCRYPT)]);
        set_flash('success', 'Utilisateur créé.');
    }
} catch (Throwable $ex) {
    set_flash('danger', "Erreur lors de l'enregistrement de l'utilisateur.");
}

redirect('/users/index.php');
