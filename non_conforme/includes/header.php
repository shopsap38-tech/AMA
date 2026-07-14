<?php if (!isset($pageTitle)) { $pageTitle = 'Suivi des non-conformités'; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/non_conforme/assets/style.css">
</head>
<body>
    <header class="topbar">
        <h1><a href="/non_conforme/index.php">Suivi des non-conformités</a></h1>
        <nav>
            <a href="/non_conforme/index.php">Dashboard</a>
            <a href="/non_conforme/semi_fini.php">PF Semi-fini-matic</a>
            <a href="/non_conforme/desinfectant.php">Désinfectant</a>
            <a href="/non_conforme/big_bag.php">Big Bag</a>
        </nav>
    </header>
    <main class="container">
