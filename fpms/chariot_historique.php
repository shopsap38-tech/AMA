<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle   = 'FPMS - Historique chariot';
$currentPage = 'chariots';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM chariots WHERE id = ?');
$stmt->execute([$id]);
$chariot = $stmt->fetch();

if (!$chariot) {
    require_once __DIR__ . '/includes/header.php';
    echo '<p class="alert alert-error">Chariot introuvable.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$h = $pdo->prepare(
    'SELECT * FROM chariot_historique WHERE chariot_id = ? ORDER BY date_evenement DESC'
);
$h->execute([$id]);
$historique = $h->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h2>Historique — chariot #<?= (int) $chariot['id'] ?></h2>

<div class="card" style="margin-bottom:1.5rem">
    <p style="margin:0">
        <strong><?= $chariot['type'] === 'electrique' ? 'Électrique' : 'Diesel' ?></strong>
        &middot; Unité : <?= htmlspecialchars(unite_label($chariot['unite'])) ?>
        &middot; État actuel :
        <span class="badge <?= etat_chariot_badge($chariot['etat']) ?>"><?= etat_chariot_label($chariot['etat']) ?></span>
    </p>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Ancien état</th>
            <th>Nouvel état</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($historique)): ?>
            <tr><td colspan="3">Aucun événement enregistré.</td></tr>
        <?php endif; ?>
        <?php foreach ($historique as $evt): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($evt['date_evenement'])) ?></td>
                <td><?= $evt['ancien_etat'] ? etat_chariot_label($evt['ancien_etat']) : '—' ?></td>
                <td><span class="badge <?= etat_chariot_badge($evt['nouvel_etat']) ?>"><?= etat_chariot_label($evt['nouvel_etat']) ?></span></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p style="margin-top:1rem"><a href="/fpms/chariots.php">&larr; Retour à la liste</a></p>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
