<?php
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = [
    'type_article' => '', 'numero_article' => '', 'description_article' => '', 'stock_mag' => '',
    'date_expiration' => '', 'code_um' => '', 'nbr_palettes' => '', 'prix_unitaire' => '',
    'poids_par_carton_kg' => '', 'date_blocage' => '', 'motif' => 'produit expiré',
    'responsable' => 'Service qualité', 'decision_cq' => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM nc_desinfectant WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) { $row = $found; }
}

$pageTitle = ($id > 0 ? 'Modifier' : 'Ajouter') . ' - Désinfectant';
require_once __DIR__ . '/includes/header.php';
?>

<h2><?= $id > 0 ? 'Modifier une non-conformité' : 'Ajouter une non-conformité' ?> &ndash; Désinfectant</h2>

<form class="form" method="post" action="/non_conforme/desinfectant_save.php">
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row">
        <div class="field">
            <label for="type_article">Type d'article</label>
            <input type="text" id="type_article" name="type_article" value="<?= htmlspecialchars($row['type_article'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="numero_article">N° article</label>
            <input type="text" id="numero_article" name="numero_article" required value="<?= htmlspecialchars($row['numero_article']) ?>">
        </div>
    </div>

    <div class="field">
        <label for="description_article">Description article</label>
        <input type="text" id="description_article" name="description_article" required value="<?= htmlspecialchars($row['description_article']) ?>">
    </div>

    <div class="row">
        <div class="field">
            <label for="stock_mag">Stock au MAG</label>
            <input type="number" id="stock_mag" name="stock_mag" min="0" required value="<?= htmlspecialchars((string) $row['stock_mag']) ?>">
        </div>
        <div class="field">
            <label for="code_um">Code UM</label>
            <input type="text" id="code_um" name="code_um" value="<?= htmlspecialchars($row['code_um'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="nbr_palettes">Nbr de palettes</label>
            <input type="number" id="nbr_palettes" name="nbr_palettes" min="0" required value="<?= htmlspecialchars((string) $row['nbr_palettes']) ?>">
        </div>
    </div>

    <div class="row">
        <div class="field">
            <label for="prix_unitaire">Prix unitaire</label>
            <input type="number" step="0.0001" id="prix_unitaire" name="prix_unitaire" min="0" value="<?= htmlspecialchars((string) $row['prix_unitaire']) ?>">
        </div>
        <div class="field">
            <label for="poids_par_carton_kg">Poids par carton (kg)</label>
            <input type="number" step="0.0001" id="poids_par_carton_kg" name="poids_par_carton_kg" min="0" value="<?= htmlspecialchars((string) $row['poids_par_carton_kg']) ?>">
        </div>
    </div>

    <div class="row">
        <div class="field">
            <label for="date_expiration">Date d'expiration <small>(vide = aucune date)</small></label>
            <input type="date" id="date_expiration" name="date_expiration" value="<?= htmlspecialchars($row['date_expiration'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="date_blocage">Date de blocage</label>
            <input type="date" id="date_blocage" name="date_blocage" value="<?= htmlspecialchars($row['date_blocage'] ?? '') ?>">
        </div>
    </div>

    <div class="row">
        <div class="field">
            <label for="motif">Motif</label>
            <input type="text" id="motif" name="motif" value="<?= htmlspecialchars($row['motif'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="responsable">Responsable</label>
            <input type="text" id="responsable" name="responsable" value="<?= htmlspecialchars($row['responsable'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="decision_cq">Décision CQ</label>
            <input type="text" id="decision_cq" name="decision_cq" value="<?= htmlspecialchars($row['decision_cq'] ?? '') ?>">
        </div>
    </div>

    <button type="submit" class="btn">Enregistrer</button>
    <a href="/non_conforme/desinfectant.php" class="btn btn-secondary">Annuler</a>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
