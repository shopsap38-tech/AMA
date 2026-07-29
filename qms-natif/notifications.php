<?php
require __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
$activeMenu = 'notifications';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div><p class="page-eyebrow">Centre de notifications</p><h2 class="page-title">Notifications</h2></div>
    <div class="page-head-actions">
        <form method="post" action="notification_read.php">
            <?= csrf_field() ?><input type="hidden" name="all" value="1">
            <button class="btn btn-outline-secondary"><i class="fa-solid fa-check-double me-2"></i>Tout marquer comme lu</button>
        </form>
    </div>
</div>
<div class="panel"><div class="panel-body p-0">
    <?php foreach ($notifications as $n): ?>
        <div class="notif-item <?= (int) $n['is_read'] === 0 ? 'unread' : '' ?>" style="border-radius:0">
            <div class="notif-ico"><i class="fa-solid <?= (int) $n['is_read'] === 0 ? 'fa-bell' : 'fa-bell-slash' ?>"></i></div>
            <div class="notif-body flex-fill"><strong><?= e($n['title']) ?></strong><span><?= e($n['message']) ?></span><time><?= e(format_date($n['created_at'], 'd/m/Y H:i')) ?></time></div>
            <?php if ($n['link']): ?><a href="<?= e($n['link']) ?>" class="btn-icon"><i class="fa-solid fa-arrow-right"></i></a><?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if ($notifications === []): ?><div class="empty-state"><i class="fa-solid fa-bell-slash"></i><p>Aucune notification.</p></div><?php endif; ?>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
