# FPMS — Fleet & Pallet Management System

Application **PHP + MySQL** de gestion de flotte de chariots et de palettes.

## Fonctionnalités

### 🏠 Accueil
- KPI en temps réel : total chariots, chariots électriques / diesel, disponibles,
  en maintenance, en panne, palettes conformes / non conformes, taux de disponibilité.
- Graphiques interactifs et accès direct aux deux tableaux de bord spécialisés.

### 📊 Deux tableaux de bord spécialisés
Chaque dashboard combine **KPI en pourcentage (%)**, **tableaux de données structurés**
et des **graphiques présentés sous forme d'histogrammes (colonnes), avec le pourcentage
affiché au-dessus de chaque colonne** :
- **Dashboard Palettes** : quantités et conformité par état, évolution des palettes créées.
- **Dashboard Chariots** : répartition par état et par type, temps d'arrêt, mises en service.

Les dashboards **Palettes** et **Chariots**, ainsi que les listes **Palettes** et
**Chariots**, proposent un **histogramme d'évolution avec un sélecteur Jour / Mois / Année**.

> Tous les graphiques de l'application sont des histogrammes ; le rendu du pourcentage
> au-dessus des colonnes est fourni par le plugin `assets/charts.js`.

### 🚜 Gestion des chariots
- Fiche : code chariot, marque, type (Électrique / Diesel), état.
- Filtres par type et par état.
- Historique complet des changements d'état de chaque chariot.

### 📦 Gestion des palettes
- Palettes conformes, non conformes et cassées, avec **quantité par lot**.
- Les indicateurs additionnent les quantités (une ligne = un lot de N palettes).

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
4. Vérifiez les identifiants dans `config/database.php`
   (par défaut : utilisateur `root`, mot de passe vide — configuration MySQL par défaut de Laragon).
5. Ouvrez `http://fpms.test` (ou `http://localhost/fpms`) dans votre navigateur.

> Le seuil d'alerte de stock de palettes se règle via la constante
> `SEUIL_PALETTES` dans `config/database.php`.

## Structure

```
fpms/
├── assets/
│   ├── style.css            Feuille de style
│   └── charts.js            Plugin histogrammes + % au-dessus des colonnes
├── config/database.php       Connexion PDO + constantes
├── includes/
│   ├── header.php            En-tête + navigation groupée
│   ├── footer.php            Pied de page
│   └── functions.php         Requêtes statistiques partagées
├── index.php                 Accueil (KPI + graphiques + accès dashboards)
├── dashboard_palettes.php    Tableau de bord des palettes (+ évolution jour/mois/année)
├── dashboard_chariots.php    Tableau de bord des chariots (+ évolution jour/mois/année)
├── chariots.php              Liste des chariots + filtres + évolution
├── chariot_form.php          Fiche chariot (ajout / modification)
├── chariot_save.php          Traitement de la fiche chariot
├── chariot_delete.php        Suppression d'un chariot
├── chariot_historique.php    Historique d'un chariot
├── palettes.php              Liste des palettes + filtres + évolution
├── palette_form.php          Fiche palette (ajout / modification)
├── palette_save.php          Traitement de la fiche palette
├── palette_delete.php        Suppression d'une palette
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
