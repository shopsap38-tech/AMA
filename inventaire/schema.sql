-- Base de données pour l'application Inventaire
CREATE DATABASE IF NOT EXISTS inventaire CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventaire;

CREATE TABLE IF NOT EXISTS produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT NULL,
    quantite INT NOT NULL DEFAULT 0,
    seuil_alerte INT NOT NULL DEFAULT 5,
    prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mouvements_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    type ENUM('entree', 'sortie') NOT NULL,
    quantite INT NOT NULL,
    motif VARCHAR(255) NULL,
    date_mouvement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mouvement_produit FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
) ENGINE=InnoDB;
