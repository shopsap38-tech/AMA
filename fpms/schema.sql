-- Base de données pour l'application FPMS (Fleet & Pallet Management System)
-- Réimporter ce fichier réinitialise entièrement la base et les données de démo.
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
    code VARCHAR(50) NOT NULL UNIQUE,
    marque VARCHAR(100) NOT NULL,
    type ENUM('electrique', 'diesel') NOT NULL,
    etat ENUM('disponible', 'maintenance', 'panne') NOT NULL DEFAULT 'disponible',
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
    code VARCHAR(50) NOT NULL UNIQUE,
    etat ENUM('conforme', 'non_conforme', 'cassee') NOT NULL DEFAULT 'conforme',
    quantite INT NOT NULL DEFAULT 1,
    commentaire VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Jeu de données de démonstration
-- ---------------------------------------------------------------------------
INSERT INTO chariots (code, marque, type, etat, date_mise_service) VALUES
    ('CH-001', 'Toyota',       'electrique', 'disponible',  CURDATE() - INTERVAL 5 DAY),
    ('CH-002', 'Linde',        'electrique', 'maintenance', CURDATE() - INTERVAL 20 DAY),
    ('CH-003', 'Still',        'electrique', 'disponible',  CURDATE() - INTERVAL 2 MONTH),
    ('CH-004', 'Caterpillar',  'diesel',     'disponible',  CURDATE() - INTERVAL 8 MONTH),
    ('CH-005', 'Hyster',       'diesel',     'panne',       CURDATE() - INTERVAL 2 YEAR),
    ('CH-006', 'Jungheinrich', 'diesel',     'disponible',  CURDATE() - INTERVAL 1 YEAR);

INSERT INTO chariot_historique (chariot_id, ancien_etat, nouvel_etat, commentaire, date_evenement) VALUES
    (2, 'disponible', 'maintenance', 'Révision périodique',        NOW() - INTERVAL 2 DAY),
    (5, 'disponible', 'panne',       'Fuite hydraulique',          NOW() - INTERVAL 5 DAY),
    (5, 'panne',      'maintenance', 'Diagnostic atelier',         NOW() - INTERVAL 4 DAY),
    (5, 'maintenance','panne',       'Pièce manquante',            NOW() - INTERVAL 3 DAY),
    (1, 'maintenance','disponible',  'Retour de maintenance',      NOW() - INTERVAL 10 DAY);

INSERT INTO palettes (code, etat, quantite, commentaire, created_at) VALUES
    ('PAL-0001', 'conforme',     40, NULL,               CURDATE() - INTERVAL 1 DAY),
    ('PAL-0002', 'conforme',     35, NULL,               CURDATE() - INTERVAL 3 DAY),
    ('PAL-0003', 'conforme',     28, NULL,               CURDATE() - INTERVAL 10 DAY),
    ('PAL-0004', 'non_conforme', 12, 'Planches fendues', CURDATE() - INTERVAL 1 MONTH),
    ('PAL-0005', 'cassee',        8, 'Dés cassés',       CURDATE() - INTERVAL 2 MONTH),
    ('PAL-0006', 'conforme',     22, NULL,               CURDATE() - INTERVAL 5 MONTH),
    ('PAL-0007', 'non_conforme', 15, 'Clous saillants',  CURDATE() - INTERVAL 1 YEAR),
    ('PAL-0008', 'cassee',        6, 'Semelles brisées', CURDATE() - INTERVAL 2 YEAR);
