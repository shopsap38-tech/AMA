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

$today = date('d/m/Y');

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
