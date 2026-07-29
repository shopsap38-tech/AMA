-- =============================================================================
--  QMS — Données initiales (rôles, permissions, comptes de démonstration,
--  jeu d'exemples pour le tableau de bord).
--  Mot de passe de tous les comptes de démonstration : Qms@2026
-- =============================================================================

SET NAMES utf8mb4;
USE `qms`;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `role_permissions`;
TRUNCATE TABLE `permissions`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `audit_logs`;
TRUNCATE TABLE `validation_steps`;
TRUNCATE TABLE `attachments`;
TRUNCATE TABLE `corrective_action_comments`;
TRUNCATE TABLE `corrective_actions`;
TRUNCATE TABLE `non_conformities`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `departments`;
TRUNCATE TABLE `roles`;

-- -----------------------------------------------------------------------------
--  Rôles
-- -----------------------------------------------------------------------------
INSERT INTO `roles` (`id`, `slug`, `name`, `description`) VALUES
    (1, 'admin',                  'Administrateur',           'Accès complet à la configuration et à tous les modules.'),
    (2, 'direction',              'Direction',                'Approbation finale et pilotage global.'),
    (3, 'responsable_qualite',    'Responsable Qualité',      'Pilotage du système qualité et des non-conformités.'),
    (4, 'responsable_production', 'Responsable Production',   'Suivi des non-conformités de production.'),
    (5, 'chef_equipe',            "Chef d'équipe",            'Encadrement opérationnel et revue de premier niveau.'),
    (6, 'employe',                'Employé',                  'Déclaration des non-conformités.');

-- -----------------------------------------------------------------------------
--  Permissions
-- -----------------------------------------------------------------------------
INSERT INTO `permissions` (`id`, `slug`, `name`, `module`) VALUES
    (1,  '*',                     'Tous les droits',              'system'),
    (2,  'nonconformity.view',    'Consulter les NC',             'nonconformity'),
    (3,  'nonconformity.create',  'Créer une NC',                 'nonconformity'),
    (4,  'nonconformity.update',  'Modifier une NC',              'nonconformity'),
    (5,  'nonconformity.delete',  'Supprimer une NC',             'nonconformity'),
    (6,  'action.create',         'Créer une action corrective',  'action'),
    (7,  'action.update',         'Modifier une action',          'action'),
    (8,  'action.delete',         'Supprimer une action',         'action'),
    (9,  'validation.act',        'Valider / signer',             'validation'),
    (10, 'report.view',           'Consulter les rapports',       'report'),
    (11, 'audit.view',            "Consulter le journal d'audit", 'audit'),
    (12, 'user.manage',           'Gérer les utilisateurs',       'admin');

-- -----------------------------------------------------------------------------
--  Attribution des permissions aux rôles
-- -----------------------------------------------------------------------------
-- Administrateur : tous les droits
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES (1, 1);

-- Direction
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (2, 2), (2, 9), (2, 10), (2, 11);

-- Responsable Qualité
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (3, 2), (3, 3), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 9), (3, 10), (3, 11);

-- Responsable Production
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (4, 2), (4, 4), (4, 6), (4, 7), (4, 9), (4, 10);

-- Chef d'équipe
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (5, 2), (5, 3), (5, 4), (5, 6), (5, 7), (5, 9);

-- Employé
INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
    (6, 2), (6, 3), (6, 6);

-- -----------------------------------------------------------------------------
--  Services / Départements
-- -----------------------------------------------------------------------------
INSERT INTO `departments` (`id`, `name`, `code`) VALUES
    (1, 'Production',  'PROD'),
    (2, 'Qualité',     'QUAL'),
    (3, 'Logistique',  'LOG'),
    (4, 'Maintenance', 'MAINT'),
    (5, 'Achats',      'ACH');

-- -----------------------------------------------------------------------------
--  Utilisateurs  (mot de passe : Qms@2026)
-- -----------------------------------------------------------------------------
SET @hash = '$2y$12$.O5xr8Q8BS2V2jBsDan5gexz.RW5erdkeiaGoby6QPnptTLPS/j3u';

INSERT INTO `users` (`id`, `role_id`, `department_id`, `first_name`, `last_name`, `email`, `password_hash`, `job_title`, `phone`, `is_active`) VALUES
    (1, 1, 2, 'Sarah',   'Admin',      'admin@qms.local',      @hash, 'Administrateur système',   '+33 1 00 00 00 01', 1),
    (2, 2, 2, 'Antoine', 'Directeur',  'direction@qms.local',  @hash, 'Directeur Général',        '+33 1 00 00 00 02', 1),
    (3, 3, 2, 'Nadia',   'Benali',     'qualite@qms.local',    @hash, 'Responsable Qualité',      '+33 1 00 00 00 03', 1),
    (4, 4, 1, 'Marc',    'Lefèvre',    'production@qms.local', @hash, 'Responsable Production',   '+33 1 00 00 00 04', 1),
    (5, 5, 1, 'Julie',   'Moreau',     'chef@qms.local',       @hash, "Chef d'équipe",            '+33 1 00 00 00 05', 1),
    (6, 6, 1, 'Karim',   'Haddad',     'employe@qms.local',    @hash, 'Opérateur de production',  '+33 1 00 00 00 06', 1);

UPDATE `departments` SET `manager_id` = 4 WHERE `id` = 1;
UPDATE `departments` SET `manager_id` = 3 WHERE `id` = 2;

-- -----------------------------------------------------------------------------
--  Non-conformités de démonstration
-- -----------------------------------------------------------------------------
INSERT INTO `non_conformities`
    (`id`, `reference`, `occurred_on`, `occurred_at`, `department_id`, `workshop`, `location`, `product`, `product_reference`, `batch`, `quantity`, `severity`, `observation`, `origin`, `description`, `root_cause_analysis`, `impact`, `responsible_id`, `status`, `created_by`, `closed_at`, `created_at`)
VALUES
    (1, 'NC-2026-000001', DATE_SUB(CURDATE(), INTERVAL 190 DAY), '08:30:00', 1, 'Atelier A', 'Ligne 1', 'Roulement à billes 6204', 'RB-6204', 'LOT-2401', 120, 'majeure',  'Jeu radial hors tolérance', 'production', 'Contrôle dimensionnel : jeu radial supérieur à la spécification sur 120 pièces.', 'Usure de l’outil de rectification non détectée à temps.', 'Rebut partiel du lot, retard de livraison client.', 4, 'cloturee', 6, DATE_SUB(CURDATE(), INTERVAL 175 DAY), DATE_SUB(CURDATE(), INTERVAL 190 DAY)),
    (2, 'NC-2026-000002', DATE_SUB(CURDATE(), INTERVAL 150 DAY), '14:10:00', 3, 'Quai réception', 'Zone R2', 'Tôle acier S235', 'TAC-S235', 'LOT-2380', 40, 'critique', 'Corrosion à la réception', 'reception', 'Présence de corrosion sur les tôles livrées par le fournisseur.', 'Emballage fournisseur non étanche.', 'Blocage matière, arrêt de la ligne d’emboutissage.', 3, 'cloturee', 5, DATE_SUB(CURDATE(), INTERVAL 120 DAY), DATE_SUB(CURDATE(), INTERVAL 150 DAY)),
    (3, 'NC-2026-000003', DATE_SUB(CURDATE(), INTERVAL 95 DAY), '10:45:00', 1, 'Atelier B', 'Poste 3', 'Carter aluminium', 'CAL-118', 'LOT-2410', 15, 'mineure', 'Défaut d’aspect peinture', 'production', 'Coulures de peinture constatées sur le carter en sortie de cabine.', 'Réglage buse de pulvérisation.', 'Retouche nécessaire.', 5, 'action_corrective', 6, NULL, DATE_SUB(CURDATE(), INTERVAL 95 DAY)),
    (4, 'NC-2026-000004', DATE_SUB(CURDATE(), INTERVAL 60 DAY), '16:20:00', 3, 'Expédition', 'Quai E1', 'Kit de montage', 'KIT-770', 'LOT-2422', 8, 'majeure', 'Erreur de colisage', 'expedition', 'Références manquantes dans le colis expédié au client final.', 'Erreur de préparation de commande.', 'Réclamation client, réexpédition express.', 3, 'validation', 5, NULL, DATE_SUB(CURDATE(), INTERVAL 60 DAY)),
    (5, 'NC-2026-000005', DATE_SUB(CURDATE(), INTERVAL 30 DAY), '09:05:00', 4, 'Maintenance', 'Ligne 2', 'Convoyeur C2', 'CV-002', NULL, NULL, 'critique', 'Arrêt machine récurrent', 'production', 'Arrêts intempestifs du convoyeur provoquant des non-conformités en cascade.', 'Capteur de position défaillant.', 'Perte de cadence, risque sécurité.', 4, 'en_analyse', 6, NULL, DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
    (6, 'NC-2026-000006', DATE_SUB(CURDATE(), INTERVAL 10 DAY), '11:30:00', 5, 'Achats', 'Bureau', 'Composant électronique X', 'CEX-990', 'LOT-2440', 500, 'majeure', 'Non-conformité fournisseur', 'fournisseur', 'Lot de composants non conforme au cahier des charges technique.', 'Changement de sous-traitant non validé par la qualité.', 'Blocage production, audit fournisseur requis.', 3, 'ouverte', 5, NULL, DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
    (7, 'NC-2026-000007', DATE_SUB(CURDATE(), INTERVAL 5 DAY), '13:15:00', 1, 'Atelier A', 'Ligne 1', 'Roulement à billes 6204', 'RB-6204', 'LOT-2445', 60, 'mineure', 'Marquage illisible', 'production', 'Marquage laser partiellement illisible sur une série de pièces.', 'Encrassement de la tête laser.', 'Tri à 100 % nécessaire.', 5, 'ouverte', 6, NULL, DATE_SUB(CURDATE(), INTERVAL 5 DAY));

-- -----------------------------------------------------------------------------
--  Circuit de validation (5 étapes par NC)
-- -----------------------------------------------------------------------------
INSERT INTO `validation_steps` (`non_conformity_id`, `step_order`, `role_required`, `step_label`, `status`, `approver_id`, `signature`, `acted_at`)
SELECT nc.id, s.step_order, s.role_required, s.step_label, 'en_attente', NULL, NULL, NULL
FROM `non_conformities` nc
CROSS JOIN (
    SELECT 1 AS step_order, 'employe' AS role_required, 'Déclaration (Employé)' AS step_label UNION ALL
    SELECT 2, 'chef_equipe',            "Revue Chef d'équipe" UNION ALL
    SELECT 3, 'responsable_qualite',    'Validation Responsable Qualité' UNION ALL
    SELECT 4, 'responsable_production', 'Validation Responsable Production' UNION ALL
    SELECT 5, 'direction',              'Approbation Direction'
) s;

-- NC clôturées : toutes les étapes approuvées
UPDATE `validation_steps` SET `status` = 'approuve', `approver_id` = 3,
    `signature` = CONCAT('Signé électroniquement | ', DATE_FORMAT(NOW(), '%Y-%m-%d'), ' | ', SUBSTRING(MD5(RAND()), 1, 16)),
    `acted_at` = NOW()
WHERE `non_conformity_id` IN (1, 2);

-- NC #4 : 3 premières étapes approuvées, en attente production/direction
UPDATE `validation_steps` SET `status` = 'approuve', `approver_id` = 5,
    `signature` = CONCAT('Signé électroniquement | ', DATE_FORMAT(NOW(), '%Y-%m-%d'), ' | ', SUBSTRING(MD5(RAND()), 1, 16)),
    `acted_at` = NOW()
WHERE `non_conformity_id` = 4 AND `step_order` <= 3;

-- -----------------------------------------------------------------------------
--  Actions correctives
-- -----------------------------------------------------------------------------
INSERT INTO `corrective_actions` (`non_conformity_id`, `title`, `description`, `assignee_id`, `due_date`, `priority`, `status`, `completed_at`, `created_by`, `created_at`) VALUES
    (1, 'Remplacer l’outil de rectification', 'Changer l’outil et mettre en place un suivi d’usure.', 4, DATE_SUB(CURDATE(), INTERVAL 180 DAY), 'haute',   'terminee', DATE_SUB(CURDATE(), INTERVAL 178 DAY), 3, DATE_SUB(CURDATE(), INTERVAL 188 DAY)),
    (2, 'Audit fournisseur emballage',        'Réaliser un audit et exiger un plan d’action.',        3, DATE_SUB(CURDATE(), INTERVAL 130 DAY), 'urgente', 'terminee', DATE_SUB(CURDATE(), INTERVAL 128 DAY), 3, DATE_SUB(CURDATE(), INTERVAL 148 DAY)),
    (3, 'Régler la buse de pulvérisation',    'Contrôler et régler les paramètres de la cabine.',     5, DATE_ADD(CURDATE(), INTERVAL 5 DAY),   'normale', 'en_cours', NULL, 3, DATE_SUB(CURDATE(), INTERVAL 90 DAY)),
    (4, 'Mettre en place un double contrôle colisage', 'Ajouter une étape de vérification avant expédition.', 4, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'haute', 'en_cours', NULL, 3, DATE_SUB(CURDATE(), INTERVAL 55 DAY)),
    (5, 'Remplacer le capteur de position',   'Commander et installer un capteur neuf.',              4, DATE_SUB(CURDATE(), INTERVAL 2 DAY),   'urgente', 'a_faire',  NULL, 3, DATE_SUB(CURDATE(), INTERVAL 25 DAY)),
    (6, 'Bloquer et trier le lot fournisseur','Isoler le lot et déclencher le retour fournisseur.',   3, DATE_ADD(CURDATE(), INTERVAL 3 DAY),   'urgente', 'a_faire',  NULL, 3, DATE_SUB(CURDATE(), INTERVAL 9 DAY));

-- -----------------------------------------------------------------------------
--  Notifications de démonstration
-- -----------------------------------------------------------------------------
INSERT INTO `notifications` (`user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
    (3, 'nc_assigned',   'Nouvelle non-conformité assignée', 'La non-conformité NC-2026-000006 vous a été assignée.', 'nonconformities/6', 0, DATE_SUB(NOW(), INTERVAL 10 DAY)),
    (4, 'action_assigned','Action corrective assignée',       'Action « Remplacer le capteur de position » à traiter en urgence.', 'nonconformities/5', 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
    (3, 'validation_update','Étape de validation en attente', 'La NC-2026-000004 attend votre validation qualité.', 'nonconformities/4', 1, DATE_SUB(NOW(), INTERVAL 4 DAY));

SET FOREIGN_KEY_CHECKS = 1;
