<?php /** @var \Throwable|null $exception */ ?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>500 · Erreur serveur</title><link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"></head>
<body style="display:grid;place-items:center;min-height:100vh;background:var(--bg)">
<div style="text-align:center;max-width:640px;padding:24px">
    <div style="font-size:84px;color:var(--danger);line-height:1"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <h1 style="font-size:22px;margin:14px 0 8px">Une erreur est survenue</h1>
    <p style="color:var(--text-soft)">Le serveur a rencontré un problème. L'incident a été journalisé.</p>
    <?php if (!empty($exception) && config('app.debug')): ?>
        <div class="prose-block" style="text-align:left;margin-top:16px;font-family:monospace;font-size:12px">
            <strong><?= e($exception->getMessage()) ?></strong><br>
            <?= e($exception->getFile()) ?>:<?= (int) $exception->getLine() ?>
        </div>
    <?php endif; ?>
    <a href="<?= e(url('dashboard')) ?>" class="btn btn-primary mt-3"><i class="fa-solid fa-house me-2"></i>Retour au tableau de bord</a>
</div>
</body></html>
