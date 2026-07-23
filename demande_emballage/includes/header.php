<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}
if (!isset($pageTitle)) {
    $pageTitle = 'Demande Emballage';
}
$u    = current_user();
$role = current_role();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0d6efd">
    <title><?= e($pageTitle) ?> · Demande Emballage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/style.css">
</head>
<body class="app-body">
<?php if (is_logged_in()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= e(BASE_URL) ?>/index.php">
            <i class="bi bi-box-seam-fill"></i> Demande Emballage
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (in_array($role, ['administrateur', 'facturation', 'preparateur'], true)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/dashboard.php"><i class="bi bi-bar-chart-line"></i> Tableau de bord</a>
                    </li>
                <?php endif; ?>
                <?php if (in_array($role, ['administrateur', 'demandeur'], true)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/demande_form.php"><i class="bi bi-plus-circle"></i> Nouvelle demande</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/mes_demandes.php"><i class="bi bi-list-check"></i> Mes demandes</a>
                    </li>
                <?php endif; ?>
                <?php if (in_array($role, ['administrateur', 'preparateur'], true)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/preparateur.php"><i class="bi bi-hourglass-split"></i> À préparer</a>
                    </li>
                <?php endif; ?>
                <?php if (in_array($role, ['administrateur', 'facturation'], true)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/facturation.php"><i class="bi bi-receipt"></i> À facturer</a>
                    </li>
                <?php endif; ?>
                <?php if ($role === 'administrateur'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(BASE_URL) ?>/users/index.php"><i class="bi bi-people"></i> Utilisateurs</a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center text-white">
                <span class="me-3 small">
                    <i class="bi bi-person-circle"></i> <?= e($u['nom']) ?>
                    <span class="badge bg-light text-primary ms-1"><?= e(role_label($role)) ?></span>
                </span>
                <a class="btn btn-outline-light btn-sm" href="<?= e(BASE_URL) ?>/auth/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>
<main class="container py-4">
<?php foreach (get_flashes() as $flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endforeach; ?>
