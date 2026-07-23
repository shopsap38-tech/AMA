<?php
require_once __DIR__ . '/config/config.php';
require_role(['facturation', 'administrateur']);

$pageTitle = 'Demandes à facturer';

$stmt = $pdo->query(
    "SELECT d.*, u.nom AS demandeur_nom, p.nom AS preparateur_nom,
            (SELECT chemin FROM photos ph WHERE ph.demande_id = d.id ORDER BY ph.id DESC LIMIT 1) AS photo
     FROM demandes d
     JOIN utilisateurs u ON u.id = d.demandeur_id
     LEFT JOIN utilisateurs p ON p.id = d.preparateur_id
     WHERE d.statut = 'en_route'
     ORDER BY d.prepare_le ASC"
);
$demandes = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4"><i class="bi bi-receipt text-primary"></i> Demandes à facturer
    <span class="badge bg-info"><?= count($demandes) ?></span>
</h1>

<?php if (!$demandes): ?>
    <div class="alert alert-success"><i class="bi bi-check2-all"></i> Aucune demande en route à traiter.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($demandes as $d): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card shadow-sm h-100">
                    <?php if (!empty($d['photo'])): ?>
                        <a href="<?= e(UPLOAD_URL . '/' . $d['photo']) ?>" target="_blank" rel="noopener">
                            <img src="<?= e(UPLOAD_URL . '/' . $d['photo']) ?>" alt="Photo de l'article"
                                 class="card-img-top" style="max-height:220px;object-fit:cover;">
                        </a>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h2 class="h5 card-title mb-0"><?= e($d['nom_article']) ?></h2>
                            <span class="badge bg-info">En route</span>
                        </div>
                        <p class="mb-1"><strong>Quantité :</strong> <?= (int) $d['quantite'] ?></p>
                        <p class="mb-1"><strong>Demandeur :</strong> <?= e($d['demandeur_nom']) ?></p>
                        <?php if (!empty($d['preparateur_nom'])): ?>
                            <p class="mb-1"><strong>Préparé par :</strong> <?= e($d['preparateur_nom']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($d['commentaire'])): ?>
                            <p class="mb-1 text-muted"><i class="bi bi-chat-left-text"></i> <?= e($d['commentaire']) ?></p>
                        <?php endif; ?>
                        <ul class="list-unstyled small text-muted">
                            <li><i class="bi bi-clock"></i> Créée le <?= e(date('d/m/Y H:i', strtotime($d['cree_le']))) ?></li>
                            <?php if ($d['prepare_le']): ?>
                                <li><i class="bi bi-truck"></i> En route le <?= e(date('d/m/Y H:i', strtotime($d['prepare_le']))) ?></li>
                            <?php endif; ?>
                        </ul>

                        <form method="post" action="<?= e(BASE_URL) ?>/facturation_save.php" class="mt-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="demande_id" value="<?= (int) $d['id'] ?>">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-check2-circle"></i> Confirmer « C'est fait »
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
