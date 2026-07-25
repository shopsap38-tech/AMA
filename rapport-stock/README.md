# Rapport de suivi de stock

Application PHP qui affiche un **rapport de suivi de stock** à partir de la vue
SQL Server `[dbo].[V_BH_STockTracking]` (base SAP Business One) hébergée sur
`192.168.1.240`.

## Fonctionnalités

- Rapport de toutes les lignes de la vue `V_BH_STockTracking` :
  Magasin, Item Code, Item Name, Disponible, UoM, CodeBars, InActif, Poids,
  Price, Value, U_u_forcast.
- Filtres : par **magasin**, **recherche** (code article, nom, code-barres),
  et option pour **masquer les articles inactifs**.
- **Tri** cliquable sur les colonnes principales.
- **Cartes de synthèse** : nombre d'articles, total disponible, valeur totale.
- **Export CSV** (compatible Excel : séparateur `;` + BOM UTF-8).
- **Impression** propre (bouton « Imprimer »).

## Prérequis

PHP avec un pilote PDO SQL Server :

- **Windows / Laragon** : activer l'extension `pdo_sqlsrv`
  (pilotes Microsoft « Drivers for PHP for SQL Server » + « ODBC Driver for SQL Server »).
- **Linux / macOS** : le pilote `pdo_dblib` (FreeTDS) fonctionne également.

Le code essaie `sqlsrv` puis `dblib` automatiquement.

## Configuration

Ouvrez `config/database.php` et adaptez les constantes en haut du fichier :

```php
const DB_HOST = '192.168.1.240'; // serveur SQL Server
const DB_PORT = 1433;
const DB_NAME = 'SBO_AMA';       // <-- REMPLACEZ par le nom réel de la base SAP B1
const DB_USER = 'sa';
const DB_PASS = '1AQWXCV';
```

> **Important** : `DB_NAME` doit correspondre au nom exact de votre base SAP
> Business One (celle qui contient la vue `V_BH_STockTracking`). C'est le seul
> paramètre à coup sûr à vérifier.

## Installation avec Laragon

1. Placez ce dossier dans `C:\laragon\www\rapport-stock`.
2. Activez `pdo_sqlsrv` dans le `php.ini` utilisé par Laragon, puis redémarrez
   Apache.
3. Renseignez `config/database.php` (voir ci-dessus).
4. Ouvrez `http://localhost/rapport-stock/` (ou `http://rapport-stock.test`).

## Structure

```
rapport-stock/
├── assets/style.css        Feuille de style (écran + impression)
├── config/database.php     Connexion PDO à SQL Server
├── includes/header.php     En-tête commun
├── includes/footer.php     Pied de page commun
├── includes/report.php     Filtres + requête + totaux partagés
├── index.php               Rapport (filtres, tri, synthèse)
├── export.php              Export CSV
└── README.md
```

## Sécurité

- Les filtres sont passés en **requêtes préparées** (paramètres liés) ; les
  colonnes de tri sont validées par **liste blanche**.
- Ne publiez pas ce dossier tel quel sur Internet : le mot de passe est en clair
  dans `config/database.php`. Pour un usage réseau interne, protégez l'accès
  (VPN / réseau local) ou déplacez les identifiants dans des variables
  d'environnement.
