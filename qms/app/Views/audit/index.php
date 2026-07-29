<?php
use App\Core\View;
View::extends('layouts.app');
/** @var array $logs */
/** @var array $filters */
/** @var array $users */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
$f = $filters;
$pages = (int) ceil($total / $perPage);
$qs = static function (array $extra) use ($f): string {
    return http_build_query(array_merge(array_filter($f), $extra));
};
?>
<?php View::section('content'); ?>
<div class="page-head">
    <div>
        <p class="page-eyebrow">Sécurité &amp; conformité</p>
        <h2 class="page-title">Journal d'audit <span class="text-muted" style="font-size:15px">· <?= (int)$total ?> événements</span></h2>
    </div>
</div>

<form class="filter-bar row g-2" method="get" action="<?= e(url('audit')) ?>">
    <div class="col-md-3">
        <select name="entity_type" class="form-select">
            <option value="">Toutes entités</option>
            <?php foreach (['non_conformity'=>'Non-conformité','corrective_action'=>'Action corrective','user'=>'Utilisateur','auth'=>'Authentification','attachment'=>'Pièce jointe'] as $k=>$lbl): ?>
                <option value="<?= $k ?>" <?= ($f['entity_type'] ?? '')===$k ? 'selected':'' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="user_id" class="form-select"><option value="">Tous utilisateurs</option>
            <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= (string)($f['user_id'] ?? '')===(string)$u['id'] ? 'selected':'' ?>><?= e($u['first_name'].' '.$u['last_name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="<?= e($f['date_from'] ?? '') ?>"></div>
    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="<?= e($f['date_to'] ?? '') ?>"></div>
    <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary flex-fill"><i class="fa-solid fa-magnifying-glass"></i></button>
        <a href="<?= e(url('audit')) ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
</form>

<div class="panel"><div class="panel-body p-0">
    <table class="table-clean" style="width:100%">
        <thead><tr><th>Horodatage</th><th>Utilisateur</th><th>Action</th><th>Entité</th><th>IP</th><th>Navigateur</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td class="text-nowrap"><?= e(format_date($log['created_at'], 'd/m/Y H:i:s')) ?></td>
                <td><?= e($log['user_name'] ?: 'Système') ?></td>
                <td><span class="badge badge-info"><?= e($log['action']) ?></span></td>
                <td><?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #'.(int)$log['entity_id'] : '' ?></td>
                <td style="font-family:monospace;font-size:12px"><?= e($log['ip_address']) ?></td>
                <td class="text-muted small" style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($log['user_agent']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($logs === []): ?><tr><td colspan="6" class="text-center text-muted py-4">Aucun événement</td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>

<?php if ($pages > 1): ?>
<div class="pagination-bar">
    <span>Page <?= $page ?> / <?= $pages ?></span>
    <div class="page-btns">
        <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="?<?= e($qs(['page'=>$page-1])) ?>">‹</a>
        <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
            <a class="<?= $i===$page ? 'active':'' ?>" href="?<?= e($qs(['page'=>$i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
        <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="?<?= e($qs(['page'=>$page+1])) ?>">›</a>
    </div>
</div>
<?php endif; ?>
<?php View::endSection(); ?>
