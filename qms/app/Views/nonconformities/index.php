<?php
use App\Core\View;
View::extends('layouts.app');
/** @var array $items */
/** @var array $filters */
/** @var array $departments */
/** @var array $users */
/** @var array $severities */
/** @var array $origins */
/** @var array $statuses */
$f = $filters;
?>
<?php View::section('content'); ?>

<div class="page-head">
    <div>
        <p class="page-eyebrow">Module Qualité</p>
        <h2 class="page-title">Non-conformités <span class="text-muted" style="font-size:16px;font-weight:500">· <?= (int) $total ?></span></h2>
    </div>
    <div class="page-head-actions">
        <a href="<?= e(url('reports')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-file-export me-2"></i>Exporter</a>
        <?php if (auth()->can('nonconformity.create')): ?>
            <a href="<?= e(url('nonconformities/create')) ?>" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Déclarer une NC</a>
        <?php endif; ?>
    </div>
</div>

<!-- Recherche multicritère -->
<form class="filter-bar" method="get" action="<?= e(url('nonconformities')) ?>">
    <div class="row g-2">
        <div class="col-lg-3 col-md-6">
            <input type="text" name="keyword" class="form-control" placeholder="Réf, produit, lot, description…" value="<?= e($f['keyword'] ?? '') ?>">
        </div>
        <div class="col-lg-2 col-md-6">
            <select name="severity" class="form-select">
                <option value="">Toutes gravités</option>
                <?php foreach ($severities as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($f['severity'] ?? '') === $s ? 'selected' : '' ?>><?= e(ui_label('severity', $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <select name="status" class="form-select">
                <option value="">Tous statuts</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= e($s) ?>" <?= ($f['status'] ?? '') === $s ? 'selected' : '' ?>><?= e(ui_label('status', $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <select name="origin" class="form-select">
                <option value="">Toutes origines</option>
                <?php foreach ($origins as $o): ?>
                    <option value="<?= e($o) ?>" <?= ($f['origin'] ?? '') === $o ? 'selected' : '' ?>><?= e(ui_label('origin', $o)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 col-md-6">
            <select name="department_id" class="form-select">
                <option value="">Tous services</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= (string) ($f['department_id'] ?? '') === (string) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-3 col-md-6">
            <select name="responsible_id" class="form-select">
                <option value="">Tous responsables</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int) $u['id'] ?>" <?= (string) ($f['responsible_id'] ?? '') === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['first_name'] . ' ' . $u['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <input type="date" name="date_from" class="form-control" value="<?= e($f['date_from'] ?? '') ?>" title="Du">
        </div>
        <div class="col-lg-2 col-md-6">
            <input type="date" name="date_to" class="form-control" value="<?= e($f['date_to'] ?? '') ?>" title="Au">
        </div>
        <div class="col-lg-5 col-md-12 d-flex gap-2">
            <button class="btn btn-primary flex-fill"><i class="fa-solid fa-magnifying-glass me-2"></i>Filtrer</button>
            <a href="<?= e(url('nonconformities')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </div>
</form>

<div class="panel">
    <div class="panel-body">
        <?php if ($items === []): ?>
            <div class="empty-state">
                <i class="fa-solid fa-clipboard-check"></i>
                <p>Aucune non-conformité ne correspond à ces critères.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table js-datatable" style="width:100%">
                <thead>
                    <tr>
                        <th>Référence</th><th>Date</th><th>Produit</th><th>Service</th>
                        <th>Origine</th><th>Gravité</th><th>Statut</th><th>Responsable</th><th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $nc): ?>
                    <tr>
                        <td><span class="ref-tag"><?= e($nc['reference']) ?></span></td>
                        <td data-order="<?= e($nc['occurred_on']) ?>"><?= e(format_date($nc['occurred_on'])) ?></td>
                        <td><?= e($nc['product']) ?></td>
                        <td><?= e($nc['department_name'] ?? '—') ?></td>
                        <td><?= e(ui_label('origin', $nc['origin'])) ?></td>
                        <td><span class="badge <?= e(ui_badge('severity', $nc['severity'])) ?>"><?= e(ui_label('severity', $nc['severity'])) ?></span></td>
                        <td><span class="badge <?= e(ui_badge('status', $nc['status'])) ?>"><?= e(ui_label('status', $nc['status'])) ?></span></td>
                        <td><?= e($nc['responsible_name'] ?: '—') ?></td>
                        <td class="text-end">
                            <a href="<?= e(url('nonconformities/' . $nc['id'])) ?>" class="btn-icon" title="Consulter"><i class="fa-solid fa-eye"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php View::endSection(); ?>
