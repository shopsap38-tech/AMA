-- Base de données pour l'application FPMS (Fleet & Pallet Management System)
CREATE DATABASE IF NOT EXISTS fpms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fpms;

-- ---------------------------------------------------------------------------
-- Chariots (flotte)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS chariots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    marque VARCHAR(100) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    type ENUM('electrique', 'diesel') NOT NULL,
    etat ENUM('disponible', 'maintenance', 'panne') NOT NULL DEFAULT 'disponible',
    date_mise_service DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Historique des changements d'état d'un chariot (utilisé pour le temps d'arrêt)
CREATE TABLE IF NOT EXISTS chariot_historique (
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
CREATE TABLE IF NOT EXISTS palettes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    etat ENUM('conforme', 'non_conforme', 'cassee') NOT NULL DEFAULT 'conforme',
    commentaire VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Réparations des palettes
CREATE TABLE IF NOT EXISTS reparations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    palette_id INT NOT NULL,
    description VARCHAR(255) NULL,
    resultat ENUM('reparee', 'irreparable') NOT NULL DEFAULT 'reparee',
    date_reparation DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rep_palette FOREIGN KEY (palette_id) REFERENCES palettes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Jeu de données de démonstration
-- ---------------------------------------------------------------------------
INSERT INTO chariots (code, marque, modele, type, etat, date_mise_service) VALUES
    ('CH-001', 'Toyota',   '8FBE20',  'electrique', 'disponible',  '2021-03-15'),
    ('CH-002', 'Linde',    'E16',     'electrique', 'maintenance', '2020-06-01'),
    ('CH-003', 'Still',    'RX60',    'electrique', 'disponible',  '2022-01-10'),
    ('CH-004', 'Caterpillar', 'DP25N', 'diesel',    'disponible',  '2019-09-20'),
    ('CH-005', 'Hyster',   'H2.5FT',  'diesel',     'panne',       '2018-11-05'),
    ('CH-006', 'Jungheinrich', 'DFG425', 'diesel',  'disponible',  '2021-07-30');

INSERT INTO chariot_historique (chariot_id, ancien_etat, nouvel_etat, commentaire, date_evenement) VALUES
    (2, 'disponible', 'maintenance', 'Révision périodique',        NOW() - INTERVAL 2 DAY),
    (5, 'disponible', 'panne',       'Fuite hydraulique',          NOW() - INTERVAL 5 DAY),
    (5, 'panne',      'maintenance', 'Diagnostic atelier',         NOW() - INTERVAL 4 DAY),
    (5, 'maintenance','panne',       'Pièce manquante',            NOW() - INTERVAL 3 DAY),
    (1, 'maintenance','disponible',  'Retour de maintenance',      NOW() - INTERVAL 10 DAY);

INSERT INTO palettes (code, etat, commentaire) VALUES
    ('PAL-0001', 'conforme',     NULL),
    ('PAL-0002', 'conforme',     NULL),
    ('PAL-0003', 'conforme',     NULL),
    ('PAL-0004', 'non_conforme', 'Planche fendue'),
    ('PAL-0005', 'cassee',       'Dé cassé'),
    ('PAL-0006', 'conforme',     NULL),
    ('PAL-0007', 'non_conforme', 'Clou saillant'),
    ('PAL-0008', 'cassee',       'Semelle brisée');

INSERT INTO reparations (palette_id, description, resultat, date_reparation) VALUES
    (4, 'Remplacement planche', 'reparee',     CURDATE()),
    (5, 'Remplacement dé',      'reparee',     CURDATE()),
    (7, 'Retrait clou',         'reparee',     CURDATE() - INTERVAL 1 DAY),
    (8, 'Semelle irréparable',  'irreparable', CURDATE() - INTERVAL 1 DAY),
    (4, 'Contrôle qualité',     'reparee',     CURDATE() - INTERVAL 2 DAY);
