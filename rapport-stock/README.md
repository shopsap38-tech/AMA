# Rapport de suivi de stock

Application PHP qui affiche un **rapport de suivi de stock** à partir de la vue
SQL Server `[dbo].[V_BH_STGlob]` (base SAP Business One) hébergée sur
`192.168.1.240`.

## Fonctionnalités

- Rapport de toutes les lignes de la vue `V_BH_STGlob` :
  Magasin, Item Code, Item Name, Disponible, UoM, CodeBars, InActif, Poids,
  Price, Value, U_u_forcast, U_Qte_Palette, U_u_cat, U_u_brand.
- Filtres : par **magasin**, **catégorie**, **marque**, **recherche** (code
  article, nom, code-barres), et option pour **masquer les articles inactifs**.
- **Tri** cliquable sur les colonnes principales.
- **Cartes de synthèse** : nombre d'articles, total disponible, valeur totale.
- **Export CSV** (compatible Excel : séparateur `;` + BOM UTF-8).
- **Impression** propre (bouton « Imprimer »).

## Deux modes de fonctionnement

Le mode est choisi par la constante `DATA_SOURCE` en haut de `config/database.php` :

| Mode | `DATA_SOURCE` | Pilote requis | Données |
|------|---------------|---------------|---------|
| **CSV** (par défaut) | `'csv'` | **aucun** | fichier exporté, à rafraîchir manuellement |
| **SQL Server** | `'sqlserver'` | pilote ODBC Microsoft + `pdo_sqlsrv` | temps réel |

---

## Mode CSV (recommandé — aucun pilote à installer)

Idéal si le pilote ODBC Microsoft n'est pas installé sur le serveur PHP.

1. Dans **SSMS** (SQL Server Management Studio) ou dans SAP, exécutez la requête
   de la vue puis exportez le résultat en **CSV** :
   - SSMS : clic droit sur la grille de résultats → *Save Results As…* → `.csv`.
   - Ou : *Résultats dans un fichier* / export Excel enregistré en CSV.
2. Nommez le fichier **`stock.csv`** et placez-le dans le dossier **`data/`**
   (à côté de `data/stock.csv` fourni en exemple — écrasez-le).
3. Vérifiez que `DATA_SOURCE` vaut `'csv'` dans `config/database.php`.
4. Ouvrez `http://localhost/rapport-stock/`.

Le lecteur CSV est tolérant : il gère le **BOM UTF-8**, détecte automatiquement
le **séparateur** (`;`, `,` ou tabulation) et reconnaît les colonnes **par leur
nom d'en-tête** (l'ordre importe peu). Les nombres au format français
(`1 020,50`) comme anglais (`1020.50`) sont acceptés.

En-têtes attendus (première ligne du CSV) :

```
Magasin;Item Code;Item Name;Disponible;UoM;CodeBars;InActif;Poids;Price;Value;U_u_forcast;U_Qte_Palette;U_u_cat;U_u_brand
```

> Pour actualiser le rapport, ré-exportez la vue et remplacez `data/stock.csv`.

---

## Mode SQL Server (connexion en direct)

Nécessite un pilote PDO SQL Server sur le serveur PHP :

- **Windows / Laragon** : pilote **ODBC Driver 18 for SQL Server** (x64) +
  extension `pdo_sqlsrv` activée.
- **Linux / macOS** : `pdo_dblib` (FreeTDS).

Dans `config/database.php`, mettez `DATA_SOURCE = 'sqlserver'` puis adaptez :

```php
const DB_HOST = '192.168.1.240'; // serveur SQL Server
const DB_PORT = 1433;
const DB_NAME = 'SBO_AMA';       // <-- REMPLACEZ par le nom réel de la base SAP B1
const DB_USER = 'sa';
const DB_PASS = '1AQWXCV';
```

> **Important** : `DB_NAME` doit correspondre au nom exact de votre base SAP
> Business One (celle qui contient la vue `V_BH_STGlob`).

## Diagnostic

Ouvrez `http://localhost/rapport-stock/test.php` : la page vérifie, selon le
mode, la présence/lecture du CSV **ou** l'extension PHP, le pilote ODBC et la
connexion. Supprimez `test.php` une fois le diagnostic terminé.

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
├── config/database.php     Choix de la source (CSV / SQL Server)
├── data/stock.csv          Fichier de données (mode CSV) — exemple fourni
├── includes/header.php     En-tête commun
├── includes/footer.php     Pied de page commun
├── includes/report.php     Chargement + filtres + tri + totaux
├── index.php               Rapport (filtres, tri, synthèse)
├── export.php              Export CSV
├── test.php                Page de diagnostic
└── README.md
```

## Sécurité

- Les filtres sont passés en **requêtes préparées** (paramètres liés) ; les
  colonnes de tri sont validées par **liste blanche**.
- Ne publiez pas ce dossier tel quel sur Internet : le mot de passe est en clair
  dans `config/database.php`. Pour un usage réseau interne, protégez l'accès
  (VPN / réseau local) ou déplacez les identifiants dans des variables
  d'environnement.
