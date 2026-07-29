<?php
require __DIR__ . '/includes/functions.php';
require_permission('audit.view');

$f = [
    'entity_type' => $_GET['entity_type'] ?? '',
    'user_id'     => $_GET['user_id'] ?? '',
    'date_from'   => $_GET['date_from'] ?? '',
    'date_to'     => $_GET['date_to'] ?? '',
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;

$where = [];
$params = [];
if ($f['entity_type'] !== '') { $where[] = 'a.entity_type = :et'; $params[':et'] = $f['entity_type']; }
if ($f['user_id'] !== '') { $where[] = 'a.user_id = :uid'; $params[':uid'] = (int) $f['user_id']; }
if ($f['date_from'] !== '') { $where[] = 'a.created_at >= :df'; $params[':df'] = $f['date_from'] . ' 00:00:00'; }
if ($f['date_to'] !== '') { $where[] = 'a.created_at <= :dt'; $params[':dt'] = $f['date_to'] . ' 23:59:59'; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a{$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = (int) ceil($total / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT a.*, CONCAT(u.first_name,' ',u.last_name) AS user_name
     FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
     {$whereSql} ORDER BY a.id DESC LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$users = $pdo->query('SELECT id, first_name, last_name FROM users ORDER BY last_name')->fetchAll();
$qs = fn (array $extra) => http_build_query(array_merge(array_filter($f), $extra));

$pageTitle = "Journal d'audit";
$activeMenu = 'audit';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div><p class="page-eyebrow">Sécurité &amp; conformité</p><h2 class="page-title">Journal d'audit <span class="text-muted" style="font-size:15px">· <?= $total ?> événements</span></h2></div>
</div>
<form class="filter-bar row g-2" method="get" action="audit.php">
    <div class="col-md-3">
        <select name="entity_type" class="form-select"><option value="">Toutes entités</option>
            <?php foreach (['non_conformity' => 'Non-conformité', 'corrective_action' => 'Action corrective', 'user' => 'Utilisateur', 'auth' => 'Authentification', 'attachment' => 'Pièce jointe'] as $k => $lbl): ?>
                <option value="<?= $k ?>" <?= $f['entity_type'] === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <select name="user_id" class="form-select"><option value="">Tous utilisateurs</option>
            <?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (string) $f['user_id'] === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['first_name'] . ' ' . $u['last_name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="<?= e($f['date_from']) ?>"></div>
    <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="<?= e($f['date_to']) ?>"></div>
    <div class="col-md-2 d-flex gap-2"><button class="btn btn-primary flex-fill"><i class="fa-solid fa-magnifying-glass"></i></button><a href="audit.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a></div>
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
                <td><?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #' . (int) $log['entity_id'] : '' ?></td>
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
        <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="?<?= e($qs(['page' => $page - 1])) ?>">‹</a>
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?><a class="<?= $i === $page ? 'active' : '' ?>" href="?<?= e($qs(['page' => $i])) ?>"><?= $i ?></a><?php endfor; ?>
        <a class="<?= $page >= $pages ? 'disabled' : '' ?>" href="?<?= e($qs(['page' => $page + 1])) ?>">›</a>
    </div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
