<?php
/**
 * Configuration de la source de données du rapport de suivi de stock.
 *
 * Deux modes possibles (constante DATA_SOURCE) :
 *
 *   'csv'       -> l'application lit un fichier CSV exporté depuis SQL Server
 *                  (aucune connexion ni pilote nécessaire). RECOMMANDÉ si le
 *                  pilote ODBC Microsoft n'est pas installé.
 *
 *   'sqlserver' -> connexion directe à SQL Server via PDO (nécessite le pilote
 *                  ODBC Microsoft + l'extension pdo_sqlsrv).
 */

// ---- Choix de la source -----------------------------------------------------
const DATA_SOURCE = 'csv'; // 'csv' ou 'sqlserver'
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
