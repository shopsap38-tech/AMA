# Quality Management System (QMS)

Module Qualité de type ERP (inspiré de SAP S/4HANA, Microsoft Dynamics 365 et
Oracle ERP) pour la gestion des **non-conformités**, des **actions correctives**,
des **circuits de validation** et de la **traçabilité** dans un contexte
industriel.

Interface premium et internationale inspirée de **SAP Fiori**, **Microsoft
Fluent** et **Material Design** — mode clair / sombre, responsive
(Desktop / Tablette / Mobile).

---

## Sommaire des fonctionnalités

| Module | Détails |
|--------|---------|
| **Tableau de bord** | KPI (total, ouvertes, critiques, actions en retard, taux de résolution, temps moyen) et graphiques Chart.js : ligne (évolution mensuelle), barres (origine, performance par service), camembert (gravité), donut (statut), carte de chaleur (heatmap gravité × service). |
| **Non-conformités** | Fiche complète, numérotation automatique `NC-2026-000001`, recherche multicritère, DataTables avancées. |
| **Actions correctives (CAPA)** | Multi-actions, responsable, échéance, priorité, statut, commentaires, calcul automatique des retards, notifications. |
| **Validation** | Workflow à 5 niveaux (Employé → Chef d'équipe → Responsable Qualité → Responsable Production → Direction) avec **signature électronique** horodatée. |
| **Photos & documents** | Upload multiple, **drag & drop**, PDF / Excel / Vidéo / images, versionnage, téléchargement sécurisé. |
| **Historique** | Traçabilité complète : qui, quand, quoi, valeurs avant/après, adresse IP, navigateur. |
| **Notifications** | Centre applicatif + polling temps réel + point d'extension email. |
| **Rapports** | Export **CSV**, **Excel** (SpreadsheetML), **impression / PDF**. |
| **Sécurité** | Authentification, **RBAC**, protection **CSRF** & **XSS**, journal d'audit, gestion des permissions. |

---

## Architecture

Application **MVC** avec pattern **Repository**, couche **Services**,
**Middleware** et **conteneur d'injection de dépendances**.

```
qms/
├── public/                  # Racine web (Front Controller)
│   ├── index.php            # Point d'entrée unique
│   ├── .htaccess            # Réécriture d'URL + en-têtes de sécurité
│   └── assets/              # CSS / JS / uploads
├── app/
│   ├── Core/                # App, Router, Container, Database, Auth, View, Csrf, Validator…
│   ├── Middleware/          # Auth, Guest, Csrf, RBAC
│   ├── Repositories/        # Accès aux données (Repository Pattern)
│   ├── Services/            # Logique métier (NC, CAPA, validation, dashboard, audit, upload, rapports)
│   ├── Controllers/         # Contrôleurs HTTP + endpoints REST
│   ├── Views/               # Vues PHP (layouts, partials, modules)
│   └── Support/             # Helpers globaux
├── config/                  # config.php, routes.php
├── database/                # schema.sql, seed.sql
└── bootstrap/               # autoload.php (PSR-4 autonome)
```

### Stack technique
- **PHP 8.3+** (typage strict, énumérations métier, Argon2id/bcrypt)
- **MySQL 8 / MariaDB 10.4+** (InnoDB, utf8mb4, clés étrangères, index)
- **Bootstrap 5.3**, **JavaScript ES6**, **Chart.js 4**, **Font Awesome 6**, **DataTables**
- **API REST** (dashboard, recherche, notifications)
- Conforme aux bonnes pratiques **PSR-4** (autoloading) et **PSR-12** (style).

---

## Installation (Laragon / WAMP / XAMPP)

1. Placez le dossier `qms/` dans votre racine web (ex. `C:\laragon\www\qms`).
2. Créez la base et importez les scripts SQL :
   ```sql
   SOURCE C:/laragon/www/qms/database/schema.sql;
   SOURCE C:/laragon/www/qms/database/seed.sql;
   ```
   > `seed.sql` charge les rôles, permissions, comptes de démonstration et un
   > jeu d'exemples pour le tableau de bord.
3. Configurez la connexion — par défaut (Laragon) : hôte `127.0.0.1`, base `qms`,
   utilisateur `root`, mot de passe vide. Ces valeurs sont surchargées par des
   variables d'environnement si présentes :
   `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_URL`, `APP_DEBUG`.
4. Faites pointer le **document root** de votre hôte virtuel sur le dossier
   `public/`. À défaut, le `.htaccess` racine redirige automatiquement vers
   `public/`.
5. Ouvrez `http://qms.test` (ou `http://localhost/qms`).

### Serveur de développement PHP intégré
```bash
DB_HOST=127.0.0.1 DB_NAME=qms DB_USER=root php -S localhost:8000 -t public
```

---

## Comptes de démonstration

> Mot de passe commun : **`Qms@2026`**

| Rôle | Email | Accès |
|------|-------|-------|
| Administrateur | `admin@qms.local` | Tous les droits |
| Direction | `direction@qms.local` | Validation finale, rapports, audit |
| Responsable Qualité | `qualite@qms.local` | Gestion complète des NC et CAPA |
| Responsable Production | `production@qms.local` | Suivi & validation production |
| Chef d'équipe | `chef@qms.local` | Déclaration, revue, validation |
| Employé | `employe@qms.local` | Déclaration des NC |

---

## Sécurité

- Mots de passe hachés (`password_hash`, ré-hachage transparent à la connexion).
- Jetons **CSRF** synchronisés sur toutes les requêtes mutantes.
- Échappement systématique des sorties (`e()`) contre les **XSS**.
- Requêtes **préparées** PDO (anti-injection SQL).
- **RBAC** granulaire par permission (`nonconformity.create`, `validation.act`, …).
- **Journal d'audit** immuable (utilisateur, action, avant/après, IP, user-agent).
- En-têtes de sécurité HTTP (`X-Frame-Options`, `X-Content-Type-Options`, …).

---

## Modèle de données

12 tables reliées par des clés étrangères avec contraintes et index :
`roles`, `permissions`, `role_permissions`, `departments`, `users`,
`non_conformities`, `corrective_actions`, `corrective_action_comments`,
`attachments`, `validation_steps`, `audit_logs`, `notifications`.

Voir [`database/schema.sql`](database/schema.sql) pour le détail complet.
