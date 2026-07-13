<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Fiche chariot';
$currentPage = 'chariots';

$chariot = [
    'id' => '', 'code' => '', 'marque' => '',
    'type' => 'electrique', 'etat' => 'disponible', 'unite' => '', 'date_mise_service' => '',
];

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM chariots WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $row = $stmt->fetch();
    if ($row) {
        $chariot = $row;
    }
}

$isEdit = !empty($chariot['id']);
require_once __DIR__ . '/includes/header.php';
?>

<h2><?= $isEdit ? 'Modifier le chariot' : 'Nouveau chariot' ?></h2>

<?php if (isset($_GET['error'])): ?>
    <p class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></p>
<?php endif; ?>

<form class="form" method="post" action="/fpms/chariot_save.php">
    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $chariot['id']) ?>">

    <label>Code chariot *
        <input type="text" name="code" required value="<?= htmlspecialchars($chariot['code']) ?>" placeholder="CH-001">
    </label>

    <label>Marque *
        <input type="text" name="marque" required value="<?= htmlspecialchars($chariot['marque']) ?>">
    </label>

    <label>Type *
        <select name="type">
            <option value="electrique" <?= $chariot['type'] === 'electrique' ? 'selected' : '' ?>>Électrique</option>
            <option value="diesel" <?= $chariot['type'] === 'diesel' ? 'selected' : '' ?>>Diesel</option>
        </select>
    </label>

    <label>État *
        <select name="etat">
            <option value="disponible" <?= $chariot['etat'] === 'disponible' ? 'selected' : '' ?>>Disponible</option>
            <option value="maintenance" <?= $chariot['etat'] === 'maintenance' ? 'selected' : '' ?>>En maintenance</option>
            <option value="panne" <?= $chariot['etat'] === 'panne' ? 'selected' : '' ?>>En panne</option>
        </select>
    </label>
    <?php if ($isEdit): ?>
        <p class="hint">Tout changement d'état sera enregistré dans l'historique.</p>
    <?php endif; ?>

    <label>Unité
        <select name="unite">
            <option value="">— Aucune —</option>
            <?php foreach (unites() as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($chariot['unite'] ?? '') === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Date de mise en service
        <input type="date" name="date_mise_service" value="<?= htmlspecialchars($chariot['date_mise_service'] ?? '') ?>">
    </label>

    <button type="submit"><?= $isEdit ? 'Enregistrer' : 'Ajouter' ?></button>
</form>

<p style="margin-top:1rem"><a href="/fpms/chariots.php">&larr; Retour à la liste</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
