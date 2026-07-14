<?php
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = ['date_nc' => date('Y-m-d'), 'total_big_bag' => '', 'tonnage' => ''];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM nc_big_bag WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) { $row = $found; }
}

$pageTitle = ($id > 0 ? 'Modifier' : 'Ajouter') . ' - Big Bag';
require_once __DIR__ . '/includes/header.php';
?>

<h2><?= $id > 0 ? 'Modifier une non-conformité' : 'Ajouter une non-conformité' ?> &ndash; Big Bag</h2>

<form class="form" method="post" action="/non_conforme/big_bag_save.php">
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row">
        <div class="field">
            <label for="date_nc">Date</label>
            <input type="date" id="date_nc" name="date_nc" required value="<?= htmlspecialchars($row['date_nc']) ?>">
        </div>
        <div class="field">
            <label for="total_big_bag">Total big bags</label>
            <input type="number" id="total_big_bag" name="total_big_bag" min="0" required value="<?= htmlspecialchars((string) $row['total_big_bag']) ?>">
        </div>
        <div class="field">
            <label for="tonnage">Tonnage (kg)</label>
            <input type="number" step="0.01" id="tonnage" name="tonnage" min="0" required value="<?= htmlspecialchars((string) $row['tonnage']) ?>">
        </div>
    </div>

    <button type="submit" class="btn">Enregistrer</button>
    <a href="/non_conforme/big_bag.php" class="btn btn-secondary">Annuler</a>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
