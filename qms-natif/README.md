# Quality Management System (QMS) — Édition PHP natif

Module Qualité de type ERP (gestion des **non-conformités**, **actions
correctives**, **circuits de validation** et **traçabilité**) écrit en **PHP
natif procédural** — sans framework ni Composer — dans le même esprit qu'une
application classique déployée sur Laragon / WAMP / XAMPP.

Interface premium inspirée de **SAP Fiori**, **Microsoft Fluent** et **Material
Design**, mode clair / sombre, responsive (Desktop / Tablette / Mobile).

---

## Différence avec la version MVC

Cette édition contient **exactement les mêmes fonctionnalités, la même base de
données et le même design** que la version MVC, mais l'organisation du code est
**procédurale** : chaque écran est un fichier `.php` autonome qui inclut des
fichiers partagés (`includes/functions.php`, `includes/header.php`,
`includes/footer.php`) et utilise **PDO** directement. C'est le style le plus
simple à lire, héberger et maintenir.

---

## Structure

```
qms-natif/
├── config/
│   └── database.php          # Connexion PDO (root / vide par défaut)
├── includes/
│   ├── functions.php         # Session, sécurité, auth, RBAC, audit, helpers
│   ├── header.php            # Sidebar + topbar
│   ├── footer.php            # Scripts
│   ├── 403.php               # Page accès refusé
│   ├── dashboard_data.php    # Calcul des KPI et graphiques
│   └── report_query.php      # Requête filtrée des rapports
├── assets/css/app.css        # Design system
├── assets/js/app.js          # Charts, thème, notifications, dropzone, DataTables
├── uploads/                  # Pièces jointes (protégé)
├── database/
│   ├── schema.sql            # 12 tables (clés étrangères, index, contraintes)
│   └── seed.sql              # Rôles, permissions, comptes et données de démo
│
├── login.php  logout.php
├── index.php                 # Tableau de bord (KPI + graphiques)
├── api_dashboard.php  api_notifications.php   # Endpoints JSON
├── nonconformites.php        # Liste + recherche multicritère (DataTables)
├── nc_form.php  nc_save.php  nc_show.php  nc_delete.php
├── action_save.php           # Actions correctives (CAPA)
├── validation_save.php       # Workflow + signature électronique
├── attachment_upload.php  attachment_download.php  attachment_delete.php
├── notifications.php  notification_read.php
├── rapports.php  export_csv.php  export_excel.php  rapport_print.php
├── utilisateurs.php  utilisateur_form.php  utilisateur_save.php
└── audit.php                 # Journal d'audit
```

---

## Installation (Laragon / WAMP / XAMPP)

1. Copiez le dossier `qms-natif/` dans votre racine web (ex. `C:\laragon\www\qms-natif`).
2. Importez les scripts SQL via phpMyAdmin / HeidiSQL :
   ```sql
   SOURCE C:/laragon/www/qms-natif/database/schema.sql;
   SOURCE C:/laragon/www/qms-natif/database/seed.sql;
   ```
3. Vérifiez `config/database.php` (par défaut : `127.0.0.1`, base `qms`,
   utilisateur `root`, mot de passe vide — configuration Laragon standard).
   Les valeurs sont surchargeables par variables d'environnement
   (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, …).
4. Ouvrez `http://localhost/qms-natif/login.php`.

> Aucune commande à exécuter, aucune dépendance à installer.

---

## Comptes de démonstration

> Mot de passe commun : **`Qms@2026`**

| Rôle | Email |
|------|-------|
| Administrateur | `admin@qms.local` |
| Direction | `direction@qms.local` |
| Responsable Qualité | `qualite@qms.local` |
| Responsable Production | `production@qms.local` |
| Chef d'équipe | `chef@qms.local` |
| Employé | `employe@qms.local` |

---

## Sécurité

- Mots de passe hachés (`password_hash` / `password_verify`).
- Jeton **CSRF** vérifié sur toutes les actions POST.
- Échappement systématique des sorties via `e()` (anti-**XSS**).
- Requêtes **préparées** PDO (anti-injection SQL).
- **RBAC** granulaire (`require_permission()` sur chaque page).
- **Journal d'audit** complet (utilisateur, action, avant/après, IP, navigateur).
- Dossier `uploads/` protégé contre l'exécution de scripts + contrôle du type MIME réel.

---

## Modules fonctionnels

Tableau de bord (6 KPI + graphiques barres/ligne/camembert/donut/heatmap),
gestion des non-conformités (numérotation auto `NC-2026-000001`, recherche
multicritère), actions correctives avec calcul des retards, workflow de
validation à 5 niveaux avec signature électronique, pièces jointes versionnées
(drag & drop), notifications temps réel, rapports CSV/Excel/PDF, administration
des utilisateurs et journal d'audit.
