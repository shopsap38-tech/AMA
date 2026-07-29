<?php /** @var string|null $permission */ ?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 · Accès refusé</title><link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"></head>
<body style="display:grid;place-items:center;min-height:100vh;background:var(--bg)">
<div style="text-align:center;max-width:440px;padding:24px">
    <div style="font-size:84px;color:var(--warning);line-height:1"><i class="fa-solid fa-lock"></i></div>
    <h1 style="font-size:22px;margin:14px 0 8px">Accès refusé</h1>
    <p style="color:var(--text-soft)">Vous ne disposez pas des droits nécessaires pour accéder à cette ressource.
    <?php if (!empty($permission)): ?><br><small class="text-muted">Permission requise : <code><?= e($permission) ?></code></small><?php endif; ?></p>
    <a href="<?= e(url('dashboard')) ?>" class="btn btn-primary"><i class="fa-solid fa-house me-2"></i>Retour au tableau de bord</a>
</div>
</body></html>
