<?php /** @var string $title */ ?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 · Page introuvable</title><link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"></head>
<body style="display:grid;place-items:center;min-height:100vh;background:var(--bg)">
<div style="text-align:center;max-width:440px;padding:24px">
    <div style="font-size:84px;font-weight:800;color:var(--brand);line-height:1">404</div>
    <h1 style="font-size:22px;margin:8px 0">Page introuvable</h1>
    <p style="color:var(--text-soft)">La ressource demandée n'existe pas ou a été déplacée.</p>
    <a href="<?= e(url('dashboard')) ?>" class="btn btn-primary"><i class="fa-solid fa-house me-2"></i>Retour au tableau de bord</a>
</div>
</body></html>
