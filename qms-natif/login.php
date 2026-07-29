<?php
require __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('index.php');
}

$emailError = null;
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $oldEmail = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($oldEmail === '' || $password === '') {
        $emailError = 'Veuillez saisir votre email et votre mot de passe.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$oldEmail]);
        $u = $stmt->fetch();

        if ($u && (int) $u['is_active'] === 1 && password_verify($password, $u['password_hash'])) {
            // Ré-hachage transparent si nécessaire.
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $u['id'];
            $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$u['id']]);
            audit_log('login', 'auth', (int) $u['id']);
            flash_set('success', 'Bienvenue ' . $u['first_name'] . ' !');
            redirect('index.php');
        }
        audit_log('login_failed', 'auth', null, null, ['email' => $oldEmail]);
        $emailError = 'Identifiants incorrects ou compte désactivé.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion · Quality Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-layout">
    <div class="auth-hero">
        <div class="auth-hero-inner">
            <div class="auth-brand"><span class="brand-mark lg"><i class="fa-solid fa-shield-halved"></i></span><span>QMS</span></div>
            <h2>Pilotez la qualité<br>à l'échelle de l'industrie.</h2>
            <p>Non-conformités, actions correctives, circuits de validation et traçabilité complète — dans une plateforme unifiée.</p>
            <ul class="auth-features">
                <li><i class="fa-solid fa-circle-check"></i> Gestion des non-conformités &amp; CAPA</li>
                <li><i class="fa-solid fa-circle-check"></i> Workflow de validation multi-niveaux</li>
                <li><i class="fa-solid fa-circle-check"></i> Journal d'audit &amp; RBAC</li>
                <li><i class="fa-solid fa-circle-check"></i> Tableaux de bord &amp; KPI temps réel</li>
            </ul>
        </div>
        <div class="auth-hero-glow"></div>
    </div>
    <div class="auth-panel">
        <div class="auth-form-wrap">
            <h1>Bienvenue</h1>
            <p class="text-muted mb-4">Connectez-vous à votre espace qualité.</p>
            <?php if ($emailError): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($emailError) ?></div><?php endif; ?>
            <form method="post" action="login.php" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Adresse email</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($oldEmail) ?>" placeholder="prenom.nom@entreprise.com" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="toggle-password" tabindex="-1"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mt-2"><i class="fa-solid fa-right-to-bracket me-2"></i>Se connecter</button>
            </form>
            <div class="auth-demo">
                <div class="auth-demo-title">Comptes de démonstration <span>· mot de passe <code>Qms@2026</code></span></div>
                <div class="auth-demo-grid">
                    <button type="button" class="demo-chip" data-email="admin@qms.local">Administrateur</button>
                    <button type="button" class="demo-chip" data-email="qualite@qms.local">Resp. Qualité</button>
                    <button type="button" class="demo-chip" data-email="production@qms.local">Resp. Production</button>
                    <button type="button" class="demo-chip" data-email="employe@qms.local">Employé</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.demo-chip').forEach(c => c.addEventListener('click', function () {
    document.getElementById('email').value = this.dataset.email;
    document.getElementById('password').value = 'Qms@2026';
}));
document.querySelector('.toggle-password')?.addEventListener('click', function () {
    const i = document.getElementById('password');
    i.type = i.type === 'password' ? 'text' : 'password';
    this.querySelector('i').classList.toggle('fa-eye'); this.querySelector('i').classList.toggle('fa-eye-slash');
});
</script>
</body>
</html>
