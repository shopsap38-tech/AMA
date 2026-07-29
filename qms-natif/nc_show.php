<?php
require __DIR__ . '/includes/functions.php';
require_permission('nonconformity.view');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT nc.*, d.name AS department_name,
            CONCAT(r.first_name,' ',r.last_name) AS responsible_name,
            CONCAT(c.first_name,' ',c.last_name) AS created_by_name
     FROM non_conformities nc
     LEFT JOIN departments d ON d.id = nc.department_id
     LEFT JOIN users r ON r.id = nc.responsible_id
     LEFT JOIN users c ON c.id = nc.created_by
     WHERE nc.id = ?"
);
$stmt->execute([$id]);
$nc = $stmt->fetch();
if (!$nc) { flash_set('error', 'Non-conformité introuvable.'); redirect('nonconformites.php'); }

$actionsStmt = $pdo->prepare(
    "SELECT ca.*, CONCAT(a.first_name,' ',a.last_name) AS assignee_name, DATEDIFF(CURDATE(), ca.due_date) AS days_overdue
     FROM corrective_actions ca LEFT JOIN users a ON a.id = ca.assignee_id
     WHERE ca.non_conformity_id = ? ORDER BY ca.due_date ASC"
);
$actionsStmt->execute([$id]);
$actions = $actionsStmt->fetchAll();

$valStmt = $pdo->prepare(
    "SELECT vs.*, CONCAT(u.first_name,' ',u.last_name) AS approver_name
     FROM validation_steps vs LEFT JOIN users u ON u.id = vs.approver_id
     WHERE vs.non_conformity_id = ? ORDER BY vs.step_order ASC"
);
$valStmt->execute([$id]);
$validations = $valStmt->fetchAll();

$attStmt = $pdo->prepare(
    "SELECT a.*, CONCAT(u.first_name,' ',u.last_name) AS uploaded_by_name
     FROM attachments a LEFT JOIN users u ON u.id = a.uploaded_by
     WHERE a.attachable_type = 'non_conformity' AND a.attachable_id = ? ORDER BY a.original_name, a.version DESC"
);
$attStmt->execute([$id]);
$attachments = $attStmt->fetchAll();

$histStmt = $pdo->prepare(
    "SELECT h.*, CONCAT(u.first_name,' ',u.last_name) AS user_name
     FROM audit_logs h LEFT JOIN users u ON u.id = h.user_id
     WHERE h.entity_type = 'non_conformity' AND h.entity_id = ? ORDER BY h.id DESC"
);
$histStmt->execute([$id]);
$history = $histStmt->fetchAll();

$users = $pdo->query('SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY last_name')->fetchAll();

$currentStep = null;
foreach ($validations as $v) { if ($v['status'] === 'en_attente') { $currentStep = $v; break; } }

$fileIcon = function (string $mime): string {
    if (str_starts_with($mime, 'image/')) return 'fa-file-image';
    if ($mime === 'application/pdf') return 'fa-file-pdf';
    if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) return 'fa-file-excel';
    if (str_starts_with($mime, 'video/')) return 'fa-file-video';
    return 'fa-file';
};

$pageTitle = $nc['reference'];
$activeMenu = 'nonconformites';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
    <div>
        <p class="page-eyebrow"><a href="nonconformites.php"><i class="fa-solid fa-arrow-left me-1"></i>Non-conformités</a></p>
        <h2 class="page-title d-flex align-items-center gap-3">
            <span class="ref-tag" style="font-size:18px"><?= e($nc['reference']) ?></span>
            <span class="badge <?= e(ui_badge('severity', $nc['severity'])) ?>"><?= e(ui_label('severity', $nc['severity'])) ?></span>
            <span class="badge <?= e(ui_badge('status', $nc['status'])) ?>"><?= e(ui_label('status', $nc['status'])) ?></span>
        </h2>
    </div>
    <div class="page-head-actions">
        <?php if (can('nonconformity.update')): ?><a href="nc_form.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="fa-solid fa-pen me-2"></i>Modifier</a><?php endif; ?>
        <?php if (can('nonconformity.delete')): ?>
            <form method="post" action="nc_delete.php" data-confirm="Supprimer définitivement cette non-conformité ?">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
                <button class="btn btn-outline-secondary text-danger"><i class="fa-solid fa-trash me-2"></i>Supprimer</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="detail-grid">
    <div>
        <div class="panel">
            <ul class="nav nav-tabs px-3 pt-2" role="tablist" style="border-bottom:1px solid var(--border)">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info" type="button">Détails</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-actions" type="button">Actions correctives <span class="badge badge-info ms-1"><?= count($actions) ?></span></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-files" type="button">Documents <span class="badge badge-info ms-1"><?= count($attachments) ?></span></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history" type="button">Historique</button></li>
            </ul>
            <div class="tab-content panel-body">
                <div class="tab-pane fade show active" id="tab-info">
                    <div class="info-list">
                        <div class="info-item"><label>Date / Heure</label><span><?= e(format_date($nc['occurred_on'])) ?> <?= e($nc['occurred_at'] ? substr($nc['occurred_at'], 0, 5) : '') ?></span></div>
                        <div class="info-item"><label>Service</label><span><?= e($nc['department_name'] ?? '—') ?></span></div>
                        <div class="info-item"><label>Atelier</label><span><?= e($nc['workshop'] ?: '—') ?></span></div>
                        <div class="info-item"><label>Emplacement</label><span><?= e($nc['location'] ?: '—') ?></span></div>
                        <div class="info-item"><label>Produit</label><span><?= e($nc['product']) ?></span></div>
                        <div class="info-item"><label>Référence</label><span><?= e($nc['product_reference'] ?: '—') ?></span></div>
                        <div class="info-item"><label>Lot</label><span><?= e($nc['batch'] ?: '—') ?></span></div>
                        <div class="info-item"><label>Quantité</label><span><?= e($nc['quantity'] !== null ? $nc['quantity'] : '—') ?></span></div>
                        <div class="info-item"><label>Origine</label><span><?= e(ui_label('origin', $nc['origin'])) ?></span></div>
                        <div class="info-item"><label>Responsable</label><span><?= e($nc['responsible_name'] ?: '—') ?></span></div>
                    </div>
                    <div class="mt-4"><label class="form-label">Description</label><div class="prose-block"><?= e($nc['description']) ?></div></div>
                    <?php if ($nc['observation']): ?><div class="mt-3"><label class="form-label">Observation</label><div class="prose-block"><?= e($nc['observation']) ?></div></div><?php endif; ?>
                    <?php if ($nc['root_cause_analysis']): ?><div class="mt-3"><label class="form-label">Analyse des causes</label><div class="prose-block"><?= e($nc['root_cause_analysis']) ?></div></div><?php endif; ?>
                    <?php if ($nc['impact']): ?><div class="mt-3"><label class="form-label">Impact</label><div class="prose-block"><?= e($nc['impact']) ?></div></div><?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab-actions">
                    <?php foreach ($actions as $a): ?>
                        <div class="attachment-card" style="align-items:flex-start">
                            <div class="attachment-thumb" style="background:var(--warning-soft);color:var(--warning)"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                            <div class="flex-fill">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <strong><?= e($a['title']) ?></strong>
                                    <div class="d-flex gap-1">
                                        <span class="badge <?= e(ui_badge('priority', $a['priority'])) ?>"><?= e(ui_label('priority', $a['priority'])) ?></span>
                                        <span class="badge <?= e(ui_badge('action_status', $a['status'])) ?>"><?= e(ui_label('action_status', $a['status'])) ?></span>
                                    </div>
                                </div>
                                <?php if ($a['description']): ?><div class="text-muted small mt-1"><?= e($a['description']) ?></div><?php endif; ?>
                                <div class="d-flex gap-3 mt-2 text-muted" style="font-size:12px">
                                    <span><i class="fa-solid fa-user me-1"></i><?= e($a['assignee_name'] ?: 'Non assigné') ?></span>
                                    <span><i class="fa-solid fa-calendar-day me-1"></i><?= e(format_date($a['due_date'])) ?></span>
                                    <?php if ((int) $a['days_overdue'] > 0 && !in_array($a['status'], ['terminee', 'annulee'], true)): ?><span class="text-danger fw-semibold"><i class="fa-solid fa-triangle-exclamation me-1"></i>En retard de <?= (int) $a['days_overdue'] ?> j</span><?php endif; ?>
                                </div>
                                <?php if (can('action.update')): ?>
                                    <form method="post" action="action_save.php" class="d-flex gap-2 mt-2">
                                        <?= csrf_field() ?><input type="hidden" name="mode" value="update"><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="nc_id" value="<?= $id ?>">
                                        <select name="status" class="form-select form-select-sm" style="max-width:180px">
                                            <?php foreach (['a_faire', 'en_cours', 'terminee', 'annulee'] as $st): ?><option value="<?= $st ?>" <?= $a['status'] === $st ? 'selected' : '' ?>><?= e(ui_label('action_status', $st)) ?></option><?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-sm btn-outline-secondary">Mettre à jour</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($actions === []): ?><div class="empty-state"><i class="fa-solid fa-screwdriver-wrench"></i><p>Aucune action corrective pour le moment.</p></div><?php endif; ?>

                    <?php if (can('action.create')): ?>
                    <div class="form-card mt-3"><div class="card-section">
                        <div class="section-title"><i class="fa-solid fa-plus"></i> Nouvelle action corrective</div>
                        <form method="post" action="action_save.php">
                            <?= csrf_field() ?><input type="hidden" name="mode" value="create"><input type="hidden" name="nc_id" value="<?= $id ?>">
                            <div class="row g-2">
                                <div class="col-12"><input name="title" class="form-control" placeholder="Intitulé de l'action *" required></div>
                                <div class="col-12"><textarea name="description" class="form-control" rows="2" placeholder="Description"></textarea></div>
                                <div class="col-md-4"><select name="assignee_id" class="form-select"><option value="">Responsable</option><?php foreach ($users as $u): ?><option value="<?= (int) $u['id'] ?>"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-4"><input type="date" name="due_date" class="form-control" required></div>
                                <div class="col-md-4"><select name="priority" class="form-select"><?php foreach (['basse', 'normale', 'haute', 'urgente'] as $pr): ?><option value="<?= $pr ?>" <?= $pr === 'normale' ? 'selected' : '' ?>><?= e(ui_label('priority', $pr)) ?></option><?php endforeach; ?></select></div>
                                <div class="col-12"><button class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Ajouter</button></div>
                            </div>
                        </form>
                    </div></div>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab-files">
                    <?php if (can('nonconformity.update')): ?>
                    <form method="post" action="attachment_upload.php" enctype="multipart/form-data">
                        <?= csrf_field() ?><input type="hidden" name="nc_id" value="<?= $id ?>">
                        <div class="dropzone" id="dropzone">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <div>Glissez-déposez vos fichiers ici ou <strong>cliquez pour parcourir</strong></div>
                            <small class="text-muted">Photos, PDF, Excel, Vidéo — 25 Mo max · versionnage automatique</small>
                            <input type="file" id="fileInput" name="attachments[]" multiple hidden>
                        </div>
                        <div class="file-preview" id="filePreview"></div>
                        <button class="btn btn-primary mt-3"><i class="fa-solid fa-upload me-2"></i>Téléverser</button>
                    </form>
                    <hr>
                    <?php endif; ?>
                    <?php foreach ($attachments as $file): ?>
                        <div class="attachment-card">
                            <div class="attachment-thumb"><i class="fa-solid <?= $fileIcon($file['mime_type']) ?>"></i></div>
                            <div class="flex-fill">
                                <strong><?= e($file['original_name']) ?></strong>
                                <div class="text-muted small">v<?= (int) $file['version'] ?> · <?= number_format($file['size'] / 1024, 0, ',', ' ') ?> Ko · <?= e($file['uploaded_by_name'] ?? '') ?> · <?= e(format_date($file['created_at'])) ?></div>
                            </div>
                            <a href="attachment_download.php?id=<?= (int) $file['id'] ?>" class="btn-icon" title="Télécharger"><i class="fa-solid fa-download"></i></a>
                            <?php if (can('nonconformity.update')): ?>
                                <form method="post" action="attachment_delete.php" data-confirm="Supprimer ce fichier ?">
                                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $file['id'] ?>"><input type="hidden" name="nc_id" value="<?= $id ?>">
                                    <button class="btn-icon text-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($attachments === []): ?><div class="empty-state"><i class="fa-solid fa-folder-open"></i><p>Aucun document joint.</p></div><?php endif; ?>
                </div>

                <div class="tab-pane fade" id="tab-history">
                    <table class="table-clean" style="width:100%">
                        <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Adresse IP</th></tr></thead>
                        <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr><td><?= e(format_date($h['created_at'], 'd/m/Y H:i')) ?></td><td><?= e($h['user_name'] ?: 'Système') ?></td><td><span class="badge badge-info"><?= e($h['action']) ?></span></td><td class="text-muted" style="font-family:monospace;font-size:12px"><?= e($h['ip_address']) ?></td></tr>
                        <?php endforeach; ?>
                        <?php if ($history === []): ?><tr><td colspan="4" class="text-center text-muted py-3">Aucun événement</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel-head"><h3><i class="fa-solid fa-signature me-2"></i>Circuit de validation</h3></div>
            <div class="panel-body">
                <div class="timeline">
                    <?php foreach ($validations as $v):
                        $cls = $v['status'] === 'approuve' ? 'done' : ($v['status'] === 'rejete' ? 'rejected' : ($currentStep && $currentStep['id'] === $v['id'] ? 'current' : ''));
                        $icon = $v['status'] === 'approuve' ? 'fa-check' : ($v['status'] === 'rejete' ? 'fa-xmark' : 'fa-hourglass-half');
                    ?>
                        <div class="timeline-step <?= $cls ?>">
                            <div class="timeline-dot"><i class="fa-solid <?= $icon ?>"></i></div>
                            <div class="timeline-content">
                                <h4><?= e($v['step_label']) ?></h4>
                                <div class="timeline-meta">
                                    <span class="badge <?= e(ui_badge('step_status', $v['status'])) ?>"><?= e(ui_label('step_status', $v['status'])) ?></span>
                                    <?php if ($v['approver_name']): ?> · <?= e($v['approver_name']) ?> · <?= e(format_date($v['acted_at'], 'd/m/Y H:i')) ?><?php endif; ?>
                                </div>
                                <?php if ($v['comment']): ?><div class="text-muted small mt-1"><?= e($v['comment']) ?></div><?php endif; ?>
                                <?php if ($v['signature']): ?><div class="signature-box"><i class="fa-solid fa-fingerprint me-1"></i><?= e($v['signature']) ?></div><?php endif; ?>

                                <?php if ($currentStep && $currentStep['id'] === $v['id'] && can('validation.act') && (has_role($v['role_required']) || can('*'))): ?>
                                    <form method="post" action="validation_save.php" class="mt-2">
                                        <?= csrf_field() ?><input type="hidden" name="nc_id" value="<?= $id ?>"><input type="hidden" name="step_id" value="<?= (int) $v['id'] ?>">
                                        <input name="signature" class="form-control form-control-sm mb-2" placeholder="Signature (nom complet) *" required>
                                        <textarea name="comment" class="form-control form-control-sm mb-2" rows="2" placeholder="Commentaire"></textarea>
                                        <div class="d-flex gap-2">
                                            <button name="decision" value="approuve" class="btn btn-sm btn-primary flex-fill"><i class="fa-solid fa-check me-1"></i>Approuver</button>
                                            <button name="decision" value="rejete" class="btn btn-sm btn-outline-secondary text-danger flex-fill"><i class="fa-solid fa-xmark me-1"></i>Rejeter</button>
                                        </div>
                                    </form>
                                <?php elseif ($currentStep && $currentStep['id'] === $v['id']): ?>
                                    <div class="text-muted small mt-1"><i class="fa-solid fa-lock me-1"></i>En attente du rôle « <?= e(ui_label('role', $v['role_required'])) ?> ».</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
