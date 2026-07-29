<?php
require __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('nonconformites.php'); }
csrf_check();

$mode = $_POST['mode'] ?? 'create';
$ncId = (int) ($_POST['nc_id'] ?? 0);
$priorities = ['basse', 'normale', 'haute', 'urgente'];
$statuses = ['a_faire', 'en_cours', 'terminee', 'annulee'];

if ($mode === 'create') {
    require_permission('action.create');
    $title = trim($_POST['title'] ?? '');
    $dueDate = $_POST['due_date'] ?? '';
    $priority = in_array($_POST['priority'] ?? '', $priorities, true) ? $_POST['priority'] : 'normale';
    $assignee = ($_POST['assignee_id'] ?? '') !== '' ? (int) $_POST['assignee_id'] : null;

    if ($title === '' || !strtotime($dueDate)) {
        flash_set('error', "L'intitulé et la date limite sont obligatoires.");
        redirect('nc_show.php?id=' . $ncId);
    }

    $pdo->prepare(
        'INSERT INTO corrective_actions (non_conformity_id, title, description, assignee_id, due_date, priority, status, created_by, created_at)
         VALUES (?, ?, ?, ?, ?, ?, "a_faire", ?, NOW())'
    )->execute([$ncId, $title, trim($_POST['description'] ?? '') ?: null, $assignee, $dueDate, $priority, current_user()['id']]);
    $actionId = (int) $pdo->lastInsertId();
    audit_log('create', 'corrective_action', $actionId, null, ['title' => $title, 'non_conformity_id' => $ncId]);

    // La NC passe en « action corrective » si elle était ouverte / en analyse
    $st = $pdo->prepare('SELECT status FROM non_conformities WHERE id = ?');
    $st->execute([$ncId]);
    if (in_array($st->fetchColumn(), ['ouverte', 'en_analyse'], true)) {
        $pdo->prepare("UPDATE non_conformities SET status = 'action_corrective', updated_at = NOW() WHERE id = ?")->execute([$ncId]);
    }
    if ($assignee) {
        notify($assignee, 'action_assigned', 'Action corrective assignée',
            "Une action corrective « {$title} » vous a été confiée (échéance : {$dueDate}).", 'nc_show.php?id=' . $ncId);
    }
    flash_set('success', 'Action corrective ajoutée.');
    redirect('nc_show.php?id=' . $ncId);
}

// --- update ---
require_permission('action.update');
$id = (int) ($_POST['id'] ?? 0);
$status = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'a_faire';

$st = $pdo->prepare('SELECT * FROM corrective_actions WHERE id = ?');
$st->execute([$id]);
$old = $st->fetch();
if (!$old) { redirect('nc_show.php?id=' . $ncId); }

$completedAt = ($status === 'terminee' && $old['status'] !== 'terminee') ? date('Y-m-d H:i:s') : $old['completed_at'];
$pdo->prepare('UPDATE corrective_actions SET status = ?, completed_at = ?, updated_at = NOW() WHERE id = ?')
    ->execute([$status, $completedAt, $id]);
audit_log('update', 'corrective_action', $id, $old, ['status' => $status]);
flash_set('success', 'Action corrective mise à jour.');
redirect('nc_show.php?id=' . $ncId);
