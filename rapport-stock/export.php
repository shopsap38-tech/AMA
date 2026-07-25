<?php
/**
 * Export CSV du rapport de suivi de stock (mêmes filtres que index.php).
 * Compatible Excel : séparateur « ; » et BOM UTF-8.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/report.php';

$filtres = lire_filtres();

try {
    $pdo    = get_pdo();
    $lignes = executer_rapport($pdo, $filtres);
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

$entetes = [
    'Magasin', 'Item Code', 'Item Name', 'Disponible', 'UoM',
    'CodeBars', 'InActif', 'Poids', 'Price', 'Value', 'U_u_forcast',
];
fputcsv($out, $entetes, ';');

foreach ($lignes as $l) {
    fputcsv($out, [
        $l['Magasin'],
        $l['Item Code'],
        $l['Item Name'],
        $l['Disponible'],
        $l['UoM'],
        $l['CodeBars'],
        $l['InActif'],
        $l['Poids'],
        $l['Price'],
        $l['Value'],
        $l['U_u_forcast'],
    ], ';');
}

fclose($out);
