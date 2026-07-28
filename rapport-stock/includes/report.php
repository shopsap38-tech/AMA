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
    if (DATA_SOURCE === 'ado') {
        $lignes = charger_depuis_ado();
    } elseif (DATA_SOURCE === 'pdo_odbc') {
        $lignes = charger_depuis_pdo_odbc();
    } elseif (DATA_SOURCE === 'sqlserver') {
        $lignes = charger_depuis_sqlserver();
    } else {
        $lignes = charger_depuis_csv();
    }

    // En mode CSV, la restriction magasin est appliquée ici (les modes SQL/ADO
    // la poussent déjà dans la requête pour ne transférer que les lignes utiles).
    if (DATA_SOURCE === 'csv' && defined('MAGASIN_FILTRE') && MAGASIN_FILTRE !== '') {
        $lignes = array_values(array_filter($lignes, static function ($l) {
            return (string) ($l['Magasin'] ?? '') === MAGASIN_FILTRE;
        }));
    }

    // Normalise les colonnes numériques quelle que soit la source (ADO, ODBC,
    // PDO ou CSV) : gère la virgule décimale et les séparateurs de milliers.
    // Évite qu'une valeur comme « 0,5 » soit tronquée à 0 par un cast (float) brut,
    // ce qui fausserait le calcul des palettes.
    foreach ($lignes as &$ligne) {
        foreach (COLONNES_NUM as $col) {
            if (isset($ligne[$col]) && $ligne[$col] !== null) {
                $ligne[$col] = parse_nombre($ligne[$col]);
            }
        }
    }
    unset($ligne);

    return $lignes;
}

/** Liste des colonnes de la vue, entre crochets, pour un SELECT. */
function colonnes_sql(): string
{
    return '[' . implode('], [', COLONNES) . ']';
}

/**
 * Clause WHERE (inline) restreignant au magasin configuré, ou '' si aucun.
 * MAGASIN_FILTRE est une constante de configuration (pas une saisie utilisateur) ;
 * les apostrophes sont malgré tout échappées par sécurité.
 */
function where_magasin_sql(): string
{
    if (!defined('MAGASIN_FILTRE') || MAGASIN_FILTRE === '') {
        return '';
    }
    return " WHERE [Magasin] = '" . str_replace("'", "''", MAGASIN_FILTRE) . "'";
}

/**
 * Lecture de la vue via OLE DB / COM (ADODB), sans pilote ODBC (Windows).
 *
 * @return array<int, array<string, mixed>>
 * @throws RuntimeException
 */
function charger_depuis_ado(): array
{
    [$conn] = open_ado_connection();

    $sql = 'SELECT ' . colonnes_sql() . ' FROM [dbo].[V_BH_STGlob]' . where_magasin_sql();

    try {
        // Curseur avant-seulement / lecture seule (le plus rapide en lecture).
        $rs = $conn->Execute($sql);

        $lignes = [];
        if (!$rs->EOF) {
            // Optimisation : on résout les objets Field UNE seule fois (par nom),
            // puis on ne lit que leur ->Value à chaque ligne. Évite une coûteuse
            // résolution COM par cellule (14 colonnes × N lignes).
            $fields = [];
            foreach (COLONNES as $col) {
                $fields[$col] = $rs->Fields->Item($col);
            }

            while (!$rs->EOF) {
                $ligne = [];
                foreach ($fields as $col => $field) {
                    $val = $field->Value;
                    if ($val === null) {
                        $ligne[$col] = null;
                    } elseif (in_array($col, COLONNES_NUM, true)) {
                        $ligne[$col] = (float) $val;
                    } else {
                        $ligne[$col] = (string) $val;
                    }
                }
                $lignes[] = $ligne;
                $rs->MoveNext();
            }
        }
        $rs->Close();
    } catch (Throwable $e) {
        throw new RuntimeException(
            "Erreur lors de la lecture de [dbo].[V_BH_STGlob] via OLE DB :\n" . $e->getMessage()
        );
    } finally {
        $conn->Close();
    }

    return $lignes;
}

/**
 * Lecture directe de la vue SQL Server (PDO sqlsrv/dblib).
 *
 * @return array<int, array<string, mixed>>
 */
function charger_depuis_sqlserver(): array
{
    return charger_via_pdo(get_pdo());
}

/**
 * Lecture via le pilote ODBC « SQL Server » intégré à Windows (pdo_odbc),
 * sans télécharger le pilote ODBC x64 de Microsoft.
 *
 * @return array<int, array<string, mixed>>
 */
function charger_depuis_pdo_odbc(): array
{
    [$pdo] = open_pdo_odbc();
    return charger_via_pdo($pdo);
}

/**
 * Exécute la requête de la vue sur une connexion PDO donnée, en appliquant
 * la restriction magasin côté serveur.
 *
 * @return array<int, array<string, mixed>>
 */
function charger_via_pdo(PDO $pdo): array
{
    $sql = 'SELECT ' . colonnes_sql() . ' FROM [dbo].[V_BH_STGlob]';

    if (defined('MAGASIN_FILTRE') && MAGASIN_FILTRE !== '') {
        $sql .= ' WHERE [Magasin] = :magasin';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':magasin' => MAGASIN_FILTRE]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
            . "Exportez la vue [dbo].[V_BH_STGlob] en CSV et placez le "
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

/**
 * Calcule l'occupation du magasin en palettes.
 *
 * Fraction de palette par article = Disponible / Qté par palette (SANS arrondi).
 * Ex. Disponible 2 / 45 => 0,0444. Les articles sans quantité par palette
 * (0 ou vide) ne sont pas comptabilisés et sont signalés.
 *
 * @param array<int, array<string, mixed>> $lignes
 * @param float $capacite Capacité totale du magasin, en emplacements palette.
 * @return array{
 *     capacite:float, occupees:float, libres:float,
 *     taux_occupation:float, taux_disponible:float, depassement:bool,
 *     nb_articles:int, nb_sans_palette:int,
 *     details:array<int,array{code:string,nom:string,disponible:float,par_palette:float,palettes:float,sans_palette:bool}>
 * }
 */
function calculer_occupation(array $lignes, float $capacite): array
{
    $occupees      = 0.0;
    $nbSansPalette = 0;
    $details       = [];

    foreach ($lignes as $l) {
        $dispo  = (float) ($l['Disponible'] ?? 0);
        $parPal = (float) ($l['U_Qte_Palette'] ?? 0);

        $sansPalette = ($parPal <= 0);
        if ($sansPalette) {
            $nbSansPalette++;
            $palettes = 0.0;
        } else {
            // Fraction de palette = division réelle, SANS arrondi.
            // Ex. Disponible 2 / 45 par palette => 0,0444 (fraction), et non 1.
            $palettes = $dispo > 0 ? $dispo / $parPal : 0.0;
        }
        $occupees += $palettes;

        $details[] = [
            'code'        => (string) ($l['Item Code'] ?? ''),
            'nom'         => (string) ($l['Item Name'] ?? ''),
            'disponible'  => $dispo,
            'par_palette' => $parPal,
            'palettes'    => $palettes,
            'sans_palette' => $sansPalette,
        ];
    }

    // Tri des détails par palettes décroissantes (plus gros consommateurs d'abord).
    usort($details, static fn($a, $b) => $b['palettes'] <=> $a['palettes']);

    $libres        = $capacite > 0 ? max($capacite - $occupees, 0.0) : 0.0;
    $tauxOcc       = $capacite > 0 ? $occupees / $capacite * 100 : 0.0;
    $tauxDispo     = $capacite > 0 ? max(100 - $tauxOcc, 0.0) : 0.0;

    return [
        'capacite'        => $capacite,
        'occupees'        => $occupees,
        'libres'          => $libres,
        'taux_occupation' => $tauxOcc,
        'taux_disponible' => $tauxDispo,
        'depassement'     => $capacite > 0 && $occupees > $capacite,
        'nb_articles'     => count($lignes),
        'nb_sans_palette' => $nbSansPalette,
        'details'         => $details,
    ];
}
