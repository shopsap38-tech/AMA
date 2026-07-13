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
-- Employés (permanents / journaliers)
-- ---------------------------------------------------------------------------
CREATE TABLE employes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    type ENUM('permanent', 'journalier') NOT NULL DEFAULT 'permanent',
    poste VARCHAR(100) NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Chariots (flotte)
-- ---------------------------------------------------------------------------
CREATE TABLE chariots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    marque VARCHAR(100) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    type ENUM('electrique', 'diesel') NOT NULL,
    etat ENUM('disponible', 'maintenance', 'panne') NOT NULL DEFAULT 'disponible',
    operateur_id INT NULL,
    date_mise_service DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_chariot_operateur FOREIGN KEY (operateur_id) REFERENCES employes(id) ON DELETE SET NULL
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
-- Palettes
-- ---------------------------------------------------------------------------
CREATE TABLE palettes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    etat ENUM('conforme', 'non_conforme', 'cassee') NOT NULL DEFAULT 'conforme',
    controleur_id INT NULL,
    commentaire VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_palette_controleur FOREIGN KEY (controleur_id) REFERENCES employes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Réparations des palettes
CREATE TABLE reparations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    palette_id INT NOT NULL,
    employe_id INT NULL,
    description VARCHAR(255) NULL,
    resultat ENUM('reparee', 'irreparable') NOT NULL DEFAULT 'reparee',
    date_reparation DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rep_palette FOREIGN KEY (palette_id) REFERENCES palettes(id) ON DELETE CASCADE,
    CONSTRAINT fk_rep_employe FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Jeu de données de démonstration
-- ---------------------------------------------------------------------------
INSERT INTO employes (matricule, nom, prenom, type, poste) VALUES
    ('EMP-001', 'Bennani',  'Karim',   'permanent',   'Cariste'),
    ('EMP-002', 'El Amrani','Sara',    'permanent',   'Contrôleur qualité'),
    ('EMP-003', 'Toure',    'Ali',     'permanent',   'Technicien réparation'),
    ('EMP-004', 'Diallo',   'Moussa',  'journalier',  'Cariste'),
    ('EMP-005', 'Haddad',   'Yassine', 'journalier',  'Manutentionnaire'),
    ('EMP-006', 'Nakache',  'Leila',   'journalier',  'Contrôleur qualité');

INSERT INTO chariots (code, marque, modele, type, etat, operateur_id, date_mise_service) VALUES
    ('CH-001', 'Toyota',       '8FBE20',  'electrique', 'disponible',  1, '2021-03-15'),
    ('CH-002', 'Linde',        'E16',     'electrique', 'maintenance', 4, '2020-06-01'),
    ('CH-003', 'Still',        'RX60',    'electrique', 'disponible',  1, '2022-01-10'),
    ('CH-004', 'Caterpillar',  'DP25N',   'diesel',     'disponible',  4, '2019-09-20'),
    ('CH-005', 'Hyster',       'H2.5FT',  'diesel',     'panne',       5, '2018-11-05'),
    ('CH-006', 'Jungheinrich', 'DFG425',  'diesel',     'disponible',  1, '2021-07-30');

INSERT INTO chariot_historique (chariot_id, ancien_etat, nouvel_etat, commentaire, date_evenement) VALUES
    (2, 'disponible', 'maintenance', 'Révision périodique',        NOW() - INTERVAL 2 DAY),
    (5, 'disponible', 'panne',       'Fuite hydraulique',          NOW() - INTERVAL 5 DAY),
    (5, 'panne',      'maintenance', 'Diagnostic atelier',         NOW() - INTERVAL 4 DAY),
    (5, 'maintenance','panne',       'Pièce manquante',            NOW() - INTERVAL 3 DAY),
    (1, 'maintenance','disponible',  'Retour de maintenance',      NOW() - INTERVAL 10 DAY);

INSERT INTO palettes (code, etat, controleur_id, commentaire) VALUES
    ('PAL-0001', 'conforme',     2, NULL),
    ('PAL-0002', 'conforme',     2, NULL),
    ('PAL-0003', 'conforme',     6, NULL),
    ('PAL-0004', 'non_conforme', 6, 'Planche fendue'),
    ('PAL-0005', 'cassee',       2, 'Dé cassé'),
    ('PAL-0006', 'conforme',     2, NULL),
    ('PAL-0007', 'non_conforme', 6, 'Clou saillant'),
    ('PAL-0008', 'cassee',       6, 'Semelle brisée');

INSERT INTO reparations (palette_id, employe_id, description, resultat, date_reparation) VALUES
    (4, 3, 'Remplacement planche', 'reparee',     CURDATE()),
    (5, 3, 'Remplacement dé',      'reparee',     CURDATE()),
    (7, 5, 'Retrait clou',         'reparee',     CURDATE() - INTERVAL 1 DAY),
    (8, 5, 'Semelle irréparable',  'irreparable', CURDATE() - INTERVAL 1 DAY),
    (4, 3, 'Contrôle qualité',     'reparee',     CURDATE() - INTERVAL 2 DAY);
