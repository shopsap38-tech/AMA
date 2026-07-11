<?php if (!isset($pageTitle)) { $pageTitle = 'Inventaire'; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/inventaire/assets/style.css">
</head>
<body>
    <header class="topbar">
        <h1><a href="/inventaire/index.php">Inventaire</a></h1>
        <nav>
            <a href="/inventaire/index.php">Produits</a>
            <a href="/inventaire/produit_form.php">Ajouter un produit</a>
            <a href="/inventaire/historique.php">Historique des mouvements</a>
        </nav>
    </header>
    <main class="container">
