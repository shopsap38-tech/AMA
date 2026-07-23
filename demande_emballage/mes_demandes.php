<?php
require_once __DIR__ . '/config/config.php';
require_role(['demandeur', 'administrateur']);

$pageTitle = 'Mes demandes';
$role      = current_role();
$userId    = current_user()['id'];

// L'administrateur voit toutes les demandes, le demandeur uniquement les siennes.
if ($role === 'administrateur') {
    $stmt = $pdo->query(
        "SELECT d.*, u.nom AS demandeur_nom,
                (SELECT chemin FROM photos p WHERE p.demande_id = d.id ORDER BY p.id DESC LIMIT 1) AS photo
         FROM demandes d
         JOIN utilisateurs u ON u.id = d.demandeur_id
         ORDER BY d.cree_le DESC"
    );
    $demandes = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare(
        "SELECT d.*,
                (SELECT chemin FROM photos p WHERE p.demande_id = d.id ORDER BY p.id DESC LIMIT 1) AS photo
         FROM demandes d
         WHERE d.demandeur_id = ?
         ORDER BY d.cree_le DESC"
    );
    $stmt->execute([$userId]);
    $demandes = $stmt->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-list-check text-primary"></i> <?= $role === 'administrateur' ? 'Toutes les demandes' : 'Mes demandes' ?></h1>
    <a href="<?= e(BASE_URL) ?>/demande_form.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nouvelle demande
    </a>
</div>

<?php if (!$demandes): ?>
    <div class="alert alert-info">Aucune demande pour le moment.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($demandes as $d): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card shadow-sm h-100 demande-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h2 class="card-title mb-0"><?= e($d['nom_article']) ?></h2>
                            <span class="badge bg-<?= e(statut_badge($d['statut'])) ?>"><?= e(statut_label($d['statut'])) ?></span>
                        </div>
                        <p class="mb-1"><strong>Quantité :</strong> <?= (int) $d['quantite'] ?></p>
                        <?php if ($role === 'administrateur'): ?>
                            <p class="mb-1"><strong>Demandeur :</strong> <?= e($d['demandeur_nom']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($d['commentaire'])): ?>
                            <p class="mb-1 text-muted"><i class="bi bi-chat-left-text"></i> <?= e($d['commentaire']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($d['photo'])): ?>
                            <a href="<?= e(UPLOAD_URL . '/' . $d['photo']) ?>" target="_blank" rel="noopener">
                                <img src="<?= e(UPLOAD_URL . '/' . $d['photo']) ?>" alt="Photo de l'article" class="photo-thumb my-2">
                            </a>
                        <?php endif; ?>

                        <ul class="list-unstyled small text-muted mt-3 mb-0">
                            <li><i class="bi bi-clock"></i> Créée le <?= e(date('d/m/Y H:i', strtotime($d['cree_le']))) ?></li>
                            <?php if ($d['prepare_le']): ?>
                                <li><i class="bi bi-truck"></i> En route le <?= e(date('d/m/Y H:i', strtotime($d['prepare_le']))) ?></li>
                            <?php endif; ?>
                            <?php if ($d['cloture_le']): ?>
                                <li><i class="bi bi-check2-circle"></i> Clôturée le <?= e(date('d/m/Y H:i', strtotime($d['cloture_le']))) ?></li>
                            <?php endif; ?>
                            <?php if ($d['delai_minutes'] !== null): ?>
                                <li>
                                    <i class="bi bi-stopwatch"></i> Délai :
                                    <span class="badge bg-<?= $d['delai_minutes'] <= SLA_SEUIL ? 'success' : 'danger' ?>">
                                        <?= e(format_delai((int) $d['delai_minutes'])) ?>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
