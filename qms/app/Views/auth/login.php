<?php $emailErrors = errors_for('email'); ?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion · <?= e(config('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-layout">
    <div class="auth-hero">
        <div class="auth-hero-inner">
            <div class="auth-brand">
                <span class="brand-mark lg"><i class="fa-solid fa-shield-halved"></i></span>
                <span>QMS</span>
            </div>
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

            <?php if ($flash = \App\Core\Session::flash('error')): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($flash) ?></div>
            <?php endif; ?>
            <?php if ($ok = \App\Core\Session::flash('success')): ?>
                <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= e($ok) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('login')) ?>" novalidate>
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="email">Adresse email</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" class="form-control <?= $emailErrors ? 'is-invalid' : '' ?>" id="email" name="email"
                               value="<?= e(old('email')) ?>" placeholder="prenom.nom@entreprise.com" required autofocus>
                    </div>
                    <?php foreach ($emailErrors as $err): ?>
                        <div class="text-danger small mt-1"><?= e($err) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mot de passe</label>
                    <div class="input-icon">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        <button type="button" class="toggle-password" tabindex="-1" aria-label="Afficher"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Se connecter
                </button>
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
document.querySelectorAll('.demo-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
        document.getElementById('email').value = this.dataset.email;
        document.getElementById('password').value = 'Qms@2026';
    });
});
document.querySelector('.toggle-password')?.addEventListener('click', function () {
    var input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
    this.querySelector('i').classList.toggle('fa-eye');
    this.querySelector('i').classList.toggle('fa-eye-slash');
});
</script>
</body>
</html>
