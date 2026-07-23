<?php
require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Veuillez saisir votre nom d\'utilisateur et votre mot de passe.';
    } else {
        // Connexion par nom d'utilisateur (insensible à la casse).
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE LOWER(username) = LOWER(?) AND actif = 1 LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'nom'      => $user['nom'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ];
            redirect('/index.php');
        } else {
            $error = 'Identifiants incorrects ou compte désactivé.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Connexion · Demande Emballage</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(BASE_URL) ?>/assets/img/logo-mark.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(BASE_URL) ?>/assets/img/favicon-32.png">
    <link rel="apple-touch-icon" href="<?= e(BASE_URL) ?>/assets/img/apple-touch-icon.png">
    <link rel="manifest" href="<?= e(BASE_URL) ?>/manifest.webmanifest">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="card login-card shadow-lg">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <img src="<?= e(logo_full_url()) ?>" alt="Enosis Group"
                     style="max-width:190px;height:auto;">
                <h1 class="h5 mt-3 mb-0">Demande Emballage</h1>
                <p class="text-muted small">Connectez-vous pour continuer</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= e($_POST['username'] ?? '') ?>" required autofocus
                               autocomplete="username" placeholder="Admin, Demandeur, Preparateur…">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               required autocomplete="current-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-box-arrow-in-right"></i> Se connecter
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
