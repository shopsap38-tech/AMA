<?php
require __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
require_permission($isEdit ? 'nonconformity.update' : 'nonconformity.create');

$nc = null;
if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM non_conformities WHERE id = ?');
    $stmt->execute([$id]);
    $nc = $stmt->fetch();
    if (!$nc) { flash_set('error', 'Non-conformité introuvable.'); redirect('nonconformites.php'); }
}

// Pré-remplissage après erreur de validation
$old = $_SESSION['_old'] ?? [];
unset($_SESSION['_old']);
$errors = $_SESSION['_errors'] ?? [];
unset($_SESSION['_errors']);
$val = fn (string $k, $d = '') => e($old[$k] ?? ($nc[$k] ?? $d));

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$users = $pdo->query('SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY last_name')->fetchAll();
$severities = ['critique', 'majeure', 'mineure'];
$origins = ['production', 'stock', 'reception', 'expedition', 'client', 'fournisseur'];

$pageTitle = $isEdit ? 'Modifier ' . $nc['reference'] : 'Déclarer une non-conformité';
$activeMenu = 'nonconformites';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div><p class="page-eyebrow"><a href="nonconformites.php">Non-conformités</a> · <?= $isEdit ? 'Modification' : 'Nouvelle fiche' ?></p>
    <h2 class="page-title"><?= $isEdit ? e($nc['reference']) : 'Déclarer une non-conformité' ?></h2></div>
</div>

<form method="post" action="nc_save.php">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $nc['id'] ?>"><?php endif; ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="card-section">
                    <div class="section-title"><i class="fa-solid fa-circle-info"></i> Identification</div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Date <span class="text-danger">*</span></label><input type="date" name="occurred_on" class="form-control" value="<?= $val('occurred_on', date('Y-m-d')) ?>" required></div>
                        <div class="col-md-4"><label class="form-label">Heure</label><input type="time" name="occurred_at" class="form-control" value="<?= $val('occurred_at') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Service <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select" required><option value="">— Sélectionner —</option>
                                <?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>" <?= (string) ($old['department_id'] ?? $nc['department_id'] ?? '') === (string) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Atelier</label><input type="text" name="workshop" class="form-control" value="<?= $val('workshop') ?>" placeholder="Ex. Atelier A"></div>
                        <div class="col-md-4"><label class="form-label">Emplacement</label><input type="text" name="location" class="form-control" value="<?= $val('location') ?>" placeholder="Ex. Ligne 1"></div>
                        <div class="col-md-4"><label class="form-label">Origine <span class="text-danger">*</span></label>
                            <select name="origin" class="form-select" required><option value="">— Sélectionner —</option>
                                <?php foreach ($origins as $o): ?><option value="<?= $o ?>" <?= ($old['origin'] ?? $nc['origin'] ?? '') === $o ? 'selected' : '' ?>><?= e(ui_label('origin', $o)) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-section">
                    <div class="section-title"><i class="fa-solid fa-box"></i> Produit concerné</div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Produit <span class="text-danger">*</span></label><input type="text" name="product" class="form-control" value="<?= $val('product') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Référence</label><input type="text" name="product_reference" class="form-control" value="<?= $val('product_reference') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Lot</label><input type="text" name="batch" class="form-control" value="<?= $val('batch') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Quantité</label><input type="number" step="0.01" name="quantity" class="form-control" value="<?= $val('quantity') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Gravité <span class="text-danger">*</span></label>
                            <select name="severity" class="form-select" required><option value="">— Sélectionner —</option>
                                <?php foreach ($severities as $s): ?><option value="<?= $s ?>" <?= ($old['severity'] ?? $nc['severity'] ?? '') === $s ? 'selected' : '' ?>><?= e(ui_label('severity', $s)) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-section">
                    <div class="section-title"><i class="fa-solid fa-file-lines"></i> Analyse</div>
                    <div class="mb-3"><label class="form-label">Description <span class="text-danger">*</span></label><textarea name="description" class="form-control" rows="3" required placeholder="Décrivez la non-conformité constatée…"><?= $val('description') ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Observation</label><textarea name="observation" class="form-control" rows="2"><?= $val('observation') ?></textarea></div>
                    <div class="mb-3"><label class="form-label">Analyse des causes</label><textarea name="root_cause_analysis" class="form-control" rows="2" placeholder="5 pourquoi, Ishikawa…"><?= $val('root_cause_analysis') ?></textarea></div>
                    <div class="mb-0"><label class="form-label">Impact</label><textarea name="impact" class="form-control" rows="2"><?= $val('impact') ?></textarea></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="form-card">
                <div class="card-section">
                    <div class="section-title"><i class="fa-solid fa-user-gear"></i> Affectation</div>
                    <div class="mb-3"><label class="form-label">Responsable</label>
                        <select name="responsible_id" class="form-select"><option value="">— Non assigné —</option>
                            <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (string) ($old['responsible_id'] ?? $nc['responsible_id'] ?? '') === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['first_name'] . ' ' . $u['last_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!$isEdit): ?>
                        <div class="prose-block" style="background:var(--brand-soft);border-color:transparent;color:var(--brand)"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Un numéro <strong>NC-<?= date('Y') ?>-XXXXXX</strong> et un circuit de validation à 5 niveaux seront générés automatiquement.</div>
                    <?php else: ?>
                        <div class="info-item"><label>Statut actuel</label><span class="badge <?= e(ui_badge('status', $nc['status'])) ?>"><?= e(ui_label('status', $nc['status'])) ?></span></div>
                    <?php endif; ?>
                </div>
                <div class="card-section d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-2"></i><?= $isEdit ? 'Enregistrer' : 'Créer la fiche' ?></button>
                    <a href="<?= $isEdit ? 'nc_show.php?id=' . (int) $nc['id'] : 'nonconformites.php' ?>" class="btn btn-outline-secondary">Annuler</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
