<?php
/**
 * Configuration de la source de données du rapport de suivi de stock.
 *
 * Quatre modes possibles (constante DATA_SOURCE) :
 *
 *   'ado'       -> connexion DIRECTE via OLE DB / COM (ADODB), SANS pilote ODBC.
 *                  Fournisseur SQLOLEDB intégré à Windows. Extension : com_dotnet.
 *
 *   'pdo_odbc'  -> connexion DIRECTE via le pilote ODBC « SQL Server » INTÉGRÉ à
 *                  Windows (MDAC), donc SANS télécharger le pilote ODBC x64.
 *                  Extension : pdo_odbc.
 *
 *   'csv'       -> l'application lit un fichier CSV exporté depuis SQL Server
 *                  (aucune connexion ni pilote nécessaire).
 *
 *   'sqlserver' -> connexion directe via PDO sqlsrv (nécessite le pilote ODBC
 *                  Driver 18 de Microsoft à télécharger — le plus performant).
 */

// ---- Choix de la source -----------------------------------------------------
const DATA_SOURCE = 'ado'; // 'ado', 'pdo_odbc', 'csv' ou 'sqlserver'
// -----------------------------------------------------------------------------

// ---- Restriction magasin ----------------------------------------------------
// Si non vide, le rapport ne montre QUE ce magasin (données, totaux, export).
// Mettez '' pour afficher tous les magasins.
const MAGASIN_FILTRE = 'MAG_FMCG';
// -----------------------------------------------------------------------------

// ---- Mode ADO / OLE DB (SQL Server sans ODBC) -------------------------------
// Fournisseur OLE DB. 'auto' teste dans l'ordre :
//   MSOLEDBSQL (récent) -> SQLNCLI11 (Native Client) -> SQLOLEDB (intégré Windows).
// SQLOLEDB est présent sur tout Windows : aucune installation nécessaire.
const ADO_PROVIDER = 'auto'; // 'auto', 'MSOLEDBSQL', 'SQLNCLI11' ou 'SQLOLEDB'
// -----------------------------------------------------------------------------

// ---- Mode pdo_odbc (SQL Server via pilote ODBC intégré à Windows) ------------
// Pilote ODBC utilisé. 'auto' teste dans l'ordre :
//   {SQL Server} (intégré à Windows, aucun téléchargement)
//   -> {SQL Server Native Client 11.0} -> {ODBC Driver 17 for SQL Server}.
// Le pilote « SQL Server » (MDAC) est présent d'origine sur tout Windows.
const PDO_ODBC_DRIVER = 'auto'; // 'auto' ou un nom exact, ex. 'SQL Server'
// -----------------------------------------------------------------------------

// ---- Mode CSV ---------------------------------------------------------------
// Fichier à lire. Exportez la vue [dbo].[V_BH_STockTracking] en CSV et placez
// le fichier ici (voir README, section « Mode CSV »).
const CSV_FILE      = __DIR__ . '/../data/stock.csv';
const CSV_DELIMITER = 'auto'; // 'auto', ';', ',' ou "\t"
// -----------------------------------------------------------------------------

// ---- Mode SQL Server (utilisé seulement si DATA_SOURCE = 'sqlserver') --------
const DB_HOST = '192.168.1.240';
const DB_PORT = 1433;
const DB_NAME = 'SBO_AMA';   // <-- nom réel de la base SAP Business One
const DB_USER = 'sa';
const DB_PASS = '1AQWXCV';
// -----------------------------------------------------------------------------

/**
 * Ouvre une connexion PDO vers SQL Server (mode 'sqlserver').
 */
function get_pdo(): PDO
{
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $drivers = PDO::getAvailableDrivers();
    $errors  = [];

    if (in_array('sqlsrv', $drivers, true)) {
        try {
            $dsn = sprintf(
                'sqlsrv:Server=%s,%d;Database=%s;TrustServerCertificate=1',
                DB_HOST, DB_PORT, DB_NAME
            );
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $errors[] = 'sqlsrv : ' . $e->getMessage();
        }
    }

    if (in_array('dblib', $drivers, true)) {
        try {
            $dsn = sprintf(
                'dblib:host=%s:%d;dbname=%s;charset=UTF-8',
                DB_HOST, DB_PORT, DB_NAME
            );
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $errors[] = 'dblib : ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $errors[] = "Aucun pilote PDO SQL Server disponible (sqlsrv ou dblib). "
            . "Pilotes PDO présents : " . implode(', ', $drivers ?: ['aucun']) . '.';
    }

    throw new RuntimeException(
        "Impossible de se connecter à SQL Server (" . DB_HOST . ").\n"
        . implode("\n", $errors)
    );
}

/**
 * Ouvre une connexion SQL Server via OLE DB / COM (ADODB), sans pilote ODBC.
 * Fonctionne uniquement sous Windows (extension com_dotnet).
 *
 * @return array{0:variant, 1:string} La connexion COM ouverte et le fournisseur utilisé.
 * @throws RuntimeException
 */
function open_ado_connection(): array
{
    if (!class_exists('COM')) {
        throw new RuntimeException(
            "L'extension PHP « com_dotnet » n'est pas disponible.\n"
            . "Elle est nécessaire pour le mode 'ado' (connexion sans ODBC) et n'existe que sous Windows.\n"
            . "Activez-la dans php.ini :  extension=com_dotnet\n"
            . "puis redémarrez Apache."
        );
    }

    $providers = ADO_PROVIDER === 'auto'
        ? ['MSOLEDBSQL', 'SQLNCLI11', 'SQLOLEDB']
        : [ADO_PROVIDER];

    $errors = [];
    foreach ($providers as $prov) {
        try {
            $conn = new COM('ADODB.Connection');
            $cs   = sprintf(
                'Provider=%s;Data Source=%s,%d;Initial Catalog=%s;User ID=%s;Password=%s;',
                $prov, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
            );
            // MSOLEDBSQL impose parfois le chiffrement : on fait confiance au certificat.
            if (stripos($prov, 'MSOLEDBSQL') !== false) {
                $cs .= 'TrustServerCertificate=yes;';
            }
            $conn->Open($cs);
            return [$conn, $prov];
        } catch (Throwable $e) {
            $errors[] = $prov . ' : ' . $e->getMessage();
        }
    }

    throw new RuntimeException(
        "Connexion OLE DB à SQL Server impossible (" . DB_HOST . ").\n"
        . "Fournisseurs testés :\n" . implode("\n", $errors)
    );
}

/**
 * Ouvre une connexion PDO vers SQL Server via le pilote ODBC « SQL Server »
 * INTÉGRÉ à Windows (MDAC), sans télécharger le pilote ODBC x64 de Microsoft.
 *
 * @return array{0:PDO, 1:string} La connexion PDO et le pilote ODBC utilisé.
 * @throws RuntimeException
 */
function open_pdo_odbc(): array
{
    if (!in_array('odbc', PDO::getAvailableDrivers(), true)) {
        throw new RuntimeException(
            "L'extension PHP « pdo_odbc » n'est pas activée.\n"
            . "Activez « extension=pdo_odbc » dans php.ini puis redémarrez Apache.\n"
            . "Pilotes PDO présents : " . implode(', ', PDO::getAvailableDrivers() ?: ['aucun']) . '.'
        );
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Pilotes ODBC essayés : le pilote « SQL Server » intégré d'abord.
    $pilotes = PDO_ODBC_DRIVER === 'auto'
        ? ['SQL Server', 'SQL Server Native Client 11.0', 'ODBC Driver 17 for SQL Server', 'ODBC Driver 18 for SQL Server']
        : [PDO_ODBC_DRIVER];

    $errors = [];
    foreach ($pilotes as $drv) {
        try {
            $dsn = sprintf(
                'odbc:Driver={%s};Server=%s,%d;Database=%s;Uid=%s;Pwd=%s;TrustServerCertificate=yes;',
                $drv, DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
            );
            return [new PDO($dsn, null, null, $options), $drv];
        } catch (PDOException $e) {
            $errors[] = '{' . $drv . '} : ' . $e->getMessage();
        }
    }

    throw new RuntimeException(
        "Connexion pdo_odbc à SQL Server impossible (" . DB_HOST . ").\n"
        . "Pilotes ODBC essayés :\n" . implode("\n", $errors)
    );
}
