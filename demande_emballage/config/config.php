<?php
// Configuration globale, session, connexion, helpers.
// À inclure en tête de chaque page.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Connexion base de données ---
require_once __DIR__ . '/database.php';

// --- URL de base de l'application (calculée dynamiquement) ---
// Fonctionne aussi bien sous http://localhost/demande_emballage
// que sous un hôte virtuel (ex. http://demande-emballage.test).
if (!defined('BASE_URL')) {
    $docRoot = str_replace('\\', '/', rtrim((string) realpath($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));
    $appRoot = str_replace('\\', '/', dirname(__DIR__));
    $base    = '';
    if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
        $base = substr($appRoot, strlen($docRoot));
    }
    define('BASE_URL', rtrim($base, '/'));
}

// --- Dossier des photos ---
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

// --- Libellés des rôles et statuts ---
$GLOBALS['ROLES'] = [
    'administrateur' => 'Administrateur',
    'demandeur'      => 'Demandeur',
    'preparateur'    => 'Préparateur',
    'facturation'    => 'Agent Facturation',
];

$GLOBALS['STATUTS'] = [
    'en_attente' => 'En attente',
    'en_route'   => 'En route',
    'fait'       => "C'est fait",
];

$GLOBALS['STATUT_BADGE'] = [
    'en_attente' => 'warning',
    'en_route'   => 'info',
    'fait'       => 'success',
];

// Seuil SLA (minutes)
define('SLA_SEUIL', 30);

require_once __DIR__ . '/../includes/functions.php';
