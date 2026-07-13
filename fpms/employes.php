<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Employés';
$currentPage = 'employes';

$filtreType = $_GET['type'] ?? '';
$sql = 'SELECT * FROM employes';
$params = [];
if (in_array($filtreType, ['permanent', 'journalier'], true)) {
    $sql .= ' WHERE type = :type';
    $params[':type'] = $filtreType;
}
$sql .= ' ORDER BY type, nom, prenom';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employes = $stmt->fetchAll();

$s = stats_employes($pdo);

require_once __DIR__ . '/includes/header.php';
?>

<div class="section-head">
    <h2>Gestion des employés</h2>
    <a class="btn" href="/fpms/employe_form.php">+ Nouvel employé</a>
</div>

<?php if (isset($_GET['success'])): ?>
    <p class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></p>
<?php endif; ?>

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Total employés</div><div class="kpi-value"><?= $s['total'] ?></div></div>
    <div class="kpi-card blue"><div class="kpi-label">Permanents</div>
        <div class="kpi-value"><?= $s['permanent'] ?> <span class="kpi-unit">(<?= pct($s['permanent'], $s['total']) ?>%)</span></div></div>
    <div class="kpi-card orange"><div class="kpi-label">Journaliers</div>
        <div class="kpi-value"><?= $s['journalier'] ?> <span class="kpi-unit">(<?= pct($s['journalier'], $s['total']) ?>%)</span></div></div>
</div>

<form class="filters" method="get">
    <label>Statut
        <select name="type" onchange="this.form.submit()">
            <option value="">Tous</option>
            <option value="permanent" <?= $filtreType === 'permanent' ? 'selected' : '' ?>>Permanent</option>
            <option value="journalier" <?= $filtreType === 'journalier' ? 'selected' : '' ?>>Journalier</option>
        </select>
    </label>
    <noscript><button class="btn btn-light" type="submit">Filtrer</button></noscript>
</form>

<table class="table">
    <thead>
        <tr><th>Matricule</th><th>Nom</th><th>Prénom</th><th>Statut</th><th>Poste</th><th>Actif</th><th>Actions</th></tr>
    </thead>
    <tbody>
        <?php if (empty($employes)): ?>
            <tr><td colspan="7">Aucun employé enregistré.</td></tr>
        <?php endif; ?>
        <?php foreach ($employes as $e): ?>
            <tr>
                <td><strong><?= htmlspecialchars($e['matricule']) ?></strong></td>
                <td><?= htmlspecialchars($e['nom']) ?></td>
                <td><?= htmlspecialchars($e['prenom']) ?></td>
                <td><span class="emp-tag emp-<?= $e['type'] ?>"><?= type_employe_label($e['type']) ?></span></td>
                <td><?= htmlspecialchars($e['poste'] ?? '') ?></td>
                <td><?= $e['actif'] ? '<span class="badge badge-ok">Oui</span>' : '<span class="badge badge-danger">Non</span>' ?></td>
                <td class="actions">
                    <a href="/fpms/employe_form.php?id=<?= (int) $e['id'] ?>">Modifier</a>
                    <a class="danger" href="/fpms/employe_delete.php?id=<?= (int) $e['id'] ?>"
                       onclick="return confirm('Supprimer cet employé ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
