-- Migration : ajoute la gestion des employés (permanents / journaliers)
-- et les liens vers chariots, palettes et réparations.
-- À exécuter UNE SEULE FOIS sur une base FPMS existante que l'on souhaite conserver.
-- (Pour une base neuve, importez simplement schema.sql.)
USE fpms;

CREATE TABLE IF NOT EXISTS employes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    type ENUM('permanent', 'journalier') NOT NULL DEFAULT 'permanent',
    poste VARCHAR(100) NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Ajout des colonnes de rattachement (ignorer l'erreur si la colonne existe déjà).
ALTER TABLE chariots    ADD COLUMN operateur_id  INT NULL AFTER etat;
ALTER TABLE palettes    ADD COLUMN controleur_id INT NULL AFTER etat;
ALTER TABLE reparations ADD COLUMN employe_id    INT NULL AFTER palette_id;

ALTER TABLE chariots
    ADD CONSTRAINT fk_chariot_operateur FOREIGN KEY (operateur_id) REFERENCES employes(id) ON DELETE SET NULL;
ALTER TABLE palettes
    ADD CONSTRAINT fk_palette_controleur FOREIGN KEY (controleur_id) REFERENCES employes(id) ON DELETE SET NULL;
ALTER TABLE reparations
    ADD CONSTRAINT fk_rep_employe FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE SET NULL;

-- Employés de démonstration (à adapter / supprimer selon vos besoins).
INSERT INTO employes (matricule, nom, prenom, type, poste) VALUES
    ('EMP-001', 'Bennani',  'Karim',   'permanent',   'Cariste'),
    ('EMP-002', 'El Amrani','Sara',    'permanent',   'Contrôleur qualité'),
    ('EMP-003', 'Toure',    'Ali',     'permanent',   'Technicien réparation'),
    ('EMP-004', 'Diallo',   'Moussa',  'journalier',  'Cariste'),
    ('EMP-005', 'Haddad',   'Yassine', 'journalier',  'Manutentionnaire'),
    ('EMP-006', 'Nakache',  'Leila',   'journalier',  'Contrôleur qualité');
