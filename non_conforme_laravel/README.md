# Suivi des non-conformités — Laravel

Version **Laravel** de l'application de suivi des non-conformités, convertie
depuis le fichier Excel **NON_CONFORME.xlsx**. Elle reprend les 4 volets du
classeur : un dashboard avec graphique et trois catégories de produits non
conformes (Semi-fini, Désinfectant, Big Bag).

> Le dépôt contient aussi une version en PHP « natif » dans le dossier
> `../non_conforme`. Ce projet-ci en est le portage sur le framework Laravel
> (Eloquent, migrations, seeders, contrôleurs de ressources, vues Blade).

## Stack

- **Laravel 13** (PHP 8.2+)
- **MySQL / MariaDB** (SQLite possible pour un démarrage rapide)
- Vues **Blade**, graphique **SVG** rendu côté serveur (aucune dépendance JS)

## Architecture

| Élément | Fichier |
|---------|---------|
| Modèles Eloquent | `app/Models/NcSemiFini.php`, `NcDesinfectant.php`, `NcBigBag.php` |
| Migrations | `database/migrations/2026_07_14_0000*_create_nc_*_table.php` |
| Données initiales (Excel) | `database/seeders/NonConformeSeeder.php` |
| Contrôleurs | `app/Http/Controllers/{Dashboard,SemiFini,Desinfectant,BigBag}Controller.php` |
| Routes | `routes/web.php` |
| Vues Blade | `resources/views/{layouts,dashboard,semi_fini,desinfectant,big_bag}` |
| Générateur de graphique | `app/Support/Chart.php` |
| Styles | `public/css/app.css` |

Les valeurs dérivées de l'Excel sont calculées par des **accesseurs Eloquent** :

- `NcSemiFini::poids_total_t` = poids (kg) ÷ 1000
- `NcDesinfectant::total` = stock × prix unitaire
- `NcDesinfectant::poids_total_kg` = stock × poids par carton
- `NcDesinfectant::is_expired` = date d'expiration dépassée
- `NcBigBag::tonnage_t` = tonnage (kg) ÷ 1000

## Installation

```bash
cd non_conforme_laravel

# 1. Dépendances PHP
composer install

# 2. Fichier d'environnement + clé applicative
cp .env.example .env
php artisan key:generate

# 3. Base de données
#    - Option MySQL : créer la base « non_conforme » et renseigner les
#      identifiants DB_* dans .env (valeurs par défaut déjà présentes)
#    - Option rapide SQLite : mettre DB_CONNECTION=sqlite dans .env puis
#      touch database/database.sqlite

# 4. Migrations + données d'exemple issues de l'Excel
php artisan migrate --seed

# 5. Lancer le serveur
php artisan serve
```

L'application est ensuite disponible sur <http://127.0.0.1:8000>.

## Fonctionnalités

- **Dashboard** : cartes de synthèse (palettes, poids, valeur bloquée) et
  grand graphique en barres « Palettes / big bags par catégorie ».
- **CRUD complet** pour chaque catégorie (liste, création, modification,
  suppression) avec **validation côté serveur**.
- **Tableau Désinfectant clair** : type en badge coloré, article regroupé
  (n° + description), colonnes numériques alignées, dates expirées mises en
  évidence, lignes alternées.
- Totaux calculés et affichés en pied de chaque tableau.

## Sécurité

- Protection **CSRF** sur tous les formulaires (jetons Blade `@csrf`).
- Requêtes **Eloquent** paramétrées (pas d'injection SQL).
- Échappement automatique des sorties Blade (`{{ }}`).
