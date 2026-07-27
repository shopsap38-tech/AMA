<?php
/**
 * Page de diagnostic — vérifie étape par étape pourquoi la connexion à
 * SQL Server échoue. À supprimer une fois que tout fonctionne.
 *
 * Ouvrez : http://localhost/rapport-stock/test.php
 */
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=UTF-8');

/** Affiche une ligne de résultat. */
function etape(string $titre, bool $ok, string $detail = ''): void
{
    $couleur = $ok ? '#2b8a3e' : '#c92a2a';
    $icone   = $ok ? '✔' : '✘';
    echo '<div style="padding:.6rem .9rem;margin:.4rem 0;border-left:4px solid '
        . $couleur . ';background:#fff;border-radius:4px">';
    echo '<strong style="color:' . $couleur . '">' . $icone . ' '
        . htmlspecialchars($titre) . '</strong>';
    if ($detail !== '') {
        echo '<div style="color:#616e7c;font-size:.9rem;margin-top:.3rem">'
            . nl2br(htmlspecialchars($detail)) . '</div>';
    }
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diagnostic — connexion SQL Server</title>
    <style>
        body{font-family:-apple-system,"Segoe UI",Roboto,sans-serif;background:#f4f5f7;
             color:#1f2933;max-width:820px;margin:2rem auto;padding:0 1.5rem}
        code{background:#eef1f4;padding:.1rem .3rem;border-radius:3px}
        h1{font-size:1.4rem}
    </style>
</head>
<body>
<h1>Diagnostic du rapport de suivi de stock</h1>
<p style="color:#616e7c">Source configurée : <code><?= htmlspecialchars(DATA_SOURCE) ?></code></p>

<?php
require_once __DIR__ . '/includes/report.php';

// ============================================================================
// MODE CSV
// ============================================================================
if (DATA_SOURCE === 'csv') {
    etape('Mode CSV — aucun pilote SQL Server requis', true,
        'Fichier attendu : ' . CSV_FILE);

    $existe = is_file(CSV_FILE);
    etape('1. Fichier CSV présent', $existe,
        $existe ? CSV_FILE : "Introuvable :\n" . CSV_FILE
            . "\nExportez la vue en CSV et placez le fichier ici (voir README).");

    if ($existe) {
        etape('2. Fichier CSV lisible', is_readable(CSV_FILE),
            is_readable(CSV_FILE) ? 'OK' : 'Droits de lecture insuffisants.');

        try {
            $lignes = charger_toutes_lignes();
            etape('3. Lecture et analyse du CSV', true,
                count($lignes) . ' ligne(s) de données lue(s).');

            if ($lignes) {
                $premier = $lignes[0];
                $apercu  = [];
                foreach (['Item Code', 'Item Name', 'Magasin', 'Disponible', 'Value'] as $c) {
                    $apercu[] = $c . ' = ' . (string) ($premier[$c] ?? '—');
                }
                etape('4. Colonnes reconnues (1re ligne)', true, implode("\n", $apercu)
                    . "\n\nTout est bon — vous pouvez ouvrir index.php.");
            } else {
                etape('4. Données', false, 'Le fichier ne contient aucune ligne de données.');
            }
        } catch (Throwable $e) {
            etape('3. Lecture et analyse du CSV', false, $e->getMessage());
        }
    }
    echo '<p style="color:#97a3af;font-size:.85rem;margin-top:2rem">'
        . 'Pensez à supprimer <code>test.php</code> une fois le diagnostic terminé.</p>';
    echo '</body></html>';
    exit;
}

// ============================================================================
// MODE pdo_odbc (pilote ODBC « SQL Server » intégré à Windows)
// ============================================================================
if (DATA_SOURCE === 'pdo_odbc') {
    echo '<p style="color:#616e7c">Serveur <code>' . htmlspecialchars(DB_HOST) . ':' . DB_PORT
        . '</code> — base <code>' . htmlspecialchars(DB_NAME) . '</code> — pilote <code>'
        . htmlspecialchars(PDO_ODBC_DRIVER) . '</code></p>';

    // 1) Extension pdo_odbc
    $aOdbc = in_array('odbc', PDO::getAvailableDrivers(), true);
    etape('1. Extension PHP pdo_odbc disponible', $aOdbc,
        $aOdbc ? 'OK.' : "Absente. Activez « extension=pdo_odbc » dans php.ini (voir chemin ci-dessous), "
            . "puis redémarrez Apache.\nPilotes PDO présents : "
            . implode(', ', PDO::getAvailableDrivers() ?: ['aucun']) . '.');

    if (!$aOdbc) {
        etape('   → Où activer pdo_odbc', false,
            "php.ini chargé par CE PHP (celui d'Apache) :\n   " . (php_ini_loaded_file() ?: '(inconnu)') . "\n"
            . "Décommentez « extension=pdo_odbc », enregistrez, puis redémarrez Apache.");
    } else {
        // 2) Connexion via le pilote intégré (chronométrée)
        try {
            $t0 = microtime(true);
            [$pdo, $drv] = open_pdo_odbc();
            $msConn = (microtime(true) - $t0) * 1000;
            etape('2. Connexion pdo_odbc établie', true, sprintf(
                "Pilote ODBC utilisé : {%s}\nTemps de connexion : %.0f ms", $drv, $msConn));

            // 3) Lecture de la vue (chronométrée)
            try {
                $t1 = microtime(true);
                $lignes = charger_toutes_lignes();
                $msLect = (microtime(true) - $t1) * 1000;
                $scope = (defined('MAGASIN_FILTRE') && MAGASIN_FILTRE !== '')
                    ? ' (restreint au magasin ' . MAGASIN_FILTRE . ')' : '';
                etape('3. Lecture de la vue [dbo].[V_BH_STGlob]', true, sprintf(
                    "%d ligne(s) lue(s)%s.\nTemps de lecture : %.0f ms\nTout est bon — ouvrez index.php.",
                    count($lignes), $scope, $msLect));
            } catch (Throwable $e) {
                etape('3. Lecture de la vue [dbo].[V_BH_STGlob]', false, $e->getMessage());
            }
        } catch (Throwable $e) {
            etape('2. Connexion pdo_odbc établie', false, $e->getMessage());
        }
    }

    echo '<p style="color:#97a3af;font-size:.85rem;margin-top:2rem">'
        . 'Pensez à supprimer <code>test.php</code> une fois le diagnostic terminé.</p>';
    echo '</body></html>';
    exit;
}

// ============================================================================
// MODE ADO / OLE DB (SQL Server sans ODBC)
// ============================================================================
if (DATA_SOURCE === 'ado') {
    echo '<p style="color:#616e7c">Serveur <code>' . htmlspecialchars(DB_HOST) . ':' . DB_PORT
        . '</code> — base <code>' . htmlspecialchars(DB_NAME) . '</code> — fournisseur <code>'
        . htmlspecialchars(ADO_PROVIDER) . '</code></p>';

    // 1) Extension com_dotnet
    $aCom = class_exists('COM');
    etape('1. Extension PHP com_dotnet disponible', $aCom,
        $aCom ? 'OK (connexion OLE DB possible sans pilote ODBC).'
              : "Absente. Voir le détail ci-dessous pour savoir quel php.ini modifier.");

    if (!$aCom) {
        // Indique précisément où activer l'extension.
        $ini = php_ini_loaded_file() ?: '(inconnu)';
        $dir = (string) ini_get('extension_dir');
        $dll = $dir !== '' ? rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . 'php_com_dotnet.dll' : '';
        $dllExiste = $dll !== '' && @file_exists($dll);
        $estWindows = stripos(PHP_OS, 'WIN') === 0;

        etape('   → Où activer com_dotnet', false,
            "Système : " . PHP_OS . ($estWindows ? '' : "  (⚠ non-Windows : COM/OLE DB indisponible)") . "\n"
            . "php.ini chargé par CE PHP (celui d'Apache) :\n   " . $ini . "\n"
            . "extension_dir :\n   " . ($dir !== '' ? $dir : '(non défini)') . "\n"
            . "DLL php_com_dotnet.dll présente : " . ($dllExiste ? 'OUI' : 'NON')
              . ($dll !== '' ? "\n   (" . $dll . ")" : '') . "\n\n"
            . "Action : ouvrez le fichier php.ini ci-dessus, décommentez la ligne\n"
            . "   extension=com_dotnet\n"
            . "enregistrez, puis redémarrez complètement Apache dans Laragon.\n\n"
            . "Alternative sans extension PHP : mode CSV + script outils/export-stock.ps1.");
    }

    if ($aCom) {
        // 2) Ouverture de la connexion OLE DB (chronométrée)
        try {
            $t0 = microtime(true);
            [$conn, $prov] = open_ado_connection();
            $msConn = (microtime(true) - $t0) * 1000;
            $conn->Close();

            etape('2. Connexion OLE DB établie', true, sprintf(
                "Fournisseur utilisé : %s\nTemps de connexion : %.0f ms%s",
                $prov, $msConn,
                (ADO_PROVIDER === 'auto' && $prov !== 'MSOLEDBSQL')
                    ? "\n\nAstuce perf : figez ce fournisseur dans config/database.php\n"
                      . "   const ADO_PROVIDER = '" . $prov . "';\n"
                      . "pour éviter d'essayer les fournisseurs absents à chaque page."
                    : ''
            ));

            // 3) Lecture de la vue (chronométrée)
            try {
                $t1 = microtime(true);
                $lignes = charger_toutes_lignes();
                $msLect = (microtime(true) - $t1) * 1000;

                $scope = (defined('MAGASIN_FILTRE') && MAGASIN_FILTRE !== '')
                    ? ' (restreint au magasin ' . MAGASIN_FILTRE . ')' : '';

                etape('3. Lecture de la vue [dbo].[V_BH_STGlob]', true, sprintf(
                    "%d ligne(s) lue(s)%s.\nTemps de lecture : %.0f ms\n\n%s",
                    count($lignes), $scope, $msLect,
                    $msConn > $msLect
                        ? "→ Le temps est dominé par la CONNEXION : figez ADO_PROVIDER (voir étape 2)."
                        : "→ Le temps est dominé par la LECTURE : la restriction magasin réduit déjà les lignes ; pour aller plus vite encore, envisagez le mode pdo_sqlsrv."
                ));
            } catch (Throwable $e) {
                etape('3. Lecture de la vue [dbo].[V_BH_STGlob]', false, $e->getMessage());
            }
        } catch (Throwable $e) {
            etape('2. Connexion OLE DB établie', false, $e->getMessage());
        }
    }

    echo '<p style="color:#97a3af;font-size:.85rem;margin-top:2rem">'
        . 'Pensez à supprimer <code>test.php</code> une fois le diagnostic terminé.</p>';
    echo '</body></html>';
    exit;
}

// ============================================================================
// MODE SQL SERVER
// ============================================================================
echo '<p style="color:#616e7c">Serveur <code>' . htmlspecialchars(DB_HOST) . ':' . DB_PORT
    . '</code> — base <code>' . htmlspecialchars(DB_NAME) . '</code></p>';

// 1) Extension PHP pdo_sqlsrv / pdo_dblib
$drivers = PDO::getAvailableDrivers();
$aSqlsrv = in_array('sqlsrv', $drivers, true);
$aDblib  = in_array('dblib', $drivers, true);
etape(
    '1. Pilote PDO SQL Server chargé dans PHP',
    $aSqlsrv || $aDblib,
    'Pilotes PDO disponibles : ' . implode(', ', $drivers ?: ['aucun']) . "\n"
    . ($aSqlsrv ? "sqlsrv : présent\n" : "sqlsrv : absent\n")
    . ($aDblib ? 'dblib : présent' : 'dblib : absent')
);

// 2) Pilote ODBC système (nécessaire pour sqlsrv)
$odbcOk   = false;
$odbcInfo = "L'extension PHP sqlsrv n'est pas chargée : on ne peut pas tester l'ODBC.";
if (function_exists('odbc_data_source') || extension_loaded('pdo_odbc')) {
    // pas fiable partout ; on se base plutôt sur la tentative de connexion ci-dessous
}
if ($aSqlsrv) {
    // On tente une connexion très courte pour distinguer « ODBC manquant » du reste.
    try {
        $dsn = sprintf('sqlsrv:Server=%s,%d;Database=%s;TrustServerCertificate=1;LoginTimeout=3',
            DB_HOST, DB_PORT, DB_NAME);
        new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $odbcOk   = true;
        $odbcInfo = 'Le pilote ODBC répond et la connexion aboutit.';
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'ODBC Driver') !== false) {
            $odbcOk   = false;
            $odbcInfo = "Pilote ODBC Microsoft ABSENT.\n"
                . "Installez « ODBC Driver 18 for SQL Server » (x64) puis redémarrez Apache :\n"
                . 'https://go.microsoft.com/fwlink/?LinkId=163712' . "\n\nDétail : " . $msg;
        } else {
            // ODBC présent, mais autre problème (login, base, réseau) → étape suivante
            $odbcOk   = true;
            $odbcInfo = "Le pilote ODBC est présent (l'erreur ne le concerne pas).";
        }
    }
    etape('2. Pilote ODBC Microsoft installé (Windows)', $odbcOk, $odbcInfo);
}

// 3) Connexion complète + lecture de la vue
try {
    $pdo = get_pdo();
    etape('3. Connexion à la base établie', true,
        'Authentification et ouverture de la base « ' . DB_NAME . ' » réussies.');

    try {
        $n = $pdo->query('SELECT COUNT(*) FROM [dbo].[V_BH_STGlob]')->fetchColumn();
        etape('4. Lecture de la vue [dbo].[V_BH_STGlob]', true,
            $n . ' ligne(s) trouvée(s). Tout est bon — vous pouvez ouvrir index.php.');
    } catch (Throwable $e) {
        etape('4. Lecture de la vue [dbo].[V_BH_STGlob]', false,
            "La connexion marche mais la vue est introuvable ou inaccessible.\n"
            . 'Vérifiez son nom / les droits de l\'utilisateur.' . "\n\n" . $e->getMessage());
    }
} catch (Throwable $e) {
    etape('3. Connexion à la base établie', false, $e->getMessage());
}
?>

<p style="color:#97a3af;font-size:.85rem;margin-top:2rem">
   Pensez à supprimer <code>test.php</code> une fois le diagnostic terminé.</p>
</body>
</html>
