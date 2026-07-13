<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Génère une page imprimable (Aperçu / Enregistrer en PDF via le navigateur).
$type = $_GET['type'] ?? 'journalier';
if (!in_array($type, ['journalier', 'mensuel', 'annuel'], true)) {
    $type = 'journalier';
}
$defaults = ['journalier' => date('Y-m-d'), 'mensuel' => date('Y-m'), 'annuel' => date('Y')];
$ref = $_GET['ref'] ?? $defaults[$type];

$d = rapport_data($pdo, $type, $ref);
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $e($d['libelle']) ?></title>
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; color: #1f2933; margin: 2.5rem; }
        h1 { font-size: 1.4rem; margin-bottom: 0.25rem; }
        .meta { color: #616e7c; font-size: 0.85rem; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        th, td { border: 1px solid #cbd2d9; padding: 0.5rem 0.75rem; text-align: left; }
        th { background: #e4e7eb; }
        h2 { font-size: 1.05rem; border-bottom: 2px solid #1f2933; padding-bottom: 0.25rem; }
        .actions { margin-bottom: 1.5rem; }
        .btn { padding: 0.5rem 1rem; background: #2563eb; color: #fff; border: none;
               border-radius: 4px; cursor: pointer; font-size: 0.9rem; }
        @media print { .actions { display: none; } body { margin: 1rem; } }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    <h1>FPMS — <?= $e($d['libelle']) ?></h1>
    <div class="meta">
        Période : du <?= $e(date('d/m/Y', strtotime($d['debut']))) ?>
        au <?= $e(date('d/m/Y', strtotime($d['fin'] . ' -1 day'))) ?>
        &middot; Édité le <?= $e(date('d/m/Y H:i')) ?>
    </div>

    <h2>Activité de la période</h2>
    <table>
        <tr><th>Changements d'état des chariots</th><td><?= $e($d['changements_etat']) ?></td></tr>
        <tr><th>Nouvelles palettes</th><td><?= $e($d['nouvelles_palettes']) ?></td></tr>
        <tr><th>Nouveaux chariots</th><td><?= $e($d['nouveaux_chariots']) ?></td></tr>
    </table>

    <h2>Situation actuelle de la flotte</h2>
    <table>
        <tr><th>Total chariots</th><td><?= $e($d['chariots']['total']) ?></td></tr>
        <tr><th>Disponibles</th><td><?= $e($d['chariots']['disponible']) ?></td></tr>
        <tr><th>En maintenance</th><td><?= $e($d['chariots']['maintenance']) ?></td></tr>
        <tr><th>En panne</th><td><?= $e($d['chariots']['panne']) ?></td></tr>
        <tr><th>Électriques</th><td><?= $e($d['chariots']['electrique']) ?></td></tr>
        <tr><th>Diesel</th><td><?= $e($d['chariots']['diesel']) ?></td></tr>
        <tr><th>Taux de disponibilité</th><td><?= $e($d['chariots']['taux_dispo']) ?> %</td></tr>
    </table>

    <h2>État des palettes</h2>
    <table>
        <tr><th>Conformes</th><td><?= $e($d['palettes']['conforme']) ?></td></tr>
        <tr><th>Non conformes</th><td><?= $e($d['palettes']['non_conforme']) ?></td></tr>
        <tr><th>Cassées</th><td><?= $e($d['palettes']['cassee']) ?></td></tr>
        <tr><th>Total</th><td><?= $e($d['palettes']['total']) ?></td></tr>
    </table>
</body>
</html>
