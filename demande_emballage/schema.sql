-- ============================================================
--  Application : Demande Emballage
--  Base de données MySQL (PHP 8.x natif)
-- ============================================================

CREATE DATABASE IF NOT EXISTS demande_emballage
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE demande_emballage;

-- ------------------------------------------------------------
--  Utilisateurs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(150)  NOT NULL,
    email         VARCHAR(190)  NOT NULL UNIQUE,
    mot_de_passe  VARCHAR(255)  NOT NULL,
    role          ENUM('administrateur','demandeur','preparateur','facturation') NOT NULL,
    actif         TINYINT(1)    NOT NULL DEFAULT 1,
    cree_le       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Articles (catalogue alimenté automatiquement)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS articles (
    id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom      VARCHAR(190) NOT NULL UNIQUE,
    cree_le  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Demandes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS demandes (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    article_id     INT UNSIGNED NULL,
    nom_article    VARCHAR(190) NOT NULL,
    quantite       INT UNSIGNED NOT NULL,
    commentaire    TEXT NULL,
    statut         ENUM('en_attente','en_route','fait') NOT NULL DEFAULT 'en_attente',

    demandeur_id   INT UNSIGNED NOT NULL,
    preparateur_id INT UNSIGNED NULL,
    facturation_id INT UNSIGNED NULL,

    cree_le        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   -- création (statut En attente)
    prepare_le     DATETIME NULL,                                 -- passage En route
    cloture_le     DATETIME NULL,                                 -- passage C'est fait
    delai_minutes  INT UNSIGNED NULL,                             -- SLA : cree_le -> cloture_le

    CONSTRAINT fk_demande_article     FOREIGN KEY (article_id)     REFERENCES articles(id)     ON DELETE SET NULL,
    CONSTRAINT fk_demande_demandeur   FOREIGN KEY (demandeur_id)   REFERENCES utilisateurs(id) ON DELETE RESTRICT,
    CONSTRAINT fk_demande_preparateur FOREIGN KEY (preparateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    CONSTRAINT fk_demande_facturation FOREIGN KEY (facturation_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,

    INDEX idx_statut (statut),
    INDEX idx_cree_le (cree_le)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Photos (jointes par le préparateur)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS photos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    demande_id   INT UNSIGNED NOT NULL,
    chemin       VARCHAR(255) NOT NULL,
    nom_origine  VARCHAR(255) NULL,
    uploaded_by  INT UNSIGNED NULL,
    uploaded_le  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_photo_demande FOREIGN KEY (demande_id)  REFERENCES demandes(id)     ON DELETE CASCADE,
    CONSTRAINT fk_photo_user    FOREIGN KEY (uploaded_by) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
--  Historique des statuts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historique_statuts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    demande_id      INT UNSIGNED NOT NULL,
    ancien_statut   ENUM('en_attente','en_route','fait') NULL,
    nouveau_statut  ENUM('en_attente','en_route','fait') NOT NULL,
    user_id         INT UNSIGNED NULL,
    change_le       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_hist_demande FOREIGN KEY (demande_id) REFERENCES demandes(id)     ON DELETE CASCADE,
    CONSTRAINT fk_hist_user    FOREIGN KEY (user_id)    REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_hist_demande (demande_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Comptes de démonstration
--  Mot de passe pour TOUS les comptes ci-dessous : "password"
--  (hash bcrypt de "password")
-- ============================================================
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES
    ('Administrateur',   'admin@demo.local',        '$2y$12$Mw9OnmBGrhqJNl7lJZo5ee3RsFnIzxahtptP8tRVzEM8p2aUjjGya', 'administrateur'),
    ('Demandeur Demo',   'demandeur@demo.local',    '$2y$12$Mw9OnmBGrhqJNl7lJZo5ee3RsFnIzxahtptP8tRVzEM8p2aUjjGya', 'demandeur'),
    ('Preparateur Demo', 'preparateur@demo.local',  '$2y$12$Mw9OnmBGrhqJNl7lJZo5ee3RsFnIzxahtptP8tRVzEM8p2aUjjGya', 'preparateur'),
    ('Facturation Demo', 'facturation@demo.local',  '$2y$12$Mw9OnmBGrhqJNl7lJZo5ee3RsFnIzxahtptP8tRVzEM8p2aUjjGya', 'facturation');
