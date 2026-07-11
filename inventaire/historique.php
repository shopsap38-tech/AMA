<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Historique des mouvements';

$stmt = $pdo->query('
    SELECT m.*, p.nom AS produit_nom
    FROM mouvements_stock m
    INNER JOIN produits p ON p.id = m.produit_id
    ORDER BY m.date_mouvement DESC
    LIMIT 200
');
$mouvements = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<h2>Historique des mouvements de stock</h2>

<table class="table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Produit</th>
            <th>Type</th>
            <th>Quantité</th>
            <th>Motif</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($mouvements)): ?>
            <tr><td colspan="5">Aucun mouvement enregistré pour le moment.</td></tr>
        <?php endif; ?>
        <?php foreach ($mouvements as $mouvement): ?>
            <tr>
                <td><?= htmlspecialchars($mouvement['date_mouvement']) ?></td>
                <td><?= htmlspecialchars($mouvement['produit_nom']) ?></td>
                <td>
                    <?php if ($mouvement['type'] === 'entree'): ?>
                        <span class="badge badge-ok">Entrée</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Sortie</span>
                    <?php endif; ?>
                </td>
                <td><?= (int) $mouvement['quantite'] ?></td>
                <td><?= htmlspecialchars($mouvement['motif'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
