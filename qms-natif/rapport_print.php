<?php
require __DIR__ . '/includes/functions.php';
require_permission('report.view');
require __DIR__ . '/includes/report_query.php';

$rows = report_rows($pdo, $_GET);
?>
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>Rapport des non-conformités</title>
<style>
* { font-family: "Segoe UI", Arial, sans-serif; }
body { color: #1c2530; margin: 32px; }
.rpt-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0a6ed1; padding-bottom: 14px; margin-bottom: 20px; }
.rpt-head h1 { font-size: 22px; margin: 0; color: #0a6ed1; }
.rpt-head p { margin: 4px 0 0; color: #5b6b7c; font-size: 13px; }
.rpt-logo { font-weight: 700; font-size: 20px; color: #0b1524; }
table { width: 100%; border-collapse: collapse; font-size: 12px; }
th { background: #0a6ed1; color: #fff; text-align: left; padding: 9px 10px; }
td { padding: 8px 10px; border-bottom: 1px solid #e3e8ee; }
tr:nth-child(even) td { background: #f7f9fb; }
.badge { padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.crit { background: #fde2e2; color: #bb0000; } .maj { background: #fdeede; color: #e9730c; } .min { background: #eceff2; color: #5b6b7c; }
.rpt-footer { margin-top: 24px; font-size: 11px; color: #8a99a8; text-align: center; }
@media print { .no-print { display: none; } body { margin: 0; } }
.no-print { text-align: center; margin-bottom: 18px; }
.btn-print { background: #0a6ed1; color: #fff; border: none; padding: 10px 22px; border-radius: 8px; font-size: 14px; cursor: pointer; }
</style></head>
<body>
<div class="no-print"><button class="btn-print" onclick="window.print()">🖨️ Imprimer / Enregistrer en PDF</button></div>
<div class="rpt-head">
    <div>
        <h1>Rapport des non-conformités</h1>
        <p>Système de Management de la Qualité (QMS) · Généré le <?= date('d/m/Y à H:i') ?></p>
        <p><?= count($rows) ?> enregistrement(s)</p>
    </div>
    <div class="rpt-logo">🛡️ QMS</div>
</div>
<table>
    <thead><tr><th>Référence</th><th>Date</th><th>Service</th><th>Produit</th><th>Origine</th><th>Gravité</th><th>Statut</th><th>Responsable</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $sevCls = ['critique' => 'crit', 'majeure' => 'maj', 'mineure' => 'min'][$r['severity']] ?? 'min'; ?>
        <tr>
            <td><strong><?= e($r['reference']) ?></strong></td>
            <td><?= e(format_date($r['occurred_on'])) ?></td>
            <td><?= e($r['department_name'] ?? '—') ?></td>
            <td><?= e($r['product']) ?></td>
            <td><?= e(ui_label('origin', $r['origin'])) ?></td>
            <td><span class="badge <?= $sevCls ?>"><?= e(ui_label('severity', $r['severity'])) ?></span></td>
            <td><?= e(ui_label('status', $r['status'])) ?></td>
            <td><?= e($r['responsible_name'] ?: '—') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="rpt-footer">Document confidentiel — usage interne · QMS Quality Management System</div>
</body></html>
