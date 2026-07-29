<?php
use App\Core\View;
/** @var string $title */
/** @var string $active */
$active = $active ?? '';
$user = auth()->user();
$initials = strtoupper(mb_substr($user['first_name'] ?? 'U', 0, 1) . mb_substr($user['last_name'] ?? '', 0, 1));
$nav = [
    ['section' => 'Pilotage', 'items' => [
        ['key' => 'dashboard', 'route' => 'dashboard', 'icon' => 'fa-gauge-high', 'label' => 'Tableau de bord', 'perm' => null],
    ]],
    ['section' => 'Qualité', 'items' => [
        ['key' => 'nonconformities', 'route' => 'nonconformities', 'icon' => 'fa-triangle-exclamation', 'label' => 'Non-conformités', 'perm' => 'nonconformity.view'],
        ['key' => 'reports', 'route' => 'reports', 'icon' => 'fa-file-lines', 'label' => 'Rapports', 'perm' => 'report.view'],
    ]],
    ['section' => 'Administration', 'items' => [
        ['key' => 'users', 'route' => 'users', 'icon' => 'fa-users-gear', 'label' => 'Utilisateurs', 'perm' => 'user.manage'],
        ['key' => 'audit', 'route' => 'audit', 'icon' => 'fa-shield-halved', 'label' => "Journal d'audit", 'perm' => 'audit.view'],
    ]],
];
?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'QMS') ?> · <?= e(config('app.name')) ?></title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <span class="brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
            <span class="brand-text">
                <strong>QMS</strong>
                <small>Quality Management</small>
            </span>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($nav as $group): ?>
                <?php
                $visible = array_filter($group['items'], static fn ($i) => $i['perm'] === null || auth()->can($i['perm']));
                if ($visible === []) { continue; }
                ?>
                <div class="nav-section"><?= e($group['section']) ?></div>
                <?php foreach ($visible as $item): ?>
                    <a href="<?= e(url($item['route'])) ?>" class="nav-link <?= $active === $item['key'] ? 'active' : '' ?>">
                        <i class="fa-solid <?= e($item['icon']) ?>"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="env-pill"><i class="fa-solid fa-circle-nodes"></i> <?= e(strtoupper(config('app.env'))) ?></div>
        </div>
    </aside>

    <!-- Main -->
    <div class="app-main">
        <header class="topbar">
            <button class="btn-icon d-lg-none" id="sidebarToggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
            <div class="topbar-title">
                <h1><?= e($title ?? 'QMS') ?></h1>
            </div>
            <div class="topbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="globalSearch" placeholder="Rechercher une non-conformité…" autocomplete="off">
            </div>
            <div class="topbar-actions">
                <button class="btn-icon" id="themeToggle" aria-label="Thème" title="Mode clair / sombre">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <div class="dropdown">
                    <button class="btn-icon position-relative" data-bs-toggle="dropdown" aria-label="Notifications" aria-expanded="false">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notif-dot d-none" id="notifDot"></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notif-menu" id="notifMenu">
                        <div class="notif-header">
                            <span>Notifications</span>
                            <form method="post" action="<?= e(url('notifications/read-all')) ?>" class="m-0">
                                <?= csrf_field() ?>
                                <button class="btn-link-sm" type="submit">Tout lire</button>
                            </form>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">Chargement…</div>
                        </div>
                        <a href="<?= e(url('notifications')) ?>" class="notif-footer">Voir tout le centre de notifications</a>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="user-chip" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar"><?= e($initials) ?></span>
                        <span class="user-meta d-none d-md-flex">
                            <strong><?= e(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></strong>
                            <small><?= e($user['role_name'] ?? '') ?></small>
                        </span>
                        <i class="fa-solid fa-chevron-down d-none d-md-inline"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header">
                            <?= e($user['email'] ?? '') ?><br>
                            <small class="text-muted"><?= e($user['department_name'] ?? 'Sans service') ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <form method="post" action="<?= e(url('logout')) ?>">
                            <?= csrf_field() ?>
                            <button class="dropdown-item text-danger" type="submit">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Se déconnecter
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            <?= View::partial('partials.flash') ?>
            <?= View::yield('content') ?>
        </main>

        <footer class="app-footer">
            <span>&copy; <?= date('Y') ?> <?= e(config('app.name')) ?> — Module Qualité</span>
            <span>v1.0 · PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?></span>
        </footer>
    </div>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
<?= View::yield('scripts') ?>
</body>
</html>
