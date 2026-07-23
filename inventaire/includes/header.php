<?php if (!isset($pageTitle)) { $pageTitle = 'Inventaire'; } ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="/inventaire/assets/img/logo-mark.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/inventaire/assets/img/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/inventaire/assets/img/favicon-16.png">
    <link rel="apple-touch-icon" href="/inventaire/assets/img/apple-touch-icon.png">
    <link rel="stylesheet" href="/inventaire/assets/style.css">
</head>
<body>
    <header class="topbar">
        <h1>
            <a href="/inventaire/index.php">
                <img src="/inventaire/assets/img/logo-mark.svg" alt="Enosis Group" class="brand-logo" width="32" height="32">
                Inventaire
            </a>
        </h1>
        <nav>
            <a href="/inventaire/index.php">Produits</a>
            <a href="/inventaire/produit_form.php">Ajouter un produit</a>
            <a href="/inventaire/historique.php">Historique des mouvements</a>
        </nav>
    </header>
    <main class="container">
