<?php
/**
 * API JSON de l'entrepôt.
 *
 * Charge les données depuis SQL Server (via config/DATA_SOURCE), calcule les
 * palettes occupées, puis renvoie la structure complète de l'entrepôt (zones,
 * racks, taux) au format JSON. Consommée par assets/entrepot3d.js.
 *
 * Réponse : { capacite_totale, occupees, libres, taux, zones[], racks[] }
 * En cas d'échec : { error: "..." } avec code HTTP 500.
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/report.php';
require_once __DIR__ . '/includes/warehouse.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
}

try {
    $lignes = charger_toutes_lignes();
    $occ    = calculer_occupation($lignes, (float) CAPACITE_PALETTES);

    $data = construire_entrepot((float) $occ['occupees']);
    // Rappel du contexte (magasins pris en compte).
    $data['magasins'] = magasins_autorises();

    echo json_encode($data, $flags);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], $flags);
}
