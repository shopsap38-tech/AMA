# Suivi des non-conformités (PHP + MySQL)

Application web convertie depuis le fichier Excel **NON_CONFORME.xlsx**.
Elle permet de suivre les produits non conformes de l'entrepôt et reproduit
les 4 feuilles du classeur d'origine :

| Feuille Excel        | Page de l'application        | Table MySQL        |
|----------------------|------------------------------|--------------------|
| `DASHBOARD`          | `index.php` (Dashboard)      | *(agrégats)*       |
| `PR SEMI-FINI-MATIC` | `semi_fini.php`              | `nc_semi_fini`     |
| `DESINFECTANT`       | `desinfectant.php`           | `nc_desinfectant`  |
| `BIG BAG`            | `big_bag.php`                | `nc_big_bag`       |

## Fonctionnalités

- **Dashboard** : synthèse du nombre de palettes / big bags, du poids total et
  de la valeur bloquée pour chaque catégorie.
- **CRUD complet** (ajout / modification / suppression) pour chacune des trois
  catégories de non-conformités.
- Calculs automatiques repris de l'Excel :
  - Poids total en tonnes = poids (kg) ÷ 1000 (semi-fini, big bag).
  - Total (valeur) = stock × prix unitaire (désinfectant).
  - Poids total (kg) = stock × poids par carton (désinfectant).
- Totaux affichés en pied de tableau, comme dans le classeur.

## Prérequis

- PHP 7.4 ou supérieur (extension PDO MySQL activée)
- MySQL / MariaDB
- Un serveur web (Apache, Nginx) ou le serveur intégré de PHP

## Installation

1. **Créer la base et les tables :**

   ```bash
   mysql -u root -p < schema.sql
   ```

2. **(Facultatif) Charger les données d'exemple du fichier Excel :**

   ```bash
   mysql -u root -p < seed.sql
   ```

3. **Configurer la connexion** dans `config/database.php`
   (hôte, nom de base, utilisateur, mot de passe).

4. **Lancer l'application.** Le plus simple, avec le serveur intégré de PHP,
   depuis le dossier *parent* de `non_conforme/` :

   ```bash
   php -S localhost:8000
   ```

   Puis ouvrir <http://localhost:8000/non_conforme/index.php>.

   > Les chemins des liens et des ressources sont préfixés par `/non_conforme`,
   > l'application doit donc être servie depuis ce sous-dossier (comme l'app
   > `inventaire` du même dépôt).

## Structure

```
non_conforme/
├── config/database.php      # Connexion PDO
├── includes/                # En-tête et pied de page communs
├── assets/style.css         # Feuille de style
├── index.php                # Dashboard
├── semi_fini*.php           # CRUD PF Semi-fini-matic
├── desinfectant*.php        # CRUD Désinfectant
├── big_bag*.php             # CRUD Big Bag
├── schema.sql               # Création de la base et des tables
└── seed.sql                 # Données initiales issues de l'Excel
```

## Sécurité

- Toutes les requêtes utilisent des **requêtes préparées PDO** (protection
  contre les injections SQL).
- Toutes les sorties HTML sont échappées avec `htmlspecialchars()`.
