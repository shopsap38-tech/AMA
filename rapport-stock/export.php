<?php
/**
 * Export CSV du rapport de suivi de stock (mêmes filtres que index.php).
 * Compatible Excel : séparateur « ; » et BOM UTF-8.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/report.php';

$filtres = lire_filtres();

try {
    $lignes = filtrer_et_trier(charger_toutes_lignes(), $filtres);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Erreur lors de la génération de l'export :\n" . $e->getMessage();
    exit;
}

$nomFichier = 'rapport-stock_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nomFichier . '"');

$out = fopen('php://output', 'w');

// BOM UTF-8 pour qu'Excel affiche correctement les accents.
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, COLONNES, ';');

foreach ($lignes as $l) {
    $ligne = [];
    foreach (COLONNES as $col) {
        $ligne[] = $l[$col] ?? '';
    }
    fputcsv($out, $ligne, ';');
}

fclose($out);
