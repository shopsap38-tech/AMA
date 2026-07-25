<?php if (!isset($pageTitle)) { $pageTitle = 'Rapport de suivi de stock'; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/rapport-stock/assets/style.css">
</head>
<body>
    <header class="topbar no-print">
        <h1><a href="/rapport-stock/index.php">Suivi de stock</a></h1>
        <nav>
            <a href="/rapport-stock/index.php">Rapport</a>
            <a href="#" onclick="window.print(); return false;">Imprimer</a>
        </nav>
    </header>
    <main class="container">
