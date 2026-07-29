<?php
require __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('nonconformites.php'); }
csrf_check();
require_permission('validation.act');

$ncId = (int) ($_POST['nc_id'] ?? 0);
$stepId = (int) ($_POST['step_id'] ?? 0);
$decision = $_POST['decision'] ?? '';
$signatureName = trim($_POST['signature'] ?? '');
$comment = trim($_POST['comment'] ?? '') ?: null;

if (!in_array($decision, ['approuve', 'rejete'], true)) { flash_set('error', 'Décision invalide.'); redirect('nc_show.php?id=' . $ncId); }
if ($signatureName === '') { flash_set('error', 'La signature électronique est obligatoire.'); redirect('nc_show.php?id=' . $ncId); }

$st = $pdo->prepare('SELECT * FROM validation_steps WHERE id = ?');
$st->execute([$stepId]);
$step = $st->fetch();

if (!$step || (int) $step['non_conformity_id'] !== $ncId) { flash_set('error', 'Étape introuvable.'); redirect('nc_show.php?id=' . $ncId); }
if ($step['status'] !== 'en_attente') { flash_set('error', 'Cette étape a déjà été traitée.'); redirect('nc_show.php?id=' . $ncId); }

// Ordre strict du circuit
$cur = $pdo->prepare("SELECT id FROM validation_steps WHERE non_conformity_id = ? AND status = 'en_attente' ORDER BY step_order ASC LIMIT 1");
$cur->execute([$ncId]);
if ((int) $cur->fetchColumn() !== $stepId) { flash_set('error', 'Vous devez traiter les étapes dans l\'ordre.'); redirect('nc_show.php?id=' . $ncId); }

if (!has_role($step['role_required']) && !can('*')) {
    flash_set('error', "Votre rôle ne vous autorise pas à signer l'étape « {$step['step_label']} ».");
    redirect('nc_show.php?id=' . $ncId);
}

// Signature électronique horodatée non répudiable
$signature = sprintf('%s | %s | %s', $signatureName, date('Y-m-d H:i:s'), substr(hash('sha256', $signatureName . microtime(true) . random_bytes(8)), 0, 16));

$pdo->prepare('UPDATE validation_steps SET status = ?, approver_id = ?, signature = ?, comment = ?, acted_at = NOW() WHERE id = ?')
    ->execute([$decision, current_user()['id'], $signature, $comment, $stepId]);
audit_log('validation_' . $decision, 'non_conformity', $ncId, null, ['step' => $step['step_label'], 'signature' => $signatureName]);

// Récupère la NC pour notifier le créateur
$ncStmt = $pdo->prepare('SELECT reference, created_by FROM non_conformities WHERE id = ?');
$ncStmt->execute([$ncId]);
$nc = $ncStmt->fetch();

if ($decision === 'rejete') {
    $pdo->prepare("UPDATE non_conformities SET status = 'en_analyse', updated_at = NOW() WHERE id = ?")->execute([$ncId]);
    if ($nc && $nc['created_by']) {
        notify((int) $nc['created_by'], 'validation_update', 'Circuit de validation',
            "La non-conformité {$nc['reference']} a été rejetée à l'étape « {$step['step_label']} ».", 'nc_show.php?id=' . $ncId);
    }
    flash_set('success', 'Étape rejetée et signée avec succès.');
    redirect('nc_show.php?id=' . $ncId);
}

// Approbation : passe en « validation », puis clôture si toutes approuvées
$pdo->prepare("UPDATE non_conformities SET status = 'validation', updated_at = NOW() WHERE id = ?")->execute([$ncId]);
$pending = $pdo->prepare("SELECT COUNT(*) FROM validation_steps WHERE non_conformity_id = ? AND status <> 'approuve'");
$pending->execute([$ncId]);
if ((int) $pending->fetchColumn() === 0) {
    $pdo->prepare("UPDATE non_conformities SET status = 'cloturee', closed_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$ncId]);
    audit_log('close', 'non_conformity', $ncId);
    if ($nc && $nc['created_by']) {
        notify((int) $nc['created_by'], 'validation_update', 'Non-conformité clôturée',
            "La non-conformité {$nc['reference']} a été clôturée après approbation finale.", 'nc_show.php?id=' . $ncId);
    }
}
flash_set('success', 'Étape approuvée et signée avec succès.');
redirect('nc_show.php?id=' . $ncId);
