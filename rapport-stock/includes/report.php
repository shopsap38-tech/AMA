<?php
/**
 * Construction et exécution de la requête du rapport de suivi de stock.
 *
 * Centralise la lecture des filtres (GET) et la requête SQL afin que la page
 * du rapport (index.php) et l'export CSV (export.php) restent cohérents.
 */

/**
 * Lit et normalise les filtres depuis $_GET.
 *
 * @return array{magasin:string, recherche:string, masquer_inactifs:bool, tri:string, sens:string}
 */
function lire_filtres(): array
{
    $colonnesTri = [
        'Magasin', 'Item Code', 'Item Name', 'Disponible',
        'Poids', 'Price', 'Value', 'U_u_forcast',
    ];

    $tri = $_GET['tri'] ?? 'Item Name';
    if (!in_array($tri, $colonnesTri, true)) {
        $tri = 'Item Name';
    }

    $sens = strtoupper($_GET['sens'] ?? 'ASC');
    if ($sens !== 'ASC' && $sens !== 'DESC') {
        $sens = 'ASC';
    }

    return [
        'magasin'          => trim((string) ($_GET['magasin'] ?? '')),
        'recherche'        => trim((string) ($_GET['recherche'] ?? '')),
        'masquer_inactifs' => isset($_GET['masquer_inactifs']),
        'tri'              => $tri,
        'sens'             => $sens,
    ];
}

/**
 * Récupère la liste distincte des magasins pour le filtre déroulant.
 *
 * @return string[]
 */
function liste_magasins(PDO $pdo): array
{
    $sql = 'SELECT DISTINCT [Magasin] FROM [dbo].[V_BH_STockTracking]
            WHERE [Magasin] IS NOT NULL ORDER BY [Magasin]';
    return $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Exécute la requête du rapport en fonction des filtres et retourne les lignes.
 *
 * @param array $filtres Résultat de lire_filtres()
 * @return array<int, array<string, mixed>>
 */
function executer_rapport(PDO $pdo, array $filtres): array
{
    $where  = [];
    $params = [];

    if ($filtres['magasin'] !== '') {
        $where[]            = '[Magasin] = :magasin';
        $params[':magasin'] = $filtres['magasin'];
    }

    if ($filtres['recherche'] !== '') {
        $where[]              = '([Item Code] LIKE :recherche OR [Item Name] LIKE :recherche OR [CodeBars] LIKE :recherche)';
        $params[':recherche'] = '%' . $filtres['recherche'] . '%';
    }

    if ($filtres['masquer_inactifs']) {
        // Dans SAP B1, InActif vaut 'Y' lorsque l'article est inactif.
        $where[] = "([InActif] IS NULL OR [InActif] <> 'Y')";
    }

    $sql = 'SELECT [Magasin], [Item Code], [Item Name], [Disponible], [UoM],
                   [CodeBars], [InActif], [Poids], [Price], [Value], [U_u_forcast]
            FROM [dbo].[V_BH_STockTracking]';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    // $filtres['tri'] et 'sens' sont validés en liste blanche dans lire_filtres().
    $sql .= sprintf(' ORDER BY [%s] %s', $filtres['tri'], $filtres['sens']);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calcule les totaux affichés en pied de rapport.
 *
 * @param array<int, array<string, mixed>> $lignes
 * @return array{nb:int, disponible:float, value:float}
 */
function calculer_totaux(array $lignes): array
{
    $totaux = ['nb' => count($lignes), 'disponible' => 0.0, 'value' => 0.0];
    foreach ($lignes as $ligne) {
        $totaux['disponible'] += (float) ($ligne['Disponible'] ?? 0);
        $totaux['value']      += (float) ($ligne['Value'] ?? 0);
    }
    return $totaux;
}
