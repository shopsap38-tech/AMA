<?php
require __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('nonconformites.php'); }
csrf_check();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$isEdit = $id > 0;
require_permission($isEdit ? 'nonconformity.update' : 'nonconformity.create');

$severities = ['critique', 'majeure', 'mineure'];
$origins = ['production', 'stock', 'reception', 'expedition', 'client', 'fournisseur'];

$data = [
    'occurred_on'         => $_POST['occurred_on'] ?? '',
    'occurred_at'         => $_POST['occurred_at'] ?: null,
    'department_id'       => $_POST['department_id'] ?? '',
    'workshop'            => trim($_POST['workshop'] ?? '') ?: null,
    'location'            => trim($_POST['location'] ?? '') ?: null,
    'product'             => trim($_POST['product'] ?? ''),
    'product_reference'   => trim($_POST['product_reference'] ?? '') ?: null,
    'batch'               => trim($_POST['batch'] ?? '') ?: null,
    'quantity'            => ($_POST['quantity'] ?? '') !== '' ? (float) $_POST['quantity'] : null,
    'severity'            => $_POST['severity'] ?? '',
    'observation'         => trim($_POST['observation'] ?? '') ?: null,
    'origin'              => $_POST['origin'] ?? '',
    'description'         => trim($_POST['description'] ?? ''),
    'root_cause_analysis' => trim($_POST['root_cause_analysis'] ?? '') ?: null,
    'impact'              => trim($_POST['impact'] ?? '') ?: null,
    'responsible_id'      => ($_POST['responsible_id'] ?? '') !== '' ? (int) $_POST['responsible_id'] : null,
];

// --- Validation ---
$errors = [];
if ($data['occurred_on'] === '' || !strtotime($data['occurred_on'])) { $errors[] = 'La date est obligatoire.'; }
if ($data['department_id'] === '') { $errors[] = 'Le service est obligatoire.'; }
if ($data['product'] === '') { $errors[] = 'Le produit est obligatoire.'; }
if (!in_array($data['severity'], $severities, true)) { $errors[] = 'La gravité est invalide.'; }
if (!in_array($data['origin'], $origins, true)) { $errors[] = "L'origine est invalide."; }
if (mb_strlen($data['description']) < 5) { $errors[] = 'La description doit contenir au moins 5 caractères.'; }

if ($errors) {
    $_SESSION['_errors'] = $errors;
    $_SESSION['_old'] = $_POST;
    flash_set('error', implode(' ', $errors));
    redirect($isEdit ? 'nc_form.php?id=' . $id : 'nc_form.php');
}
$data['department_id'] = (int) $data['department_id'];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM non_conformities WHERE id = ?');
    $stmt->execute([$id]);
    $oldRow = $stmt->fetch();

    $sql = 'UPDATE non_conformities SET occurred_on=:occurred_on, occurred_at=:occurred_at, department_id=:department_id,
            workshop=:workshop, location=:location, product=:product, product_reference=:product_reference, batch=:batch,
            quantity=:quantity, severity=:severity, observation=:observation, origin=:origin, description=:description,
            root_cause_analysis=:root_cause_analysis, impact=:impact, responsible_id=:responsible_id, updated_at=NOW()
            WHERE id=:id';
    $params = $data; $params['id'] = $id;
    $pdo->prepare($sql)->execute($params);
    audit_log('update', 'non_conformity', $id, $oldRow, $data);
    flash_set('success', 'Non-conformité mise à jour.');
    redirect('nc_show.php?id=' . $id);
}

// --- Création : transaction (NC + circuit de validation) ---
$pdo->beginTransaction();
try {
    $data['reference'] = next_nc_reference((int) date('Y'));
    $data['status'] = 'ouverte';
    $data['created_by'] = current_user()['id'];

    $cols = array_keys($data);
    $placeholders = array_map(fn ($c) => ':' . $c, $cols);
    $pdo->prepare('INSERT INTO non_conformities (' . implode(',', $cols) . ') VALUES (' . implode(',', $placeholders) . ')')
        ->execute($data);
    $ncId = (int) $pdo->lastInsertId();

    // Circuit de validation à 5 niveaux
    $workflow = [
        ['employe', 'Déclaration (Employé)'],
        ['chef_equipe', "Revue Chef d'équipe"],
        ['responsable_qualite', 'Validation Responsable Qualité'],
        ['responsable_production', 'Validation Responsable Production'],
        ['direction', 'Approbation Direction'],
    ];
    $wf = $pdo->prepare('INSERT INTO validation_steps (non_conformity_id, step_order, role_required, step_label, status) VALUES (?, ?, ?, ?, "en_attente")');
    foreach ($workflow as $i => $step) {
        $wf->execute([$ncId, $i + 1, $step[0], $step[1]]);
    }

    audit_log('create', 'non_conformity', $ncId, null, $data);
    if ($data['responsible_id']) {
        notify((int) $data['responsible_id'], 'nc_assigned', 'Nouvelle non-conformité assignée',
            "La non-conformité {$data['reference']} vous a été assignée.", 'nc_show.php?id=' . $ncId);
    }
    $pdo->commit();
    flash_set('success', 'Non-conformité créée avec succès.');
    redirect('nc_show.php?id=' . $ncId);
} catch (Throwable $ex) {
    $pdo->rollBack();
    flash_set('error', 'Erreur lors de la création : ' . $ex->getMessage());
    redirect('nc_form.php');
}
