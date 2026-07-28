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
- **Tableau de bord Occupation** (`occupation.php`) : taux d'occupation du
  magasin en palettes, en temps réel (jauge, indicateurs, grille d'emplacements).
- **Entrepôt 3D** (`entrepot3d.php`) : visualisation 3D interactive des palettes
  dans des racks, colorées par catégorie (rotation, zoom, filtres, KPI).
- **Tri** cliquable sur les colonnes principales.
- **Cartes de synthèse** : nombre d'articles, total disponible, valeur totale.
- **Export CSV** (compatible Excel : séparateur `;` + BOM UTF-8).
- **Impression** propre (bouton « Imprimer »).

## Quatre modes de fonctionnement

Le mode est choisi par la constante `DATA_SOURCE` en haut de `config/database.php`.
Trois d'entre eux se connectent à SQL Server **sans télécharger le pilote ODBC x64** :

| Mode | `DATA_SOURCE` | Pilote requis | Extension PHP | Données |
|------|---------------|---------------|---------------|---------|
| **OLE DB / COM** (défaut) | `'ado'` | **aucun** (SQLOLEDB intégré) | `com_dotnet` | temps réel |
| **ODBC intégré** | `'pdo_odbc'` | **aucun** (pilote « SQL Server » intégré) | `pdo_odbc` | temps réel |
| **CSV** | `'csv'` | **aucun** | aucune | fichier exporté |
| **SQL Server (rapide)** | `'sqlserver'` | ODBC Driver 18 (à télécharger) | `pdo_sqlsrv` | temps réel |

> Les modes `ado` et `pdo_odbc` évitent tous deux le téléchargement du pilote
> ODBC x64 : ils s'appuient sur des composants **déjà présents dans Windows**.
> Ils diffèrent seulement par l'extension PHP à activer (`com_dotnet` ou
> `pdo_odbc`) — choisissez celle qui est disponible chez vous.

---

## Mode OLE DB / COM (temps réel, SANS pilote ODBC)

Connexion directe à SQL Server **sans installer le pilote ODBC de Microsoft**.
PHP utilise le fournisseur **OLE DB** via COM/ADODB. Le fournisseur `SQLOLEDB`
est intégré à Windows : rien à installer côté base.

1. Vérifiez que l'extension **`com_dotnet`** est activée dans le `php.ini` de
   Laragon (ligne `extension=com_dotnet` non commentée), puis redémarrez Apache.
2. Dans `config/database.php`, laissez `DATA_SOURCE = 'ado'` et renseignez
   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
3. Ouvrez `http://localhost/rapport-stock/`.

Le fournisseur est choisi par `ADO_PROVIDER` :

- `'auto'` (défaut) essaie dans l'ordre `MSOLEDBSQL` → `SQLNCLI11` → `SQLOLEDB`.
  `SQLOLEDB` étant présent sur tout Windows, la connexion aboutit sans
  installation supplémentaire.
- Vous pouvez forcer un fournisseur précis (ex. `'SQLOLEDB'`).

> Diagnostic : ouvrez `test.php` — il indique si `com_dotnet` est présent, quel
> fournisseur OLE DB a répondu, et combien de lignes la vue renvoie.

---

## Mode ODBC intégré (temps réel, SANS télécharger le pilote ODBC x64)

Alternative au mode `ado` si l'extension `com_dotnet` n'est pas activable.
Utilise le pilote ODBC **« SQL Server »** livré d'origine avec Windows (MDAC) —
à ne pas confondre avec « ODBC Driver 17/18 for SQL Server » qui, lui, se
télécharge.

1. Activez l'extension **`pdo_odbc`** dans le `php.ini` de Laragon
   (`extension=pdo_odbc`), puis redémarrez Apache.
2. Dans `config/database.php`, mettez `DATA_SOURCE = 'pdo_odbc'` et renseignez
   `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
3. Ouvrez `http://localhost/rapport-stock/` (ou d'abord `test.php`).

`PDO_ODBC_DRIVER = 'auto'` essaie `{SQL Server}` (intégré) puis les Native
Client / ODBC Driver éventuellement présents. Vous pouvez forcer un pilote
précis.

---

## Mode CSV (aucun pilote, données figées)

Idéal s'il n'est pas possible d'ouvrir une connexion directe.

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

### Export automatique sans ODBC ni extension PHP (PowerShell)

Le script `outils/export-stock.ps1` lit la vue `V_BH_STGlob` directement via
**.NET SqlClient** (intégré à Windows — aucun pilote ODBC, aucune extension PHP)
et écrit `data/stock.csv`. Exécution :

```powershell
powershell -ExecutionPolicy Bypass -File outils\export-stock.ps1
```

Adaptez si besoin `-Database`, `-Server`, `-User`, `-Password` en tête du script.
Vous pouvez le **planifier** (Planificateur de tâches Windows) pour rafraîchir le
CSV automatiquement, par ex. chaque nuit — le rapport reste ainsi à jour sans
connexion directe depuis PHP.

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

## Tableau de bord Occupation

La page `occupation.php` calcule l'occupation du magasin en **palettes** :

- **Palettes occupées** par article = `plafond(Disponible ÷ Qté par palette)`
  (une palette entamée occupe un emplacement complet), puis somme sur le magasin.
- Comparaison à la **capacité totale** du magasin (`CAPACITE_PALETTES`) pour
  obtenir : palettes occupées, palettes libres, **taux d'occupation (%)** et
  **taux d'espace disponible (%)**.
- Visualisation : jauge circulaire, cartes d'indicateurs, barre d'occupation,
  grille des emplacements (coloré = occupé), et détail par article.
- **Temps réel** : la page se rafraîchit automatiquement (60 s par défaut) et lit
  la source configurée (OLE DB, ODBC, ou CSV).

> **À configurer** : dans `config/database.php`, mettez `CAPACITE_PALETTES` à la
> capacité réelle (nombre d'emplacements palette) du magasin `MAGASIN_FILTRE`.
> Les articles sans « Qté/Palette » renseignée ne sont pas comptabilisés (et
> sont signalés). La grille visuelle s'affiche quand la capacité ≤ 300.

## Entrepôt 3D

La page `entrepot3d.php` affiche l'entrepôt en **3D interactive** (Three.js,
embarqué localement dans `assets/vendor/` — aucun accès Internet requis) :

- chaque **palette occupée** est une boîte 3D placée dans des bâtis de rack ;
  les emplacements libres restent en fil de fer (capacité vs occupation) ;
- **couleur par catégorie** (`U_u_cat`), avec légende et filtres cliquables ;
- **KPI** : capacité, palettes occupées/libres, taux d'occupation / espace libre ;
- navigation souris : glisser = pivoter, molette = zoom, clic droit = déplacer ;
  boutons « Rotation auto » et « Recentrer ».

> **Note sur les données** : la vue `V_BH_STGlob` ne contient ni l'ancienneté
> des palettes ni le plan physique (allée/niveau). Contrairement à un outil type
> Power BI « Bodega 3D » (qui colore par ancienneté et positionne chaque palette
> réelle), cette page **calcule** les palettes depuis le stock et les **dispose
> dans une grille de racks générée**, colorées par catégorie. Si vous disposez
> d'un champ d'ancienneté ou d'un plan (allée/niveau/position), on peut brancher
> un feu tricolore (vert/jaune/rouge) et un placement fidèle.

## Diagnostic

Ouvrez `http://localhost/rapport-stock/test.php` : la page vérifie, selon le
mode, la présence/lecture du CSV **ou** l'extension PHP, le pilote ODBC et la
connexion. En mode `ado`, elle **chronomètre** la connexion et la lecture pour
localiser une lenteur. Supprimez `test.php` une fois le diagnostic terminé.

## Performance

Le mode `ado` (OLE DB/COM) est pratique car il ne demande aucun pilote ODBC,
mais il est intrinsèquement plus lent que `pdo_sqlsrv`. Optimisations déjà en
place :

- **Restriction poussée dans SQL** : la clause `WHERE [Magasin] = …`
  (constante `MAGASIN_FILTRE`) est ajoutée à la requête, donc SQL Server ne
  renvoie que les lignes utiles au lieu de toute la vue.
- **Objets Field mis en cache** : en mode `ado`, les colonnes sont résolues une
  seule fois puis relues par référence à chaque ligne (évite une résolution COM
  par cellule, principal coût de lenteur).

Pour aller plus loin :

1. **Figer le fournisseur OLE DB.** Si `test.php` indique que le fournisseur
   retenu n'est pas le premier essayé, remplacez `ADO_PROVIDER = 'auto'` par le
   fournisseur réel (ex. `'SQLOLEDB'`) : on évite d'essayer à chaque page des
   fournisseurs absents.
2. **Passer en `pdo_sqlsrv` (le plus rapide).** Si vous pouvez installer le
   pilote **ODBC Driver 18 for SQL Server**, mettez `DATA_SOURCE = 'sqlserver'`.
   C'est nettement plus performant que COM/OLE DB. Le reste du code est déjà
   prêt (même requête, même restriction magasin).

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
├── config/database.php     Choix de la source (OLE DB / CSV / SQL Server) + connexions
├── data/stock.csv          Fichier de données (mode CSV) — exemple fourni
├── includes/header.php     En-tête commun
├── includes/footer.php     Pied de page commun
├── includes/report.php     Chargement + filtres + tri + totaux
├── index.php               Rapport (filtres, tri, synthèse)
├── occupation.php          Tableau de bord d'occupation (palettes)
├── entrepot3d.php          Visualisation 3D de l'entrepôt
├── assets/vendor/          Three.js + OrbitControls (embarqués, hors-ligne)
├── export.php              Export CSV
├── outils/export-stock.ps1 Export SQL Server -> CSV via .NET (sans ODBC)
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
