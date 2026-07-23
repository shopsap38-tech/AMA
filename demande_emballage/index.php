<?php
require_once __DIR__ . '/config/config.php';
require_login();

$role = current_role();

// Redirection vers l'espace principal de chaque rôle.
switch ($role) {
    case 'demandeur':
        redirect('/mes_demandes.php');
    case 'preparateur':
        redirect('/preparateur.php');
    case 'facturation':
        redirect('/facturation.php');
    case 'administrateur':
        redirect('/dashboard.php');
}

$pageTitle = 'Accueil';
require __DIR__ . '/includes/header.php';
?>
<h1 class="h3 mb-4">Bienvenue</h1>
<p>Utilisez le menu pour accéder aux fonctionnalités.</p>
<?php require __DIR__ . '/includes/footer.php'; ?>
