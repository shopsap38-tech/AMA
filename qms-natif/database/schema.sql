-- =============================================================================
--  Quality Management System (QMS) — Schéma de base de données
--  Moteur : MySQL 8.0+ / InnoDB — Encodage : utf8mb4
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `qms`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE `qms`;

-- -----------------------------------------------------------------------------
--  Rôles (RBAC)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`        VARCHAR(50)  NOT NULL,
    `name`        VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Permissions (RBAC)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
    `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`   VARCHAR(80)  NOT NULL,
    `name`   VARCHAR(150) NOT NULL,
    `module` VARCHAR(60)  NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_permissions_slug` (`slug`),
    KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Liaison rôles <-> permissions
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
    `role_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    KEY `idx_rp_permission` (`permission_id`),
    CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles` (`id`)       ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Services / Départements
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(120) NOT NULL,
    `code`       VARCHAR(20)  DEFAULT NULL,
    `manager_id` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_departments_name` (`name`),
    KEY `idx_departments_manager` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Utilisateurs
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id`       INT UNSIGNED NOT NULL,
    `department_id` INT UNSIGNED DEFAULT NULL,
    `first_name`    VARCHAR(100) NOT NULL,
    `last_name`     VARCHAR(100) NOT NULL,
    `email`         VARCHAR(180) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `job_title`     VARCHAR(120) DEFAULT NULL,
    `phone`         VARCHAR(40)  DEFAULT NULL,
    `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `last_login_at` DATETIME     DEFAULT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role_id`),
    KEY `idx_users_department` (`department_id`),
    CONSTRAINT `fk_users_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles` (`id`),
    CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `departments`
    ADD CONSTRAINT `fk_departments_manager` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- -----------------------------------------------------------------------------
--  Non-conformités
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `non_conformities`;
CREATE TABLE `non_conformities` (
    `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference`           VARCHAR(20)  NOT NULL,
    `occurred_on`         DATE         NOT NULL,
    `occurred_at`         TIME         DEFAULT NULL,
    `department_id`       INT UNSIGNED DEFAULT NULL,
    `workshop`            VARCHAR(120) DEFAULT NULL,
    `location`            VARCHAR(120) DEFAULT NULL,
    `product`             VARCHAR(180) NOT NULL,
    `product_reference`   VARCHAR(120) DEFAULT NULL,
    `batch`               VARCHAR(120) DEFAULT NULL,
    `quantity`            DECIMAL(12,2) DEFAULT NULL,
    `severity`            ENUM('critique','majeure','mineure') NOT NULL,
    `observation`         TEXT DEFAULT NULL,
    `origin`              ENUM('production','stock','reception','expedition','client','fournisseur') NOT NULL,
    `description`         TEXT NOT NULL,
    `root_cause_analysis` TEXT DEFAULT NULL,
    `impact`              TEXT DEFAULT NULL,
    `responsible_id`      INT UNSIGNED DEFAULT NULL,
    `status`             ENUM('ouverte','en_analyse','action_corrective','validation','cloturee') NOT NULL DEFAULT 'ouverte',
    `created_by`          INT UNSIGNED DEFAULT NULL,
    `closed_at`           DATETIME DEFAULT NULL,
    `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_nc_reference` (`reference`),
    KEY `idx_nc_status` (`status`),
    KEY `idx_nc_severity` (`severity`),
    KEY `idx_nc_origin` (`origin`),
    KEY `idx_nc_department` (`department_id`),
    KEY `idx_nc_responsible` (`responsible_id`),
    KEY `idx_nc_occurred_on` (`occurred_on`),
    KEY `idx_nc_created_by` (`created_by`),
    CONSTRAINT `fk_nc_department`  FOREIGN KEY (`department_id`)  REFERENCES `departments` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_nc_responsible` FOREIGN KEY (`responsible_id`) REFERENCES `users` (`id`)       ON DELETE SET NULL,
    CONSTRAINT `fk_nc_created_by`  FOREIGN KEY (`created_by`)     REFERENCES `users` (`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Actions correctives
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `corrective_actions`;
CREATE TABLE `corrective_actions` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `non_conformity_id` INT UNSIGNED NOT NULL,
    `title`             VARCHAR(200) NOT NULL,
    `description`       TEXT DEFAULT NULL,
    `assignee_id`       INT UNSIGNED DEFAULT NULL,
    `due_date`          DATE NOT NULL,
    `priority`          ENUM('basse','normale','haute','urgente') NOT NULL DEFAULT 'normale',
    `status`            ENUM('a_faire','en_cours','terminee','annulee') NOT NULL DEFAULT 'a_faire',
    `completed_at`      DATETIME DEFAULT NULL,
    `created_by`        INT UNSIGNED DEFAULT NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ca_nc` (`non_conformity_id`),
    KEY `idx_ca_assignee` (`assignee_id`),
    KEY `idx_ca_status` (`status`),
    KEY `idx_ca_due_date` (`due_date`),
    CONSTRAINT `fk_ca_nc`       FOREIGN KEY (`non_conformity_id`) REFERENCES `non_conformities` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ca_assignee` FOREIGN KEY (`assignee_id`)       REFERENCES `users` (`id`)            ON DELETE SET NULL,
    CONSTRAINT `fk_ca_creator`  FOREIGN KEY (`created_by`)        REFERENCES `users` (`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Commentaires des actions correctives
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `corrective_action_comments`;
CREATE TABLE `corrective_action_comments` (
    `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `corrective_action_id` INT UNSIGNED NOT NULL,
    `user_id`              INT UNSIGNED DEFAULT NULL,
    `body`                 TEXT NOT NULL,
    `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_cac_action` (`corrective_action_id`),
    KEY `idx_cac_user` (`user_id`),
    CONSTRAINT `fk_cac_action` FOREIGN KEY (`corrective_action_id`) REFERENCES `corrective_actions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cac_user`   FOREIGN KEY (`user_id`)              REFERENCES `users` (`id`)              ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Pièces jointes (photos, PDF, Excel, vidéos) avec versionnage
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `attachments`;
CREATE TABLE `attachments` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attachable_type` VARCHAR(40)  NOT NULL,
    `attachable_id`   INT UNSIGNED NOT NULL,
    `original_name`   VARCHAR(255) NOT NULL,
    `stored_name`     VARCHAR(255) NOT NULL,
    `mime_type`       VARCHAR(120) NOT NULL,
    `size`            INT UNSIGNED NOT NULL DEFAULT 0,
    `version`         INT UNSIGNED NOT NULL DEFAULT 1,
    `uploaded_by`     INT UNSIGNED DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_att_entity` (`attachable_type`, `attachable_id`),
    KEY `idx_att_user` (`uploaded_by`),
    CONSTRAINT `fk_att_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Étapes de validation (workflow + signature électronique)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `validation_steps`;
CREATE TABLE `validation_steps` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `non_conformity_id` INT UNSIGNED NOT NULL,
    `step_order`        TINYINT UNSIGNED NOT NULL,
    `role_required`     VARCHAR(50)  NOT NULL,
    `step_label`        VARCHAR(150) NOT NULL,
    `status`            ENUM('en_attente','approuve','rejete') NOT NULL DEFAULT 'en_attente',
    `approver_id`       INT UNSIGNED DEFAULT NULL,
    `signature`         VARCHAR(255) DEFAULT NULL,
    `comment`           TEXT DEFAULT NULL,
    `acted_at`          DATETIME DEFAULT NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_vs_step` (`non_conformity_id`, `step_order`),
    KEY `idx_vs_status` (`status`),
    KEY `idx_vs_approver` (`approver_id`),
    CONSTRAINT `fk_vs_nc`       FOREIGN KEY (`non_conformity_id`) REFERENCES `non_conformities` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_vs_approver` FOREIGN KEY (`approver_id`)       REFERENCES `users` (`id`)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Journal d'audit (traçabilité complète)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `action`      VARCHAR(60)  NOT NULL,
    `entity_type` VARCHAR(60)  NOT NULL,
    `entity_id`   INT UNSIGNED DEFAULT NULL,
    `old_values`  JSON DEFAULT NULL,
    `new_values`  JSON DEFAULT NULL,
    `ip_address`  VARCHAR(45)  DEFAULT NULL,
    `user_agent`  VARCHAR(255) DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_user` (`user_id`),
    KEY `idx_audit_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_action` (`action`),
    KEY `idx_audit_created` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
--  Notifications
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED NOT NULL,
    `type`       VARCHAR(60)  NOT NULL,
    `title`      VARCHAR(180) NOT NULL,
    `message`    VARCHAR(500) NOT NULL,
    `link`       VARCHAR(255) DEFAULT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user` (`user_id`, `is_read`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
