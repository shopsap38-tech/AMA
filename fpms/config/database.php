<?php

$host = 'localhost';
$dbname = 'fpms';
$user = 'root';
$password = '';

// Seuil d'alerte : stock minimal de palettes conformes disponibles.
define('SEUIL_PALETTES', 20);

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données : ' . $e->getMessage());
}
