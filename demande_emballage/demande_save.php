<?php
require_once __DIR__ . '/config/config.php';
require_role(['demandeur', 'administrateur']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/demande_form.php');
}
check_csrf();

$nomArticle  = trim($_POST['nom_article'] ?? '');
$quantite    = (int) ($_POST['quantite'] ?? 0);
$commentaire = trim($_POST['commentaire'] ?? '');
$commentaire = $commentaire === '' ? null : $commentaire;

if ($nomArticle === '' || $quantite < 1) {
    set_flash('danger', 'Veuillez indiquer un article et une quantité valide (≥ 1).');
    redirect('/demande_form.php');
}

$userId = current_user()['id'];

try {
    $pdo->beginTransaction();

    // Article : réutilise ou crée dans le catalogue.
    $stmt = $pdo->prepare('SELECT id FROM articles WHERE nom = ? LIMIT 1');
    $stmt->execute([$nomArticle]);
    $articleId = $stmt->fetchColumn();
    if ($articleId === false) {
        $ins = $pdo->prepare('INSERT INTO articles (nom) VALUES (?)');
        $ins->execute([$nomArticle]);
        $articleId = (int) $pdo->lastInsertId();
    } else {
        $articleId = (int) $articleId;
    }

    // Demande
    $ins = $pdo->prepare(
        'INSERT INTO demandes (article_id, nom_article, quantite, commentaire, statut, demandeur_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([$articleId, $nomArticle, $quantite, $commentaire, 'en_attente', $userId]);
    $demandeId = (int) $pdo->lastInsertId();

    // Historique
    $hist = $pdo->prepare(
        'INSERT INTO historique_statuts (demande_id, ancien_statut, nouveau_statut, user_id)
         VALUES (?, NULL, ?, ?)'
    );
    $hist->execute([$demandeId, 'en_attente', $userId]);

    $pdo->commit();
    set_flash('success', 'Votre demande a été enregistrée avec le statut « En attente ».');
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', "Erreur lors de l'enregistrement de la demande.");
    redirect('/demande_form.php');
}

redirect('/mes_demandes.php');
