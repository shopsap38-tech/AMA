<?php
if (!isset($pageTitle)) { $pageTitle = 'FPMS'; }
$currentPage = $currentPage ?? '';
// Navigation groupée : accueil, dashboards, gestion, analyses.
$navGroupes = [
    [
        'dashboard'         => ['index.php',               'Accueil'],
    ],
    [
        'dash_palettes'     => ['dashboard_palettes.php',    'Dashboard Palettes'],
        'dash_chariots'     => ['dashboard_chariots.php',    'Dashboard Chariots'],
        'dash_reparations'  => ['dashboard_reparations.php', 'Dashboard Réparations'],
    ],
    [
        'chariots'          => ['chariots.php',    'Chariots'],
        'palettes'          => ['palettes.php',    'Palettes'],
        'reparations'       => ['reparations.php', 'Réparations'],
    ],
    [
        'rapports'          => ['rapports.php',     'Rapports'],
        'alertes'           => ['alertes.php',      'Alertes'],
        'statistiques'      => ['statistiques.php', 'Statistiques'],
    ],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/fpms/assets/style.css">
</head>
<body>
    <header class="topbar">
        <h1><a href="/fpms/index.php">FPMS<span class="topbar-sub">Fleet &amp; Pallet Management</span></a></h1>
        <nav>
            <?php foreach ($navGroupes as $i => $groupe): ?>
                <?php if ($i > 0): ?><span class="nav-sep"></span><?php endif; ?>
                <?php foreach ($groupe as $key => [$url, $label]): ?>
                    <a href="/fpms/<?= $url ?>" class="<?= $currentPage === $key ? 'active' : '' ?>"><?= $label ?></a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
    </header>
    <main class="container">
