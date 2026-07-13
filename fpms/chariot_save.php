<?php
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /fpms/chariots.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';

$id     = (int) ($_POST['id'] ?? 0);
$marque = trim($_POST['marque'] ?? '');
$type   = $_POST['type'] ?? '';
$etat   = $_POST['etat'] ?? '';
$unite  = $_POST['unite'] ?? '';
$unite  = array_key_exists($unite, unites()) ? $unite : null;
$dateMS = $_POST['date_mise_service'] ?? '';
$dateMS = $dateMS !== '' ? $dateMS : null;

if ($marque === ''
    || !in_array($type, ['electrique', 'diesel'], true)
    || !in_array($etat, ['disponible', 'maintenance', 'panne'], true)) {
    $back = $id ? "?id=$id" : '';
    header('Location: /fpms/chariot_form.php' . $back . '&error=' . urlencode('Veuillez remplir tous les champs obligatoires.'));
    exit;
}

try {
    if ($id > 0) {
        // Récupérer l'état actuel pour tracer un éventuel changement.
        $stmt = $pdo->prepare('SELECT etat FROM chariots WHERE id = ?');
        $stmt->execute([$id]);
        $ancienEtat = $stmt->fetchColumn();

        $stmt = $pdo->prepare(
            'UPDATE chariots
                SET marque = ?, type = ?, etat = ?, unite = ?, date_mise_service = ?
              WHERE id = ?'
        );
        $stmt->execute([$marque, $type, $etat, $unite, $dateMS, $id]);

        if ($ancienEtat !== false && $ancienEtat !== $etat) {
            $h = $pdo->prepare(
                'INSERT INTO chariot_historique (chariot_id, ancien_etat, nouvel_etat, commentaire)
                 VALUES (?, ?, ?, ?)'
            );
            $h->execute([$id, $ancienEtat, $etat, 'Changement d\'état via la fiche']);
        }
        $msg = 'Chariot mis à jour.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO chariots (marque, type, etat, unite, date_mise_service)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$marque, $type, $etat, $unite, $dateMS]);
        $newId = (int) $pdo->lastInsertId();

        $h = $pdo->prepare(
            'INSERT INTO chariot_historique (chariot_id, ancien_etat, nouvel_etat, commentaire)
             VALUES (?, NULL, ?, ?)'
        );
        $h->execute([$newId, $etat, 'Création du chariot']);
        $msg = 'Chariot ajouté.';
    }
} catch (PDOException $e) {
    $back = $id ? "?id=$id" : '';
    header('Location: /fpms/chariot_form.php' . $back . '&error=' . urlencode('Erreur : ' . $e->getMessage()));
    exit;
}

header('Location: /fpms/chariots.php?success=' . urlencode($msg));
exit;
