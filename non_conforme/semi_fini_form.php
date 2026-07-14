<?php
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = [
    'numero_article' => '', 'description_article' => '', 'quantite' => '',
    'nbr_palettes' => '', 'poids_total_kg' => '', 'motif' => 'colmaté', 'decision_sq' => 'a recycler',
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM nc_semi_fini WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) { $row = $found; }
}

$pageTitle = ($id > 0 ? 'Modifier' : 'Ajouter') . ' - PF Semi-fini-matic';
require_once __DIR__ . '/includes/header.php';
?>

<h2><?= $id > 0 ? 'Modifier une non-conformité' : 'Ajouter une non-conformité' ?> &ndash; Semi-fini</h2>

<form class="form" method="post" action="/non_conforme/semi_fini_save.php">
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row">
        <div class="field">
            <label for="numero_article">N° article</label>
            <input type="text" id="numero_article" name="numero_article" required
                   value="<?= htmlspecialchars($row['numero_article']) ?>">
        </div>
        <div class="field" style="flex:2;">
            <label for="description_article">Description article</label>
            <input type="text" id="description_article" name="description_article" required
                   value="<?= htmlspecialchars($row['description_article']) ?>">
        </div>
    </div>

    <div class="row">
        <div class="field">
            <label for="quantite">Quantité</label>
            <input type="number" id="quantite" name="quantite" min="0" required
                   value="<?= htmlspecialchars((string) $row['quantite']) ?>">
        </div>
        <div class="field">
            <label for="nbr_palettes">Nbr de palettes</label>
            <input type="number" id="nbr_palettes" name="nbr_palettes" min="0" required
                   value="<?= htmlspecialchars((string) $row['nbr_palettes']) ?>">
        </div>
        <div class="field">
            <label for="poids_total_kg">Poids total (kg)</label>
            <input type="number" step="0.01" id="poids_total_kg" name="poids_total_kg" min="0" required
                   value="<?= htmlspecialchars((string) $row['poids_total_kg']) ?>">
        </div>
    </div>

    <div class="row">
        <div class="field">
            <label for="motif">Motif</label>
            <input type="text" id="motif" name="motif" value="<?= htmlspecialchars($row['motif'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="decision_sq">Décision SQ</label>
            <input type="text" id="decision_sq" name="decision_sq" value="<?= htmlspecialchars($row['decision_sq'] ?? '') ?>">
        </div>
    </div>

    <button type="submit" class="btn">Enregistrer</button>
    <a href="/non_conforme/semi_fini.php" class="btn btn-secondary">Annuler</a>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
