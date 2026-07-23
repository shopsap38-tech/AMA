<?php
require_once __DIR__ . '/config/config.php';
require_role(['preparateur', 'administrateur']);

$pageTitle = 'Demandes à préparer';

$stmt = $pdo->query(
    "SELECT d.*, u.nom AS demandeur_nom
     FROM demandes d
     JOIN utilisateurs u ON u.id = d.demandeur_id
     WHERE d.statut = 'en_attente'
     ORDER BY d.cree_le ASC"
);
$demandes = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4"><i class="bi bi-hourglass-split text-primary"></i> Demandes à préparer
    <span class="badge bg-warning text-dark"><?= count($demandes) ?></span>
</h1>

<?php if (!$demandes): ?>
    <div class="alert alert-success"><i class="bi bi-check2-all"></i> Aucune demande en attente. Tout est à jour !</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($demandes as $d): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h2 class="h5 card-title mb-0"><?= e($d['nom_article']) ?></h2>
                            <span class="badge bg-warning text-dark">En attente</span>
                        </div>
                        <p class="mb-1"><strong>Quantité :</strong> <?= (int) $d['quantite'] ?></p>
                        <p class="mb-1"><strong>Demandeur :</strong> <?= e($d['demandeur_nom']) ?></p>
                        <?php if (!empty($d['commentaire'])): ?>
                            <p class="mb-1 text-muted"><i class="bi bi-chat-left-text"></i> <?= e($d['commentaire']) ?></p>
                        <?php endif; ?>
                        <p class="small text-muted"><i class="bi bi-clock"></i> Créée le <?= e(date('d/m/Y H:i', strtotime($d['cree_le']))) ?></p>

                        <form method="post" action="<?= e(BASE_URL) ?>/preparateur_save.php" enctype="multipart/form-data" class="mt-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="demande_id" value="<?= (int) $d['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold" for="photo_<?= (int) $d['id'] ?>">
                                    <i class="bi bi-camera"></i> Photo de l'article
                                </label>
                                <input type="file" class="form-control" id="photo_<?= (int) $d['id'] ?>"
                                       name="photo" accept="image/*" capture="environment">
                            </div>
                            <button type="submit" class="btn btn-info w-100">
                                <i class="bi bi-truck"></i> Marquer « En route »
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
