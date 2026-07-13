<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Fiche palette';
$currentPage = 'palettes';

$palette = ['id' => '', 'code' => '', 'etat' => 'conforme', 'commentaire' => '', 'controleur_id' => ''];
$employes = employes_actifs($pdo);

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM palettes WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $row = $stmt->fetch();
    if ($row) {
        $palette = $row;
    }
}

$isEdit = !empty($palette['id']);
require_once __DIR__ . '/includes/header.php';
?>

<h2><?= $isEdit ? 'Modifier la palette' : 'Nouvelle palette' ?></h2>

<?php if (isset($_GET['error'])): ?>
    <p class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></p>
<?php endif; ?>

<form class="form" method="post" action="/fpms/palette_save.php">
    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $palette['id']) ?>">

    <label>Code palette *
        <input type="text" name="code" required value="<?= htmlspecialchars($palette['code']) ?>" placeholder="PAL-0001">
    </label>

    <label>État *
        <select name="etat">
            <option value="conforme" <?= $palette['etat'] === 'conforme' ? 'selected' : '' ?>>Conforme</option>
            <option value="non_conforme" <?= $palette['etat'] === 'non_conforme' ? 'selected' : '' ?>>Non conforme</option>
            <option value="cassee" <?= $palette['etat'] === 'cassee' ? 'selected' : '' ?>>Cassée</option>
        </select>
    </label>

    <label>Contrôleur
        <select name="controleur_id">
            <option value="">— Aucun —</option>
            <?php foreach ($employes as $emp): ?>
                <option value="<?= (int) $emp['id'] ?>" <?= (int) $palette['controleur_id'] === (int) $emp['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($emp['prenom'] . ' ' . $emp['nom']) ?> — <?= type_employe_label($emp['type']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Commentaire
        <textarea name="commentaire" rows="3"><?= htmlspecialchars($palette['commentaire'] ?? '') ?></textarea>
    </label>

    <button type="submit"><?= $isEdit ? 'Enregistrer' : 'Ajouter' ?></button>
</form>

<p style="margin-top:1rem"><a href="/fpms/palettes.php">&larr; Retour à la liste</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
