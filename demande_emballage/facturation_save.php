<?php
require_once __DIR__ . '/config/config.php';
require_role(['facturation', 'administrateur']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/facturation.php');
}
check_csrf();

$demandeId = (int) ($_POST['demande_id'] ?? 0);
$userId    = current_user()['id'];

$stmt = $pdo->prepare("SELECT * FROM demandes WHERE id = ? AND statut = 'en_route' LIMIT 1");
$stmt->execute([$demandeId]);
$demande = $stmt->fetch();

if (!$demande) {
    set_flash('warning', 'Cette demande n\'est plus en route.');
    redirect('/facturation.php');
}

try {
    $pdo->beginTransaction();

    // Clôture : statut « fait », horodatage et calcul du SLA (minutes) côté SQL.
    $upd = $pdo->prepare(
        "UPDATE demandes
         SET statut = 'fait',
             facturation_id = ?,
             cloture_le = NOW(),
             delai_minutes = TIMESTAMPDIFF(MINUTE, cree_le, NOW())
         WHERE id = ? AND statut = 'en_route'"
    );
    $upd->execute([$userId, $demandeId]);

    $hist = $pdo->prepare(
        'INSERT INTO historique_statuts (demande_id, ancien_statut, nouveau_statut, user_id)
         VALUES (?, ?, ?, ?)'
    );
    $hist->execute([$demandeId, 'en_route', 'fait', $userId]);

    $pdo->commit();

    // Relire le délai pour le message.
    $d = $pdo->prepare('SELECT delai_minutes FROM demandes WHERE id = ?');
    $d->execute([$demandeId]);
    $delai = (int) $d->fetchColumn();

    set_flash(
        'success',
        'Demande #' . $demandeId . " clôturée. Délai de traitement : " . format_delai($delai)
        . ($delai <= SLA_SEUIL ? ' (dans les délais ✓)' : ' (hors délai)')
    );
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Erreur lors de la clôture de la demande.');
}

redirect('/facturation.php');
