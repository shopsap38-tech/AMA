<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$type = $_GET['type'] ?? 'journalier';
if (!in_array($type, ['journalier', 'mensuel', 'annuel'], true)) {
    $type = 'journalier';
}
$defaults = ['journalier' => date('Y-m-d'), 'mensuel' => date('Y-m'), 'annuel' => date('Y')];
$ref = $_GET['ref'] ?? $defaults[$type];

$d = rapport_data($pdo, $type, $ref);

$filename = 'rapport_' . $type . '_' . preg_replace('/[^0-9A-Za-z-]/', '', $ref) . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
// BOM UTF-8 pour qu'Excel affiche correctement les accents.
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [$d['libelle']], ';');
fputcsv($out, [], ';');
fputcsv($out, ['Indicateur', 'Valeur'], ';');
fputcsv($out, ['Changements d\'état chariots', $d['changements_etat']], ';');
fputcsv($out, ['Nouvelles palettes', $d['nouvelles_palettes']], ';');
fputcsv($out, ['Nouveaux chariots', $d['nouveaux_chariots']], ';');
fputcsv($out, [], ';');
fputcsv($out, ['Situation actuelle', ''], ';');
fputcsv($out, ['Total chariots', $d['chariots']['total']], ';');
fputcsv($out, ['Chariots disponibles', $d['chariots']['disponible']], ';');
fputcsv($out, ['Chariots en maintenance', $d['chariots']['maintenance']], ';');
fputcsv($out, ['Chariots en panne', $d['chariots']['panne']], ';');
fputcsv($out, ['Chariots électriques', $d['chariots']['electrique']], ';');
fputcsv($out, ['Chariots diesel', $d['chariots']['diesel']], ';');
fputcsv($out, ['Taux de disponibilité (%)', $d['chariots']['taux_dispo']], ';');
fputcsv($out, ['Palettes conformes', $d['palettes']['conforme']], ';');
fputcsv($out, ['Palettes non conformes', $d['palettes']['non_conforme']], ';');
fputcsv($out, ['Palettes cassées', $d['palettes']['cassee']], ';');

fclose($out);
exit;
