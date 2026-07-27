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
              : "Absente. Activez « extension=com_dotnet » dans php.ini puis redémarrez Apache.\n"
                . "(Disponible uniquement sous Windows.)");

    if ($aCom) {
        // 2) Ouverture de la connexion OLE DB
        try {
            [$conn, $prov] = open_ado_connection();
            etape('2. Connexion OLE DB établie', true,
                'Fournisseur utilisé : ' . $prov . ' (aucun pilote ODBC requis).');
            $conn->Close();

            // 3) Lecture de la vue
            try {
                $lignes = charger_toutes_lignes();
                etape('3. Lecture de la vue [dbo].[V_BH_STGlob]', true,
                    count($lignes) . ' ligne(s) lue(s). Tout est bon — ouvrez index.php.');
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
