<?php
require_once __DIR__ . '/config/config.php';
require_role(['demandeur', 'administrateur']);

$pageTitle = 'Nouvelle demande';

// Suggestions d'articles déjà saisis
$articles = $pdo->query('SELECT nom FROM articles ORDER BY nom')->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <h1 class="h3 mb-4"><i class="bi bi-plus-circle text-primary"></i> Nouvelle demande d'emballage</h1>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" action="<?= e(BASE_URL) ?>/demande_save.php" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="nom_article" class="form-label">Nom de l'article <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-lg" id="nom_article" name="nom_article"
                               list="articles_list" maxlength="190" required autofocus
                               placeholder="Ex. Carton 60×40, Film étirable…">
                        <datalist id="articles_list">
                            <?php foreach ($articles as $a): ?>
                                <option value="<?= e($a) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label for="quantite" class="form-label">Quantité demandée <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" id="quantite" name="quantite"
                               min="1" step="1" required inputmode="numeric" placeholder="Ex. 10">
                    </div>

                    <div class="mb-4">
                        <label for="commentaire" class="form-label">Commentaire <span class="text-muted">(optionnel)</span></label>
                        <textarea class="form-control" id="commentaire" name="commentaire" rows="3"
                                  placeholder="Précisions éventuelles…"></textarea>
                    </div>

                    <div class="d-grid d-sm-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-send"></i> Valider la demande
                        </button>
                        <a href="<?= e(BASE_URL) ?>/mes_demandes.php" class="btn btn-outline-secondary btn-lg">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
