<?php
/**
 * En-tête commun : sidebar + topbar. Attend $pageTitle et $activeMenu.
 * Inclure APRÈS require_login().
 */
$user = current_user();
$pageTitle = $pageTitle ?? 'QMS';
$activeMenu = $activeMenu ?? '';
$initials = strtoupper(mb_substr($user['first_name'] ?? 'U', 0, 1) . mb_substr($user['last_name'] ?? '', 0, 1));

$menu = [
    ['section' => 'Pilotage', 'items' => [
        ['key' => 'dashboard', 'url' => 'index.php', 'icon' => 'fa-gauge-high', 'label' => 'Tableau de bord', 'perm' => null],
    ]],
    ['section' => 'Qualité', 'items' => [
        ['key' => 'nonconformites', 'url' => 'nonconformites.php', 'icon' => 'fa-triangle-exclamation', 'label' => 'Non-conformités', 'perm' => 'nonconformity.view'],
        ['key' => 'rapports', 'url' => 'rapports.php', 'icon' => 'fa-file-lines', 'label' => 'Rapports', 'perm' => 'report.view'],
    ]],
    ['section' => 'Administration', 'items' => [
        ['key' => 'utilisateurs', 'url' => 'utilisateurs.php', 'icon' => 'fa-users-gear', 'label' => 'Utilisateurs', 'perm' => 'user.manage'],
        ['key' => 'audit', 'url' => 'audit.php', 'icon' => 'fa-shield-halved', 'label' => "Journal d'audit", 'perm' => 'audit.view'],
    ]],
];
$flashSuccess = flash_get('success');
$flashError = flash_get('error');
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> · Quality Management System</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
            <span class="brand-text"><strong>QMS</strong><small>Quality Management</small></span>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($menu as $group): ?>
                <?php $visible = array_filter($group['items'], fn ($i) => $i['perm'] === null || can($i['perm'])); if ($visible === []) continue; ?>
                <div class="nav-section"><?= e($group['section']) ?></div>
                <?php foreach ($visible as $item): ?>
                    <a href="<?= e($item['url']) ?>" class="nav-link <?= $activeMenu === $item['key'] ? 'active' : '' ?>">
                        <i class="fa-solid <?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer"><div class="env-pill"><i class="fa-solid fa-circle-nodes"></i> PRODUCTION</div></div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button class="btn-icon d-lg-none" id="sidebarToggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title"><h1><?= e($pageTitle) ?></h1></div>
            <div class="topbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="globalSearch" placeholder="Rechercher une non-conformité…" autocomplete="off">
            </div>
            <div class="topbar-actions">
                <button class="btn-icon" id="themeToggle" title="Mode clair / sombre"><i class="fa-solid fa-moon"></i></button>
                <div class="dropdown">
                    <button class="btn-icon position-relative" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-bell"></i><span class="notif-dot d-none" id="notifDot"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notif-menu">
                        <div class="notif-header">
                            <span>Notifications</span>
                            <form method="post" action="notification_read.php" class="m-0">
                                <?= csrf_field() ?><input type="hidden" name="all" value="1">
                                <button class="btn-link-sm" type="submit">Tout lire</button>
                            </form>
                        </div>
                        <div class="notif-list" id="notifList"><div class="notif-empty">Chargement…</div></div>
                        <a href="notifications.php" class="notif-footer">Voir tout le centre de notifications</a>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="user-chip" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar"><?= e($initials) ?></span>
                        <span class="user-meta d-none d-md-flex">
                            <strong><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                            <small><?= e($user['role_name']) ?></small>
                        </span>
                        <i class="fa-solid fa-chevron-down d-none d-md-inline"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header"><?= e($user['email']) ?><br><small class="text-muted"><?= e($user['department_name'] ?? 'Sans service') ?></small></div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Se déconnecter</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show app-alert"><i class="fa-solid fa-circle-check me-2"></i><?= e($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert alert-danger alert-dismissible fade show app-alert"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
