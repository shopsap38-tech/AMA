<?php
/**
 * Construit et exécute la requête filtrée pour les rapports.
 * Retourne les lignes prêtes à l'export. Nécessite $pdo.
 */
function report_rows(PDO $pdo, array $get): array
{
    $where = [];
    $params = [];
    if (!empty($get['severity'])) { $where[] = 'nc.severity = :sev'; $params[':sev'] = $get['severity']; }
    if (!empty($get['status'])) { $where[] = 'nc.status = :st'; $params[':st'] = $get['status']; }
    if (!empty($get['department_id'])) { $where[] = 'nc.department_id = :dept'; $params[':dept'] = (int) $get['department_id']; }
    if (!empty($get['date_from'])) { $where[] = 'nc.occurred_on >= :df'; $params[':df'] = $get['date_from']; }
    if (!empty($get['date_to'])) { $where[] = 'nc.occurred_on <= :dt'; $params[':dt'] = $get['date_to']; }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare(
        "SELECT nc.reference, nc.occurred_on, d.name AS department_name, nc.product,
                nc.severity, nc.origin, nc.status, nc.quantity,
                CONCAT(r.first_name,' ',r.last_name) AS responsible_name
         FROM non_conformities nc
         LEFT JOIN departments d ON d.id = nc.department_id
         LEFT JOIN users r ON r.id = nc.responsible_id
         {$whereSql} ORDER BY nc.id DESC"
    );
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** En-têtes de colonnes des rapports (clé DB => libellé). */
function report_headers(): array
{
    return [
        'reference' => 'Référence', 'occurred_on' => 'Date', 'department_name' => 'Service',
        'product' => 'Produit', 'severity' => 'Gravité', 'origin' => 'Origine',
        'status' => 'Statut', 'responsible_name' => 'Responsable', 'quantity' => 'Quantité',
    ];
}
