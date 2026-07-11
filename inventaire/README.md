# Inventaire

Application PHP + MySQL de gestion des stocks (quantités, entrées/sorties).

## Fonctionnalités

- Gestion des produits (ajout, modification, suppression)
- Suivi des quantités en stock avec seuil d'alerte
- Mouvements de stock (entrées / sorties) avec historique
- Historique complet des mouvements

## Installation avec Laragon

1. Placez ce dossier dans `C:\laragon\www\inventaire`.
2. Démarrez Laragon (Apache + MySQL).
3. Ouvrez la base de données via `phpMyAdmin` ou HeidiSQL, puis importez `schema.sql` :
   ```sql
   SOURCE C:/laragon/www/inventaire/schema.sql;
   ```
   ou copiez/collez le contenu de `schema.sql` dans un nouvel onglet SQL.
4. Vérifiez les identifiants de connexion dans `config/database.php` (par défaut : utilisateur `root`, mot de passe vide, ce qui correspond à la configuration MySQL par défaut de Laragon).
5. Ouvrez `http://inventaire.test` (ou `http://localhost/inventaire`) dans votre navigateur.

## Structure

```
inventaire/
├── assets/style.css       Feuille de style
├── config/database.php    Connexion PDO à MySQL
├── includes/               Header/footer communs
├── index.php               Liste des produits
├── produit_form.php        Formulaire d'ajout/modification
├── produit_save.php        Traitement du formulaire produit
├── produit_delete.php      Suppression d'un produit
├── mouvement.php           Formulaire d'entrée/sortie de stock
├── mouvement_save.php      Traitement du mouvement de stock
├── historique.php          Historique des mouvements
└── schema.sql               Structure de la base de données
```
