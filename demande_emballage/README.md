# Demande Emballage

Application web professionnelle de **gestion des demandes d'emballage**, développée en
**PHP natif 8.x + MySQL**, avec une interface **responsive** (Bootstrap 5) qui fonctionne
sur ordinateur, tablette et smartphone (Android / iPhone) via un simple navigateur — aucune
application APK n'est nécessaire.

## Fonctionnalités

Le circuit d'une demande suit trois statuts :

| Statut        | Créé / modifié par | Horodatage enregistré |
|---------------|--------------------|-----------------------|
| **En attente**| Demandeur (création)| `cree_le`            |
| **En route**  | Préparateur         | `prepare_le`          |
| **C'est fait**| Agent Facturation   | `cloture_le` + calcul du délai SLA |

### Rôles

- **Demandeur** — crée une demande (article, quantité, commentaire) ; suit l'avancement de ses demandes.
- **Préparateur** — voit les demandes *En attente*, joint une photo (prise ou importée depuis le téléphone/PC) et passe la demande *En route*.
- **Agent Facturation** — voit les demandes *En route*, confirme le traitement et passe la demande *C'est fait*.
- **Administrateur** — accès complet : tableau de bord, toutes les demandes, gestion des utilisateurs.

### Calcul automatique du délai (SLA)

À la clôture, le système calcule le délai en **minutes** entre la création et la clôture
(`TIMESTAMPDIFF`). Deux indicateurs sont suivis :

- Demandes traitées en **≤ 30 minutes** ;
- Demandes traitées en **> 30 minutes**.

Le seuil est configurable via la constante `SLA_SEUIL` (`config/config.php`).

### Tableau de bord

- **Graphique en colonnes** — nombre de demandes MTD (≤ 30 min vs > 30 min).
- **Graphique linéaire** — tendance quotidienne du respect du délai sur le mois en cours.
- Cartes indicateurs : taux de respect, charge de travail en cours.

Les graphiques utilisent [Chart.js](https://www.chartjs.org/).

## Technologies

- PHP natif 8.x (PDO, requêtes préparées)
- MySQL (utf8mb4)
- HTML5 / CSS3
- Bootstrap 5 + Bootstrap Icons
- JavaScript (Chart.js)

## Installation (Laragon / WAMP / XAMPP)

1. Placez ce dossier dans la racine web, par ex. `C:\laragon\www\demande_emballage`.
2. Démarrez Apache + MySQL.
3. Importez le schéma dans MySQL (phpMyAdmin, HeidiSQL, ou en ligne de commande) :
   ```sql
   SOURCE C:/laragon/www/demande_emballage/schema.sql;
   ```
   Cela crée la base `demande_emballage`, les tables et **4 comptes de démonstration**.
4. Vérifiez les identifiants MySQL dans `config/database.php`
   (par défaut : hôte `localhost`, utilisateur `root`, mot de passe vide — configuration Laragon standard).
5. Assurez-vous que le dossier `uploads/` est accessible en écriture par le serveur web.
6. Ouvrez l'application :
   - `http://localhost/demande_emballage/` ou l'hôte virtuel `http://demande-emballage.test`
   - Depuis un téléphone sur le même réseau : `http://ADRESSE-IP-DU-PC/demande_emballage/`

L'URL de base est **détectée automatiquement** (`BASE_URL`), l'application fonctionne donc
aussi bien en sous-dossier qu'en hôte virtuel.

## Comptes de démonstration

Mot de passe commun : **`password`**

| Rôle              | E-mail                    |
|-------------------|---------------------------|
| Administrateur    | `admin@demo.local`        |
| Demandeur         | `demandeur@demo.local`    |
| Préparateur       | `preparateur@demo.local`  |
| Agent Facturation | `facturation@demo.local`  |

> ⚠️ En production, changez ces mots de passe (menu **Utilisateurs**) et les identifiants MySQL.

## Structure

```
demande_emballage/
├── config/
│   ├── database.php        Connexion PDO à MySQL
│   └── config.php          Session, BASE_URL, constantes, helpers
├── includes/
│   ├── functions.php       Auth, rôles, CSRF, flash, formatage
│   ├── header.php          En-tête + navigation selon le rôle (Bootstrap)
│   └── footer.php          Pied de page + scripts
├── auth/
│   ├── login.php           Connexion
│   └── logout.php          Déconnexion
├── assets/
│   ├── css/style.css       Styles complémentaires
│   └── js/dashboard.js     Graphiques Chart.js
├── uploads/                Photos jointes (protégé contre l'exécution)
├── index.php               Aiguillage vers l'espace du rôle
├── demande_form.php        Formulaire de nouvelle demande (Demandeur)
├── demande_save.php        Enregistrement de la demande
├── mes_demandes.php        Suivi des demandes (Demandeur / Admin)
├── preparateur.php         File « En attente » + photo (Préparateur)
├── preparateur_save.php    Passage « En route »
├── facturation.php         File « En route » (Agent Facturation)
├── facturation_save.php    Passage « C'est fait » + calcul SLA
├── dashboard.php           Tableau de bord (2 graphiques)
├── users/                  Gestion des utilisateurs (Admin)
│   ├── index.php
│   ├── form.php
│   ├── save.php
│   └── delete.php
└── schema.sql              Structure + données de démonstration
```

## Sécurité

- Mots de passe hachés (`password_hash` / bcrypt).
- Requêtes préparées PDO (protection injection SQL).
- Échappement systématique en sortie (`htmlspecialchars`).
- Jeton **CSRF** sur tous les formulaires POST.
- Contrôle d'accès par rôle sur chaque page.
- Validation du type MIME et de la taille des photos ; exécution PHP désactivée dans `uploads/`.
