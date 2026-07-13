<?php
require_once __DIR__ . '/config/database.php';

$pageTitle   = 'FPMS - Fiche employé';
$currentPage = 'employes';

$emp = ['id' => '', 'matricule' => '', 'nom' => '', 'prenom' => '', 'type' => 'permanent', 'poste' => '', 'actif' => 1];

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM employes WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $row = $stmt->fetch();
    if ($row) {
        $emp = $row;
    }
}

$isEdit = !empty($emp['id']);
require_once __DIR__ . '/includes/header.php';
?>

<h2><?= $isEdit ? 'Modifier l\'employé' : 'Nouvel employé' ?></h2>

<?php if (isset($_GET['error'])): ?>
    <p class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></p>
<?php endif; ?>

<form class="form" method="post" action="/fpms/employe_save.php">
    <input type="hidden" name="id" value="<?= htmlspecialchars((string) $emp['id']) ?>">

    <label>Matricule *
        <input type="text" name="matricule" required value="<?= htmlspecialchars($emp['matricule']) ?>" placeholder="EMP-001">
    </label>
    <label>Nom *
        <input type="text" name="nom" required value="<?= htmlspecialchars($emp['nom']) ?>">
    </label>
    <label>Prénom *
        <input type="text" name="prenom" required value="<?= htmlspecialchars($emp['prenom']) ?>">
    </label>
    <label>Statut *
        <select name="type">
            <option value="permanent" <?= $emp['type'] === 'permanent' ? 'selected' : '' ?>>Permanent</option>
            <option value="journalier" <?= $emp['type'] === 'journalier' ? 'selected' : '' ?>>Journalier</option>
        </select>
    </label>
    <label>Poste
        <input type="text" name="poste" value="<?= htmlspecialchars($emp['poste'] ?? '') ?>" placeholder="Cariste, Contrôleur qualité…">
    </label>
    <label>Actif
        <select name="actif">
            <option value="1" <?= (int) $emp['actif'] === 1 ? 'selected' : '' ?>>Oui</option>
            <option value="0" <?= (int) $emp['actif'] === 0 ? 'selected' : '' ?>>Non</option>
        </select>
    </label>

    <button type="submit"><?= $isEdit ? 'Enregistrer' : 'Ajouter' ?></button>
</form>

<p style="margin-top:1rem"><a href="/fpms/employes.php">&larr; Retour à la liste</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
