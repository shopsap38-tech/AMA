<?php
use App\Core\View;
View::extends('layouts.app');
/** @var array $users */
?>
<?php View::section('content'); ?>
<div class="page-head">
    <div>
        <p class="page-eyebrow">Administration</p>
        <h2 class="page-title">Utilisateurs</h2>
    </div>
    <div class="page-head-actions">
        <a href="<?= e(url('users/create')) ?>" class="btn btn-primary"><i class="fa-solid fa-user-plus me-2"></i>Nouvel utilisateur</a>
    </div>
</div>

<div class="panel"><div class="panel-body">
    <table class="table js-datatable" style="width:100%">
        <thead><tr><th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Service</th><th>Statut</th><th>Dernière connexion</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar" style="width:32px;height:32px;font-size:11px"><?= e(strtoupper(mb_substr($u['first_name'],0,1).mb_substr($u['last_name'],0,1))) ?></span>
                        <div><strong><?= e($u['first_name'].' '.$u['last_name']) ?></strong><br><small class="text-muted"><?= e($u['job_title'] ?? '') ?></small></div>
                    </div>
                </td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge badge-info"><?= e($u['role_name']) ?></span></td>
                <td><?= e($u['department_name'] ?? '—') ?></td>
                <td><?php if ((int)$u['is_active']===1): ?><span class="badge badge-closed">Actif</span><?php else: ?><span class="badge badge-muted">Inactif</span><?php endif; ?></td>
                <td class="text-muted small"><?= $u['last_login_at'] ? e(format_date($u['last_login_at'], 'd/m/Y H:i')) : 'Jamais' ?></td>
                <td class="text-end"><a href="<?= e(url('users/'.$u['id'].'/edit')) ?>" class="btn-icon"><i class="fa-solid fa-pen"></i></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
<?php View::endSection(); ?>
