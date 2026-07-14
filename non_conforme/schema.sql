-- Base de données pour l'application « Suivi des non-conformités »
-- Convertie depuis le fichier Excel NON_CONFORME.xlsx
CREATE DATABASE IF NOT EXISTS non_conforme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE non_conforme;

-- Feuille « PR SEMI-FINI-MATIC » : produits finis / semi-finis non conformes
CREATE TABLE IF NOT EXISTS nc_semi_fini (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_article    VARCHAR(30)  NOT NULL,
    description_article VARCHAR(255) NOT NULL,
    quantite          INT          NOT NULL DEFAULT 0,
    nbr_palettes      INT          NOT NULL DEFAULT 0,
    poids_total_kg    DECIMAL(12,2) NOT NULL DEFAULT 0,
    motif             VARCHAR(150) NULL,
    decision_sq       VARCHAR(150) NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Feuille « DESINFECTANT » : désinfectants non conformes (expirés)
CREATE TABLE IF NOT EXISTS nc_desinfectant (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_article      VARCHAR(50)  NULL,
    numero_article    VARCHAR(30)  NOT NULL,
    description_article VARCHAR(255) NOT NULL,
    stock_mag         INT          NOT NULL DEFAULT 0,
    date_expiration   DATE         NULL,        -- NULL = « aucune date »
    code_um           VARCHAR(50)  NULL,        -- unité de manutention (ex : Carton 24)
    nbr_palettes      INT          NOT NULL DEFAULT 0,
    prix_unitaire     DECIMAL(12,4) NOT NULL DEFAULT 0,
    poids_par_carton_kg DECIMAL(12,4) NOT NULL DEFAULT 0,
    date_blocage      DATE         NULL,
    motif             VARCHAR(150) NULL,
    responsable       VARCHAR(150) NULL,
    decision_cq       VARCHAR(150) NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Feuille « BIG BAG » : big bags non conformes
CREATE TABLE IF NOT EXISTS nc_big_bag (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_nc           DATE         NOT NULL,
    total_big_bag     INT          NOT NULL DEFAULT 0,
    tonnage           DECIMAL(12,2) NOT NULL DEFAULT 0,   -- en kg
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
