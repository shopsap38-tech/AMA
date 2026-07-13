# FPMS — Fleet & Pallet Management System

Application **PHP + MySQL** de gestion de flotte de chariots et de palettes.

## Fonctionnalités

### 🏠 Accueil
- KPI en temps réel : total chariots, chariots électriques / diesel, disponibles,
  en maintenance, en panne, palettes conformes / non conformes, taux de disponibilité.
- Accès rapide aux modules Chariots et Palettes.

> Les graphiques sont des histogrammes agrandis et lisibles ; **le nombre** est affiché
> au-dessus de chaque colonne (plugin `assets/charts.js`).

### 🚜 Gestion des chariots
Le module Chariots regroupe le tableau de bord et la gestion :
- KPI façon suivi : **Total chariots, Opérationnels, En panne, Réparation**, électriques / diesel.
- Graphiques (histogrammes, nombre au-dessus des colonnes) : chariots par état,
  par type, temps d'arrêt par chariot, chariots mis en service (**évolution Jour / Mois / Année**).
- **Répartition des chariots par unité** (Liquide, Sachet, Transfert, MP, Chargement,
  Papier, Retour, Déchet) : graphique + tableau (nombre et pourcentage).
- Fiche : marque, type (Électrique / Diesel), état, **unité** et **date de mise en service**.
- Filtres, historique des changements d'état. (Chaque chariot est identifié par une réf. `#id`.)

### 📦 Gestion des palettes
Le module Palettes regroupe le tableau de bord et la gestion :
- KPI par état (conformes / non conformes / cassées) et quantité totale.
- Graphique **« Nombre de palettes par état »** : histogramme **empilé dans le temps**
  (conformes / non conformes / cassées) avec sélecteur **Jour / Mois / Année**.
- Fiche : état, **quantité par lot**, **date** (modifiable) et commentaire.
- Les indicateurs additionnent les quantités (une ligne = un lot de N palettes).
  (Chaque palette est identifiée par une réf. `#id`.)

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
   Le script crée une **base vide** (aucune donnée de démonstration) ; vous saisissez
   vos chariots et palettes depuis l'application.
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
├── index.php                 Accueil (KPI + accès rapide)
├── chariots.php              Chariots : dashboard + gestion (KPI, graphiques, unité, liste)
├── chariot_form.php          Fiche chariot (ajout / modification)
├── chariot_save.php          Traitement de la fiche chariot
├── chariot_delete.php        Suppression d'un chariot
├── chariot_historique.php    Historique d'un chariot
├── palettes.php              Palettes : dashboard + gestion (KPI, graphiques, liste)
├── palette_form.php          Fiche palette (ajout / modification)
├── palette_save.php          Traitement de la fiche palette
├── palette_delete.php        Suppression d'une palette
├── rapports.php              Rapports journalier / mensuel / annuel
├── export_excel.php          Export CSV / Excel d'un rapport
├── export_pdf.php            Export PDF (page imprimable) d'un rapport
├── alertes.php               Alertes (stock faible, indisponibilités)
├── statistiques.php          Disponibilité + temps d'arrêt
└── schema.sql                Structure de la base (base vide, sans données)
```

## Remarques techniques

- Requêtes préparées (PDO) partout pour éviter les injections SQL.
- Sorties échappées avec `htmlspecialchars` pour éviter les failles XSS.
- Chart.js est chargé depuis un CDN (connexion Internet requise pour les graphiques).
