<?php
use App\Core\View;
View::extends('layouts.app');
/** @var array|null $user */
/** @var array $roles */
/** @var array $departments */
$isEdit = $user !== null;
$action = $isEdit ? url('users/' . $user['id']) : url('users');
$val = static fn (string $k, $d = '') => e(old($k, $user[$k] ?? $d));
?>
<?php View::section('content'); ?>
<div class="page-head">
    <div>
        <p class="page-eyebrow"><a href="<?= e(url('users')) ?>">Utilisateurs</a> · <?= $isEdit ? 'Modification' : 'Création' ?></p>
        <h2 class="page-title"><?= $isEdit ? e($user['first_name'].' '.$user['last_name']) : 'Nouvel utilisateur' ?></h2>
    </div>
</div>

<form method="post" action="<?= e($action) ?>" class="row g-3">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <div class="col-lg-8">
        <div class="form-card"><div class="card-section">
            <div class="section-title"><i class="fa-solid fa-id-card"></i> Informations</div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Prénom *</label><input name="first_name" class="form-control" value="<?= $val('first_name') ?>" required>
                    <?php foreach (errors_for('first_name') as $er): ?><div class="text-danger small"><?= e($er) ?></div><?php endforeach; ?></div>
                <div class="col-md-6"><label class="form-label">Nom *</label><input name="last_name" class="form-control" value="<?= $val('last_name') ?>" required>
                    <?php foreach (errors_for('last_name') as $er): ?><div class="text-danger small"><?= e($er) ?></div><?php endforeach; ?></div>
                <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= $val('email') ?>" required>
                    <?php foreach (errors_for('email') as $er): ?><div class="text-danger small"><?= e($er) ?></div><?php endforeach; ?></div>
                <div class="col-md-6"><label class="form-label">Mot de passe <?= $isEdit ? '(laisser vide pour conserver)' : '*' ?></label><input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
                    <?php foreach (errors_for('password') as $er): ?><div class="text-danger small"><?= e($er) ?></div><?php endforeach; ?></div>
                <div class="col-md-6"><label class="form-label">Fonction</label><input name="job_title" class="form-control" value="<?= $val('job_title') ?>"></div>
                <div class="col-md-6"><label class="form-label">Téléphone</label><input name="phone" class="form-control" value="<?= $val('phone') ?>"></div>
            </div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="form-card">
            <div class="card-section">
                <div class="section-title"><i class="fa-solid fa-user-shield"></i> Rôle &amp; accès</div>
                <div class="mb-3"><label class="form-label">Rôle *</label>
                    <select name="role_id" class="form-select" required>
                        <?php foreach ($roles as $r): ?><option value="<?= (int)$r['id'] ?>" <?= (string)old('role_id', $user['role_id'] ?? '') === (string)$r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3"><label class="form-label">Service</label>
                    <select name="department_id" class="form-select"><option value="">—</option>
                        <?php foreach ($departments as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (string)old('department_id', $user['department_id'] ?? '') === (string)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php if ($isEdit): ?>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="active" <?= (int)($user['is_active'] ?? 1) === 1 ? 'checked' : '' ?>><label class="form-check-label" for="active">Compte actif</label></div>
                <?php endif; ?>
            </div>
            <div class="card-section d-grid gap-2">
                <button class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk me-2"></i>Enregistrer</button>
                <a href="<?= e(url('users')) ?>" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </div>
    </div>
</form>
<?php View::endSection(); ?>
