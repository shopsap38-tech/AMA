<?php
/**
 * Connexion à SQL Server (SAP Business One) pour le rapport de suivi de stock.
 *
 * La vue lue est [dbo].[V_BH_STockTracking] sur le serveur 192.168.1.240.
 *
 * Deux pilotes PDO sont possibles :
 *   - sqlsrv  : pilote officiel Microsoft (Windows / Laragon avec pdo_sqlsrv activé)
 *   - dblib   : FreeTDS (Linux / macOS)
 * Le code essaie sqlsrv en premier, puis dblib en secours.
 *
 * Adaptez les valeurs ci-dessous à votre environnement (surtout DB_NAME).
 */

// ---- Paramètres de connexion ------------------------------------------------
const DB_HOST = '192.168.1.240'; // Serveur SQL Server
const DB_PORT = 1433;            // Port SQL Server par défaut
const DB_NAME = 'SBO_AMA';       // <-- Nom de la base SAP Business One à ADAPTER
const DB_USER = 'sa';            // Utilisateur SQL Server
const DB_PASS = '1AQWXCV';       // Mot de passe SQL Server
// -----------------------------------------------------------------------------

/**
 * Ouvre une connexion PDO vers SQL Server.
 *
 * @return PDO
 */
function get_pdo(): PDO
{
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $drivers = PDO::getAvailableDrivers();
    $errors  = [];

    // 1) Pilote Microsoft sqlsrv (recommandé sous Windows / Laragon)
    if (in_array('sqlsrv', $drivers, true)) {
        try {
            $dsn = sprintf(
                'sqlsrv:Server=%s,%d;Database=%s;TrustServerCertificate=1',
                DB_HOST,
                DB_PORT,
                DB_NAME
            );
            return new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $errors[] = 'sqlsrv : ' . $e->getMessage();
        }
    }

    // 2) Pilote FreeTDS dblib (Linux / macOS)
    if (in_array('dblib', $drivers, true)) {
        try {
            $dsn = sprintf(
                'dblib:host=%s:%d;dbname=%s;charset=UTF-8',
                DB_HOST,
                DB_PORT,
                DB_NAME
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
