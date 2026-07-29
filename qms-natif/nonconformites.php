<?php
require __DIR__ . '/includes/functions.php';
require_permission('nonconformity.view');

// --- Filtres multicritères ---
$where = [];
$params = [];
$f = [
    'keyword'        => trim($_GET['keyword'] ?? ''),
    'severity'       => $_GET['severity'] ?? '',
    'status'         => $_GET['status'] ?? '',
    'origin'         => $_GET['origin'] ?? '',
    'department_id'  => $_GET['department_id'] ?? '',
    'responsible_id' => $_GET['responsible_id'] ?? '',
    'date_from'      => $_GET['date_from'] ?? '',
    'date_to'        => $_GET['date_to'] ?? '',
];

if ($f['keyword'] !== '') { $where[] = '(nc.reference LIKE :kw OR nc.product LIKE :kw OR nc.description LIKE :kw OR nc.batch LIKE :kw)'; $params[':kw'] = '%' . $f['keyword'] . '%'; }
if ($f['severity'] !== '') { $where[] = 'nc.severity = :severity'; $params[':severity'] = $f['severity']; }
if ($f['status'] !== '') { $where[] = 'nc.status = :status'; $params[':status'] = $f['status']; }
if ($f['origin'] !== '') { $where[] = 'nc.origin = :origin'; $params[':origin'] = $f['origin']; }
if ($f['department_id'] !== '') { $where[] = 'nc.department_id = :dept'; $params[':dept'] = (int) $f['department_id']; }
if ($f['responsible_id'] !== '') { $where[] = 'nc.responsible_id = :resp'; $params[':resp'] = (int) $f['responsible_id']; }
if ($f['date_from'] !== '') { $where[] = 'nc.occurred_on >= :df'; $params[':df'] = $f['date_from']; }
if ($f['date_to'] !== '') { $where[] = 'nc.occurred_on <= :dt'; $params[':dt'] = $f['date_to']; }

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare(
    "SELECT nc.id, nc.reference, nc.occurred_on, nc.severity, nc.status, nc.product, nc.origin,
            d.name AS department_name, CONCAT(r.first_name,' ',r.last_name) AS responsible_name
     FROM non_conformities nc
     LEFT JOIN departments d ON d.id = nc.department_id
     LEFT JOIN users r ON r.id = nc.responsible_id
     {$whereSql} ORDER BY nc.id DESC"
);
$stmt->execute($params);
$items = $stmt->fetchAll();

$departments = $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
$users = $pdo->query('SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY last_name')->fetchAll();
$severities = ['critique', 'majeure', 'mineure'];
$origins = ['production', 'stock', 'reception', 'expedition', 'client', 'fournisseur'];
$statuses = ['ouverte', 'en_analyse', 'action_corrective', 'validation', 'cloturee'];

$pageTitle = 'Non-conformités';
$activeMenu = 'nonconformites';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div><p class="page-eyebrow">Module Qualité</p><h2 class="page-title">Non-conformités <span class="text-muted" style="font-size:16px;font-weight:500">· <?= count($items) ?></span></h2></div>
    <div class="page-head-actions">
        <a href="rapports.php" class="btn btn-outline-secondary"><i class="fa-solid fa-file-export me-2"></i>Exporter</a>
        <?php if (can('nonconformity.create')): ?><a href="nc_form.php" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Déclarer une NC</a><?php endif; ?>
    </div>
</div>

<form class="filter-bar" method="get" action="nonconformites.php">
    <div class="row g-2">
        <div class="col-lg-3 col-md-6"><input type="text" name="keyword" class="form-control" placeholder="Réf, produit, lot, description…" value="<?= e($f['keyword']) ?>"></div>
        <div class="col-lg-2 col-md-6"><select name="severity" class="form-select"><option value="">Toutes gravités</option><?php foreach ($severities as $s): ?><option value="<?= $s ?>" <?= $f['severity'] === $s ? 'selected' : '' ?>><?= e(ui_label('severity', $s)) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2 col-md-6"><select name="status" class="form-select"><option value="">Tous statuts</option><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= e(ui_label('status', $s)) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2 col-md-6"><select name="origin" class="form-select"><option value="">Toutes origines</option><?php foreach ($origins as $o): ?><option value="<?= $o ?>" <?= $f['origin'] === $o ? 'selected' : '' ?>><?= e(ui_label('origin', $o)) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-3 col-md-6"><select name="department_id" class="form-select"><option value="">Tous services</option><?php foreach ($departments as $d): ?><option value="<?= (int) $d['id'] ?>" <?= (string) $f['department_id'] === (string) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-3 col-md-6"><select name="responsible_id" class="form-select"><option value="">Tous responsables</option><?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>" <?= (string) $f['responsible_id'] === (string) $u['id'] ? 'selected' : '' ?>><?= e($u['first_name'] . ' ' . $u['last_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2 col-md-6"><input type="date" name="date_from" class="form-control" value="<?= e($f['date_from']) ?>" title="Du"></div>
        <div class="col-lg-2 col-md-6"><input type="date" name="date_to" class="form-control" value="<?= e($f['date_to']) ?>" title="Au"></div>
        <div class="col-lg-5 col-md-12 d-flex gap-2">
            <button class="btn btn-primary flex-fill"><i class="fa-solid fa-magnifying-glass me-2"></i>Filtrer</button>
            <a href="nonconformites.php" class="btn btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
    </div>
</form>

<div class="panel"><div class="panel-body">
    <?php if ($items === []): ?>
        <div class="empty-state"><i class="fa-solid fa-clipboard-check"></i><p>Aucune non-conformité ne correspond à ces critères.</p></div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table js-datatable" style="width:100%">
            <thead><tr><th>Référence</th><th>Date</th><th>Produit</th><th>Service</th><th>Origine</th><th>Gravité</th><th>Statut</th><th>Responsable</th><th></th></tr></thead>
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
                    <td class="text-end"><a href="nc_show.php?id=<?= (int) $nc['id'] ?>" class="btn-icon" title="Consulter"><i class="fa-solid fa-eye"></i></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div></div>

<?php require __DIR__ . '/includes/footer.php'; ?>
