<?php
require_once __DIR__ . '/../config/config.php';

if (is_logged_in()) {
    redirect('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez saisir votre e-mail et votre mot de passe.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'   => (int) $user['id'],
                'nom'  => $user['nom'],
                'email'=> $user['email'],
                'role' => $user['role'],
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrapper">
    <div class="card login-card shadow-lg">
        <div class="card-body p-4 p-sm-5">
            <div class="text-center mb-4">
                <i class="bi bi-box-seam-fill text-primary" style="font-size:3rem;"></i>
                <h1 class="h4 mt-2 mb-0">Demande Emballage</h1>
                <p class="text-muted small">Connectez-vous pour continuer</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="email" class="form-label">Adresse e-mail</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($_POST['email'] ?? '') ?>" required autofocus autocomplete="username">
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
