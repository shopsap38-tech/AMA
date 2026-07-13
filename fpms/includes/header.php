<?php
if (!isset($pageTitle)) { $pageTitle = 'FPMS'; }
$currentPage = $currentPage ?? '';
$nav = [
    'dashboard'    => ['index.php',         'Tableau de bord'],
    'chariots'     => ['chariots.php',      'Chariots'],
    'palettes'     => ['palettes.php',      'Palettes'],
    'reparations'  => ['reparations.php',   'Réparations'],
    'rapports'     => ['rapports.php',      'Rapports'],
    'alertes'      => ['alertes.php',       'Alertes'],
    'statistiques' => ['statistiques.php',  'Statistiques'],
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
            <?php foreach ($nav as $key => [$url, $label]): ?>
                <a href="/fpms/<?= $url ?>" class="<?= $currentPage === $key ? 'active' : '' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </nav>
    </header>
    <main class="container">
