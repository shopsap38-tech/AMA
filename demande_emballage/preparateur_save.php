<?php
require_once __DIR__ . '/config/config.php';
require_role(['preparateur', 'administrateur']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/preparateur.php');
}
check_csrf();

$demandeId = (int) ($_POST['demande_id'] ?? 0);
$userId    = current_user()['id'];

// La demande doit exister et être « en attente ».
$stmt = $pdo->prepare("SELECT * FROM demandes WHERE id = ? AND statut = 'en_attente' LIMIT 1");
$stmt->execute([$demandeId]);
$demande = $stmt->fetch();

if (!$demande) {
    set_flash('warning', 'Cette demande n\'est plus en attente.');
    redirect('/preparateur.php');
}

// --- Traitement de la photo (optionnelle) ---
$photoNom = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!isset($allowed[$mime])) {
        set_flash('danger', 'Format d\'image non pris en charge (JPEG, PNG, WEBP ou GIF).');
        redirect('/preparateur.php');
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        set_flash('danger', 'La photo dépasse la taille maximale de 8 Mo.');
        redirect('/preparateur.php');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }

    $ext      = $allowed[$mime];
    $photoNom = 'demande_' . $demandeId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest     = UPLOAD_DIR . '/' . $photoNom;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        set_flash('danger', 'Échec de l\'enregistrement de la photo.');
        redirect('/preparateur.php');
    }
} elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    set_flash('danger', 'Erreur lors du téléversement de la photo.');
    redirect('/preparateur.php');
}

// --- Mise à jour du statut : en_route + horodatage ---
try {
    $pdo->beginTransaction();

    $upd = $pdo->prepare(
        "UPDATE demandes
         SET statut = 'en_route', preparateur_id = ?, prepare_le = NOW()
         WHERE id = ? AND statut = 'en_attente'"
    );
    $upd->execute([$userId, $demandeId]);

    if ($photoNom !== null) {
        $ph = $pdo->prepare(
            'INSERT INTO photos (demande_id, chemin, nom_origine, uploaded_by) VALUES (?, ?, ?, ?)'
        );
        $ph->execute([$demandeId, $photoNom, $_FILES['photo']['name'] ?? null, $userId]);
    }

    $hist = $pdo->prepare(
        'INSERT INTO historique_statuts (demande_id, ancien_statut, nouveau_statut, user_id)
         VALUES (?, ?, ?, ?)'
    );
    $hist->execute([$demandeId, 'en_attente', 'en_route', $userId]);

    $pdo->commit();
    set_flash('success', 'Demande #' . $demandeId . ' marquée « En route ».');
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Nettoyage de la photo si la transaction a échoué.
    if ($photoNom !== null && is_file(UPLOAD_DIR . '/' . $photoNom)) {
        unlink(UPLOAD_DIR . '/' . $photoNom);
    }
    set_flash('danger', 'Erreur lors de la mise à jour de la demande.');
}

redirect('/preparateur.php');
