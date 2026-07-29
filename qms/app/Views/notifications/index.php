<?php
use App\Core\View;
View::extends('layouts.app');
/** @var array $notifications */
?>
<?php View::section('content'); ?>
<div class="page-head">
    <div>
        <p class="page-eyebrow">Centre de notifications</p>
        <h2 class="page-title">Notifications</h2>
    </div>
    <div class="page-head-actions">
        <form method="post" action="<?= e(url('notifications/read-all')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-outline-secondary"><i class="fa-solid fa-check-double me-2"></i>Tout marquer comme lu</button>
        </form>
    </div>
</div>

<div class="panel"><div class="panel-body p-0">
    <?php foreach ($notifications as $n): ?>
        <div class="notif-item <?= (int)$n['is_read']===0 ? 'unread':'' ?>" style="border-radius:0">
            <div class="notif-ico"><i class="fa-solid <?= (int)$n['is_read']===0 ? 'fa-bell':'fa-bell-slash' ?>"></i></div>
            <div class="notif-body flex-fill">
                <strong><?= e($n['title']) ?></strong>
                <span><?= e($n['message']) ?></span>
                <time><?= e(format_date($n['created_at'], 'd/m/Y H:i')) ?></time>
            </div>
            <?php if ($n['link']): ?><a href="<?= e(url($n['link'])) ?>" class="btn-icon"><i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if ($notifications === []): ?><div class="empty-state"><i class="fa-solid fa-bell-slash"></i><p>Aucune notification.</p></div><?php endif; ?>
</div></div>
<?php View::endSection(); ?>
