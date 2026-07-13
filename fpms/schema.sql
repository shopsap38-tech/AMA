-- Base de données pour l'application FPMS (Fleet & Pallet Management System)
-- Ce script crée une base VIDE (aucune donnée de démonstration).
CREATE DATABASE IF NOT EXISTS fpms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fpms;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reparations;
DROP TABLE IF EXISTS chariot_historique;
DROP TABLE IF EXISTS palettes;
DROP TABLE IF EXISTS chariots;
DROP TABLE IF EXISTS employes;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Chariots (flotte)
-- ---------------------------------------------------------------------------
CREATE TABLE chariots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    marque VARCHAR(100) NOT NULL,
    type ENUM('electrique', 'diesel') NOT NULL,
    etat ENUM('disponible', 'maintenance', 'panne') NOT NULL DEFAULT 'disponible',
    unite ENUM('liquide', 'sachet', 'transfert', 'mp', 'chargement', 'papier', 'retour', 'dechet') NULL,
    date_mise_service DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Historique des changements d'état d'un chariot (utilisé pour le temps d'arrêt)
CREATE TABLE chariot_historique (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chariot_id INT NOT NULL,
    ancien_etat VARCHAR(20) NULL,
    nouvel_etat VARCHAR(20) NOT NULL,
    commentaire VARCHAR(255) NULL,
    date_evenement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hist_chariot FOREIGN KEY (chariot_id) REFERENCES chariots(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Palettes (chaque ligne représente un lot d'une certaine quantité)
-- ---------------------------------------------------------------------------
CREATE TABLE palettes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etat ENUM('conforme', 'non_conforme', 'cassee') NOT NULL DEFAULT 'conforme',
    quantite INT NOT NULL DEFAULT 1,
    commentaire VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Aucune donnée de démonstration : la base démarre vide.
