<?php
/**
 * Chargement, filtrage, tri et totaux du rapport de suivi de stock.
 *
 * Fonctionne indépendamment de la source (CSV ou SQL Server) : les données
 * sont chargées sous forme de tableau de lignes, puis filtrées/triées en PHP.
 * Cela garantit un comportement identique dans les deux modes.
 */

/** Colonnes attendues, dans l'ordre du rapport. */
const COLONNES = [
    'Magasin', 'Item Code', 'Item Name', 'Disponible', 'UoM',
    'CodeBars', 'InActif', 'Poids', 'Price', 'Value', 'U_u_forcast',
    'U_Qte_Palette', 'U_u_cat', 'U_u_brand',
];

/** Colonnes numériques (tri numérique + totaux). */
const COLONNES_NUM = ['Disponible', 'Poids', 'Price', 'Value', 'U_u_forcast', 'U_Qte_Palette'];

/**
 * Lit et normalise les filtres depuis $_GET.
 *
 * @return array{magasin:string, recherche:string, masquer_inactifs:bool, tri:string, sens:string}
 */
function lire_filtres(): array
{
    $triAutorise = ['Magasin', 'Item Code', 'Item Name', 'Disponible', 'Poids',
                    'Price', 'Value', 'U_u_forcast', 'U_Qte_Palette', 'U_u_cat', 'U_u_brand'];

    $tri = $_GET['tri'] ?? 'Item Name';
    if (!in_array($tri, $triAutorise, true)) {
        $tri = 'Item Name';
    }

    $sens = strtoupper($_GET['sens'] ?? 'ASC');
    if ($sens !== 'ASC' && $sens !== 'DESC') {
        $sens = 'ASC';
    }

    return [
        'magasin'          => trim((string) ($_GET['magasin'] ?? '')),
        'categorie'        => trim((string) ($_GET['categorie'] ?? '')),
        'marque'           => trim((string) ($_GET['marque'] ?? '')),
        'recherche'        => trim((string) ($_GET['recherche'] ?? '')),
        'masquer_inactifs' => isset($_GET['masquer_inactifs']),
        'tri'              => $tri,
        'sens'             => $sens,
    ];
}

/**
 * Charge TOUTES les lignes depuis la source configurée (CSV ou SQL Server).
 *
 * @return array<int, array<string, mixed>>
 * @throws RuntimeException
 */
function charger_toutes_lignes(): array
{
    if (DATA_SOURCE === 'sqlserver') {
        return charger_depuis_sqlserver();
    }
    return charger_depuis_csv();
}

/**
 * Lecture directe de la vue SQL Server.
 *
 * @return array<int, array<string, mixed>>
 */
function charger_depuis_sqlserver(): array
{
    $pdo = get_pdo();
    $sql = 'SELECT [Magasin], [Item Code], [Item Name], [Disponible], [UoM],
                   [CodeBars], [InActif], [Poids], [Price], [Value], [U_u_forcast],
                   [U_Qte_Palette], [U_u_cat], [U_u_brand]
            FROM [dbo].[V_BH_STGlob]';
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lecture d'un fichier CSV exporté de la vue.
 * Tolère le BOM UTF-8, détecte le séparateur et mappe par nom d'en-tête.
 *
 * @return array<int, array<string, mixed>>
 * @throws RuntimeException
 */
function charger_depuis_csv(): array
{
    $fichier = CSV_FILE;

    if (!is_file($fichier)) {
        throw new RuntimeException(
            "Fichier CSV introuvable :\n" . $fichier . "\n\n"
            . "Exportez la vue [dbo].[V_BH_STockTracking] en CSV et placez le "
            . "fichier à cet emplacement (voir README, section « Mode CSV »)."
        );
    }
    if (!is_readable($fichier)) {
        throw new RuntimeException("Fichier CSV présent mais non lisible :\n" . $fichier);
    }

    $contenu = file_get_contents($fichier);
    if ($contenu === false || $contenu === '') {
        throw new RuntimeException("Fichier CSV vide ou illisible :\n" . $fichier);
    }

    // Retire le BOM UTF-8 éventuel.
    $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu);

    $lignesTexte = preg_split('/\r\n|\r|\n/', $contenu);
    // Supprime les lignes vides finales.
    while (!empty($lignesTexte) && trim(end($lignesTexte)) === '') {
        array_pop($lignesTexte);
    }
    if (empty($lignesTexte)) {
        throw new RuntimeException("Fichier CSV sans données :\n" . $fichier);
    }

    $delim   = detecter_delimiteur($lignesTexte[0]);
    $entetes = str_getcsv(array_shift($lignesTexte), $delim);
    $entetes = array_map(static fn($h) => trim((string) $h), $entetes);

    // Index colonne -> nom canonique (insensible à la casse / espaces).
    $map = [];
    foreach ($entetes as $i => $nom) {
        foreach (COLONNES as $canon) {
            if (mb_strtolower($nom) === mb_strtolower($canon)) {
                $map[$canon] = $i;
            }
        }
    }

    if (!isset($map['Item Code']) && !isset($map['Item Name'])) {
        throw new RuntimeException(
            "En-têtes CSV non reconnus. Attendu (au moins) « Item Code » / « Item Name ».\n"
            . "En-têtes trouvés : " . implode(', ', $entetes) . "\n"
            . "Séparateur détecté : « " . ($delim === "\t" ? 'TAB' : $delim) . " »."
        );
    }

    $lignes = [];
    foreach ($lignesTexte as $texte) {
        if (trim($texte) === '') {
            continue;
        }
        $champs = str_getcsv($texte, $delim);
        $ligne  = [];
        foreach (COLONNES as $canon) {
            $val = isset($map[$canon], $champs[$map[$canon]]) ? $champs[$map[$canon]] : null;
            if ($val !== null && in_array($canon, COLONNES_NUM, true)) {
                $val = parse_nombre($val);
            }
            $ligne[$canon] = $val;
        }
        $lignes[] = $ligne;
    }

    return $lignes;
}

/** Détecte le séparateur d'une ligne d'en-tête. */
function detecter_delimiteur(string $entete): string
{
    if (CSV_DELIMITER !== 'auto') {
        return CSV_DELIMITER;
    }
    $candidats = [';' => substr_count($entete, ';'),
                  ',' => substr_count($entete, ','),
                  "\t" => substr_count($entete, "\t")];
    arsort($candidats);
    $meilleur = array_key_first($candidats);
    return $candidats[$meilleur] > 0 ? $meilleur : ';';
}

/**
 * Convertit une chaîne (formats FR ou US) en nombre.
 * Gère les espaces (milliers), la virgule ou le point décimal.
 */
function parse_nombre($valeur): float
{
    $s = trim((string) $valeur);
    if ($s === '') {
        return 0.0;
    }
    // Retire espaces normaux et insécables (séparateurs de milliers).
    $s = str_replace(["\xC2\xA0", ' '], '', $s);

    $aVirgule = strpos($s, ',') !== false;
    $aPoint   = strpos($s, '.') !== false;

    if ($aVirgule && $aPoint) {
        // Le dernier séparateur rencontré est le séparateur décimal.
        if (strrpos($s, ',') > strrpos($s, '.')) {
            $s = str_replace('.', '', $s);   // point = milliers
            $s = str_replace(',', '.', $s);  // virgule = décimal
        } else {
            $s = str_replace(',', '', $s);   // virgule = milliers
        }
    } elseif ($aVirgule) {
        $s = str_replace(',', '.', $s);      // virgule = décimal
    }

    return is_numeric($s) ? (float) $s : 0.0;
}

/**
 * Liste distincte, triée, des valeurs d'une colonne dans les données chargées.
 *
 * @param array<int, array<string, mixed>> $lignes
 * @return string[]
 */
function liste_valeurs(array $lignes, string $colonne): array
{
    $vues = [];
    foreach ($lignes as $l) {
        $v = trim((string) ($l[$colonne] ?? ''));
        if ($v !== '') {
            $vues[$v] = true;
        }
    }
    $liste = array_keys($vues);
    natcasesort($liste);
    return array_values($liste);
}

/**
 * Liste distincte des magasins (raccourci rétro-compatible).
 *
 * @param array<int, array<string, mixed>> $lignes
 * @return string[]
 */
function liste_magasins(array $lignes): array
{
    return liste_valeurs($lignes, 'Magasin');
}

/**
 * Applique les filtres puis le tri sur les lignes chargées.
 *
 * @param array<int, array<string, mixed>> $lignes
 * @param array $filtres Résultat de lire_filtres()
 * @return array<int, array<string, mixed>>
 */
function filtrer_et_trier(array $lignes, array $filtres): array
{
    $recherche = mb_strtolower($filtres['recherche']);

    $lignes = array_values(array_filter($lignes, static function ($l) use ($filtres, $recherche) {
        if ($filtres['magasin'] !== '' && (string) ($l['Magasin'] ?? '') !== $filtres['magasin']) {
            return false;
        }
        if ($filtres['categorie'] !== '' && (string) ($l['U_u_cat'] ?? '') !== $filtres['categorie']) {
            return false;
        }
        if ($filtres['marque'] !== '' && (string) ($l['U_u_brand'] ?? '') !== $filtres['marque']) {
            return false;
        }
        if ($recherche !== '') {
            $foin = mb_strtolower(
                (string) ($l['Item Code'] ?? '') . ' '
                . (string) ($l['Item Name'] ?? '') . ' '
                . (string) ($l['CodeBars'] ?? '')
            );
            if (strpos($foin, $recherche) === false) {
                return false;
            }
        }
        if ($filtres['masquer_inactifs'] && strtoupper((string) ($l['InActif'] ?? '')) === 'Y') {
            return false;
        }
        return true;
    }));

    $col     = $filtres['tri'];
    $numeric = in_array($col, COLONNES_NUM, true);
    $facteur = $filtres['sens'] === 'DESC' ? -1 : 1;

    usort($lignes, static function ($a, $b) use ($col, $numeric, $facteur) {
        if ($numeric) {
            $cmp = ((float) ($a[$col] ?? 0)) <=> ((float) ($b[$col] ?? 0));
        } else {
            $cmp = strcasecmp((string) ($a[$col] ?? ''), (string) ($b[$col] ?? ''));
        }
        return $cmp * $facteur;
    });

    return $lignes;
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
    foreach ($lignes as $l) {
        $totaux['disponible'] += (float) ($l['Disponible'] ?? 0);
        $totaux['value']      += (float) ($l['Value'] ?? 0);
    }
    return $totaux;
}
