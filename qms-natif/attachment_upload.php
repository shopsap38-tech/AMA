<?php
require __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('nonconformites.php'); }
csrf_check();
require_permission('nonconformity.update');

$ncId = (int) ($_POST['nc_id'] ?? 0);
$allowedMimes = [
    'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'video/mp4', 'video/webm',
];
$maxSize = 25 * 1024 * 1024;
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }

$files = $_FILES['attachments'] ?? null;
if (!$files || !isset($files['name'])) { flash_set('error', 'Aucun fichier sélectionné.'); redirect('nc_show.php?id=' . $ncId); }

$names = (array) $files['name'];
$count = 0;
$finfo = new finfo(FILEINFO_MIME_TYPE);

for ($i = 0; $i < count($names); $i++) {
    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { continue; }
    if ($files['error'][$i] !== UPLOAD_ERR_OK) { flash_set('error', 'Erreur de téléversement.'); redirect('nc_show.php?id=' . $ncId); }
    if ($files['size'][$i] > $maxSize) { flash_set('error', 'Fichier trop volumineux (25 Mo max).'); redirect('nc_show.php?id=' . $ncId); }

    $tmp = $files['tmp_name'][$i];
    $mime = $finfo->file($tmp) ?: 'application/octet-stream';
    if (!in_array($mime, $allowedMimes, true)) { flash_set('error', "Type de fichier non autorisé : {$mime}"); redirect('nc_show.php?id=' . $ncId); }

    $original = preg_replace('/[^\w.\- ]+/u', '_', basename($names[$i])) ?: 'fichier';
    $original = mb_substr($original, 0, 200);
    $ext = pathinfo($original, PATHINFO_EXTENSION);
    $stored = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . ($ext !== '' ? '.' . $ext : '');

    if (!move_uploaded_file($tmp, $uploadDir . '/' . $stored)) {
        flash_set('error', "Impossible d'enregistrer le fichier."); redirect('nc_show.php?id=' . $ncId);
    }

    // Versionnage : incrémente si un fichier du même nom existe déjà
    $vs = $pdo->prepare('SELECT MAX(version) FROM attachments WHERE attachable_type = "non_conformity" AND attachable_id = ? AND original_name = ?');
    $vs->execute([$ncId, $original]);
    $version = $vs->fetchColumn();
    $version = $version === null ? 1 : ((int) $version + 1);

    $pdo->prepare(
        'INSERT INTO attachments (attachable_type, attachable_id, original_name, stored_name, mime_type, size, version, uploaded_by, created_at)
         VALUES ("non_conformity", ?, ?, ?, ?, ?, ?, ?, NOW())'
    )->execute([$ncId, $original, $stored, $mime, (int) $files['size'][$i], $version, current_user()['id']]);
    $count++;
}

audit_log('upload', 'non_conformity', $ncId, null, ['count' => $count]);
flash_set('success', $count . ' fichier(s) téléversé(s).');
redirect('nc_show.php?id=' . $ncId);
