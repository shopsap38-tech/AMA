<?php
require_once __DIR__ . '/../config/config.php';
require_role(['administrateur']);

$id   = (int) ($_GET['id'] ?? 0);
$user = ['id' => 0, 'nom' => '', 'email' => '', 'role' => 'demandeur', 'actif' => 1];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        set_flash('danger', 'Utilisateur introuvable.');
        redirect('/users/index.php');
    }
    $user = $found;
}

$pageTitle = $id > 0 ? 'Modifier un utilisateur' : 'Nouvel utilisateur';
require __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <h1 class="h3 mb-4"><i class="bi bi-person-gear text-primary"></i> <?= e($pageTitle) ?></h1>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" action="<?= e(BASE_URL) ?>/users/save.php" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">

                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom complet <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nom" name="nom" required
                               value="<?= e($user['nom']) ?>" maxlength="150">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse e-mail <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= e($user['email']) ?>" maxlength="190">
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <?php foreach ($GLOBALS['ROLES'] as $key => $label): ?>
                                <option value="<?= e($key) ?>" <?= $user['role'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Mot de passe <?= $id > 0 ? '<span class="text-muted">(laisser vide pour ne pas changer)</span>' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password"
                               autocomplete="new-password" <?= $id > 0 ? '' : 'required' ?>>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="actif" name="actif" value="1"
                               <?= $user['actif'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="actif">Compte actif</label>
                    </div>

                    <div class="d-grid d-sm-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                        <a href="<?= e(BASE_URL) ?>/users/index.php" class="btn btn-outline-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
