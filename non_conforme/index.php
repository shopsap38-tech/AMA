<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Dashboard - Suivi des non-conformités';

// Agrégats PF Semi-fini-matic
$sf = $pdo->query('SELECT COUNT(*) AS lignes, COALESCE(SUM(quantite),0) AS quantite,
                          COALESCE(SUM(nbr_palettes),0) AS palettes, COALESCE(SUM(poids_total_kg),0) AS poids_kg
                   FROM nc_semi_fini')->fetch();

// Agrégats Désinfectant
$de = $pdo->query('SELECT COUNT(*) AS lignes, COALESCE(SUM(nbr_palettes),0) AS palettes,
                          COALESCE(SUM(stock_mag * prix_unitaire),0) AS total_valeur,
                          COALESCE(SUM(stock_mag * poids_par_carton_kg),0) AS poids_kg
                   FROM nc_desinfectant')->fetch();

// Agrégats Big Bag
$bb = $pdo->query('SELECT COUNT(*) AS lignes, COALESCE(SUM(total_big_bag),0) AS big_bags,
                          COALESCE(SUM(tonnage),0) AS tonnage
                   FROM nc_big_bag')->fetch();

// Répartition du désinfectant par type d'article (nbr de palettes)
$deTypes = $pdo->query("SELECT COALESCE(NULLIF(type_article,''),'Autre') AS type,
                               SUM(nbr_palettes) AS palettes
                        FROM nc_desinfectant GROUP BY type ORDER BY palettes DESC")->fetchAll();

// Top articles semi-fini par nombre de palettes
$sfTop = $pdo->query('SELECT description_article, nbr_palettes
                      FROM nc_semi_fini ORDER BY nbr_palettes DESC, description_article ASC')->fetchAll();

// Données pour les graphiques
$chartPalettes = [
    'Semi-fini'    => (int) $sf['palettes'],
    'Désinfectant' => (int) $de['palettes'],
    'Big Bag'      => (int) $bb['big_bags'],
];
$chartPoids = [
    'Semi-fini'    => round((float) $sf['poids_kg'], 0),
    'Désinfectant' => round((float) $de['poids_kg'], 0),
    'Big Bag'      => round((float) $bb['tonnage'], 0),
];
$chartDeTypes = [];
foreach ($deTypes as $t) { $chartDeTypes[$t['type']] = (int) $t['palettes']; }
$chartSfTop = [];
foreach ($sfTop as $t) { $chartSfTop[$t['description_article']] = (int) $t['nbr_palettes']; }

$today = date('d/m/Y');

require_once __DIR__ . '/includes/charts.php';
require_once __DIR__ . '/includes/header.php';
?>

<h2>Dashboard &ndash; Suivi des non-conformités</h2>
<p class="sub" style="color:var(--muted);">Situation au <?= htmlspecialchars($today) ?></p>

<div class="cards">
    <div class="card">
        <h3>PF Semi-fini-matic</h3>
        <div class="metric"><?= (int) $sf['palettes'] ?></div>
        <div class="sub">palettes non conformes</div>
        <div class="sub"><?= number_format((float) $sf['poids_kg'], 0, ',', ' ') ?> kg &middot; <?= (int) $sf['quantite'] ?> unités</div>
    </div>
    <div class="card">
        <h3>Désinfectant</h3>
        <div class="metric"><?= (int) $de['palettes'] ?></div>
        <div class="sub">palettes non conformes</div>
        <div class="sub"><?= number_format((float) $de['poids_kg'], 0, ',', ' ') ?> kg &middot; <?= number_format((float) $de['total_valeur'], 2, ',', ' ') ?> DH</div>
    </div>
    <div class="card">
        <h3>Big Bag</h3>
        <div class="metric"><?= (int) $bb['big_bags'] ?></div>
        <div class="sub">big bags non conformes</div>
        <div class="sub"><?= number_format((float) $bb['tonnage'], 0, ',', ' ') ?> kg</div>
    </div>
</div>

<h3 class="section-title">Graphiques</h3>
<div class="charts">
    <div class="chart-card">
        <h4>Palettes / big bags par catégorie</h4>
        <?= svg_bar_chart($chartPalettes, 'unités') ?>
    </div>
    <div class="chart-card">
        <h4>Poids total par catégorie (kg)</h4>
        <?= svg_bar_chart($chartPoids, 'kg') ?>
    </div>
    <div class="chart-card">
        <h4>Désinfectant : palettes par type d'article</h4>
        <?= svg_donut($chartDeTypes, 'palettes') ?>
    </div>
    <div class="chart-card">
        <h4>Semi-fini : palettes par article</h4>
        <?= svg_bar_chart($chartSfTop, 'palettes') ?>
    </div>
</div>

<h3 class="section-title">Synthèse détaillée</h3>
<table class="table">
    <thead>
        <tr>
            <th>Catégorie</th>
            <th class="text-right">Lignes</th>
            <th class="text-right">Palettes / Big bags</th>
            <th class="text-right">Poids total (kg)</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>PF Semi-fini-matic</td>
            <td class="text-right"><?= (int) $sf['lignes'] ?></td>
            <td class="text-right"><?= (int) $sf['palettes'] ?></td>
            <td class="text-right"><?= number_format((float) $sf['poids_kg'], 2, ',', ' ') ?></td>
            <td><a href="/non_conforme/semi_fini.php">Détails &rarr;</a></td>
        </tr>
        <tr>
            <td>Désinfectant</td>
            <td class="text-right"><?= (int) $de['lignes'] ?></td>
            <td class="text-right"><?= (int) $de['palettes'] ?></td>
            <td class="text-right"><?= number_format((float) $de['poids_kg'], 2, ',', ' ') ?></td>
            <td><a href="/non_conforme/desinfectant.php">Détails &rarr;</a></td>
        </tr>
        <tr>
            <td>Big Bag</td>
            <td class="text-right"><?= (int) $bb['lignes'] ?></td>
            <td class="text-right"><?= (int) $bb['big_bags'] ?></td>
            <td class="text-right"><?= number_format((float) $bb['tonnage'], 2, ',', ' ') ?></td>
            <td><a href="/non_conforme/big_bag.php">Détails &rarr;</a></td>
        </tr>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
