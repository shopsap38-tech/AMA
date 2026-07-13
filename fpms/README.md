# FPMS — Fleet & Pallet Management System

Application **PHP + MySQL** de gestion de flotte de chariots et de palettes.

## Fonctionnalités

### 🏠 Accueil
- KPI en temps réel : total chariots, chariots électriques / diesel, disponibles,
  en maintenance, en panne, palettes conformes / non conformes, taux de disponibilité.
- Graphiques interactifs (Chart.js) : état de la flotte, répartition par type,
  état des palettes, réparations par jour.
- Accès direct aux trois tableaux de bord spécialisés.

### 📊 Trois tableaux de bord spécialisés
Chaque dashboard combine **KPI en pourcentage (%)**, **graphiques** (barres, secteurs,
courbes), **tableaux de données structurés** et une **distinction claire entre employés
permanents et journaliers** :
- **Dashboard Palettes** : conformité par état, taux de conformité par type d'employé (contrôleur).
- **Dashboard Chariots** : disponibilité, temps d'arrêt, taux de disponibilité par type d'opérateur.
- **Dashboard Réparations** : taux de réussite, résultats et classement des réparateurs par statut.

### 👷 Gestion des employés
- Employés **permanents** et **journaliers** (matricule, nom, poste, statut, actif).
- Affectation d'un opérateur aux chariots, d'un contrôleur aux palettes et d'un réparateur aux réparations.

### 🚜 Gestion des chariots
- Fiche complète : code chariot, marque, modèle, type (Électrique / Diesel), état.
- Filtres par type et par état.
- Historique complet des changements d'état de chaque chariot.

### 📦 Gestion des palettes
- Palettes conformes, non conformes et cassées.
- Réparation des palettes (une réparation « réparée » remet la palette conforme).
- Nombre de réparations par jour (graphique).

### 📊 Rapports
- Rapport journalier, mensuel et annuel.
- Export **Excel** (CSV compatible Excel, avec BOM UTF-8).
- Export **PDF** (page imprimable → « Enregistrer en PDF » du navigateur).

### 🔔 Alertes
- Alerte de faible stock de palettes conformes (seuil configurable).
- Liste des chariots indisponibles et des palettes à traiter.

### 📈 Statistiques
- Disponibilité des chariots.
- Temps d'arrêt par chariot (calculé à partir de l'historique).

## Installation avec Laragon

1. Placez ce dossier dans `C:\laragon\www\fpms`.
2. Démarrez Laragon (Apache + MySQL).
3. Importez la base de données via phpMyAdmin ou HeidiSQL :
   ```sql
   SOURCE C:/laragon/www/fpms/schema.sql;
   ```
   ou copiez/collez le contenu de `schema.sql` dans un onglet SQL.
   Le script crée la base et un jeu de données de démonstration.
   > ⚠️ Réimporter `schema.sql` réinitialise entièrement les données.
   > Si vous avez déjà une base FPMS à conserver, exécutez plutôt
   > `migration_dashboards.sql` (ajoute les employés et les affectations sans effacer vos données).
4. Vérifiez les identifiants dans `config/database.php`
   (par défaut : utilisateur `root`, mot de passe vide — configuration MySQL par défaut de Laragon).
5. Ouvrez `http://fpms.test` (ou `http://localhost/fpms`) dans votre navigateur.

> Le seuil d'alerte de stock de palettes se règle via la constante
> `SEUIL_PALETTES` dans `config/database.php`.

## Structure

```
fpms/
├── assets/style.css          Feuille de style
├── config/database.php       Connexion PDO + constantes
├── includes/
│   ├── header.php            En-tête + navigation groupée
│   ├── footer.php            Pied de page
│   └── functions.php         Requêtes statistiques partagées (dont ventilations par type d'employé)
├── index.php                 Accueil (KPI + graphiques + accès dashboards)
├── dashboard_palettes.php    Tableau de bord des palettes
├── dashboard_chariots.php    Tableau de bord des chariots élévateurs
├── dashboard_reparations.php Tableau de bord des réparations
├── employes.php              Liste des employés (permanents / journaliers)
├── employe_form.php          Fiche employé (ajout / modification)
├── employe_save.php          Traitement de la fiche employé
├── employe_delete.php        Suppression d'un employé
├── chariots.php              Liste des chariots + filtres
├── chariot_form.php          Fiche chariot (ajout / modification)
├── chariot_save.php          Traitement de la fiche chariot
├── chariot_delete.php        Suppression d'un chariot
├── chariot_historique.php    Historique d'un chariot
├── palettes.php              Liste des palettes + filtres
├── palette_form.php          Fiche palette (ajout / modification)
├── palette_save.php          Traitement de la fiche palette
├── palette_delete.php        Suppression d'une palette
├── reparations.php           Réparations + réparations par jour
├── reparation_save.php       Traitement d'une réparation
├── rapports.php              Rapports journalier / mensuel / annuel
├── export_excel.php          Export CSV / Excel d'un rapport
├── export_pdf.php            Export PDF (page imprimable) d'un rapport
├── alertes.php               Alertes (stock faible, indisponibilités)
├── statistiques.php          Disponibilité + temps d'arrêt
└── schema.sql                Structure de la base + données de démo
```

## Remarques techniques

- Requêtes préparées (PDO) partout pour éviter les injections SQL.
- Sorties échappées avec `htmlspecialchars` pour éviter les failles XSS.
- Chart.js est chargé depuis un CDN (connexion Internet requise pour les graphiques).
