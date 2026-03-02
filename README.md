# Mediatekformation

## Présentation
Ce dépôt contient les améliorations et nouvelles fonctionnalités apportées à l'application MediatekFormation dans le cadre d'un atelier de professionnalisation. Les interventions portent sur :<br>
•	La qualité du code (nettoyage SonarLint, respect des bonnes pratiques).<br>
•	L'ajout d'une fonctionnalité dans le front office (tri et affichage du nombre de formations par playlist).<br>
•	Le développement d'un back office complet et sécurisé (gestion des formations, playlists et catégories).<br>
•	La mise en place de tests automatisés (unitaires, d'intégration et fonctionnels).<br>
•	La génération d'une documentation technique (phpDoc).<br>
•	Le déploiement en ligne avec CI/CD via Microsoft Azure et GitHub Actions.<br>

## Amélioration du front office
### Nouvelles fonctionnalités de la page Playlists
La page "Playlists" a été enrichie avec deux nouvelles fonctionnalités :<br>
•	Une nouvelle colonne "nombre de formations" indique le nombre de formations contenues dans chaque playlist.<br>
•	Deux boutons de tri ("<" et ">") permettent de trier les playlists selon ce nombre de formations, en ordre croissant ou décroissant.<br>
La page de détail d'une playlist affiche désormais également le nombre de formations qu'elle contient ("nombre de vidéos").<br>

## Le back office
Le back office est accessible en ajoutant "/admin" à l'URL de base de l'application. L'accès nécessite une authentification avec un compte administrateur. Il contient la même bannière que le front office ainsi qu'un menu comportant trois entrées : "Formations", "Playlists" et "Catégories". Un bouton "Déconnexion" est présent sur toutes les pages ; il redirige vers l'accueil du front office.<br>

## Les différentes pages du back office
Voici les 6 pages correspondant aux différents cas d'utilisation du back office.

### Page 1 : connexion au back office
Cette page est affichée automatiquement lors d'un accès à "/admin" sans être authentifié.<br>
La partie centrale contient un formulaire de connexion avec deux champs : "Nom" (identifiant) et "Mot de passe".<br>
Un bouton "Se connecter" soumet le formulaire et un lien "Retour à l'accueil" permet de revenir au front office sans s'authentifier.<br>
En cas d'identifiants incorrects, un message d'erreur est affiché. Après connexion réussie, l'administrateur est redirigé vers la page de gestion des formations.<br>

### Page 2 : gestion des formations
Cette page est accessible via le menu "Formations" du back office.<br>
La partie haute contient un bouton "Ajouter une formation" permettant d'accéder au formulaire d'ajout.<br>
La partie centrale contient un tableau présentant toutes les formations. Les mêmes tris et filtres que dans le front office sont disponibles : tri et filtre sur le titre, tri et filtre sur la playlist, filtre par catégorie, tri par date.<br>
Pour chaque formation, deux boutons sont disponibles en dernière colonne :<br>
•	"Supprimer" : affiche une boîte de dialogue de confirmation ("Confirmer la suppression ?") avant de procéder à la suppression. La formation est également retirée de sa playlist.<br>
•	"Modifier" : accède au formulaire de modification prérempli avec les données de la formation sélectionnée.<br>

### Page 3 : ajout / modification d'une formation
Cette page est accessible via le bouton "Ajouter une formation" ou le bouton "Modifier" de la liste des formations.<br>
Le titre de la page est affiché dynamiquement : "Ajouter une formation" lors d'un ajout, "Modifier une formation" lors d'une modification.<br>
Le formulaire contient les champs suivants :<br>
•	Titre (obligatoire, 100 caractères maximum).<br>
•	Identifiant vidéo (obligatoire).<br>
•	Date de publication (obligatoire, sélection via un calendrier, ne peut pas être postérieure à la date du jour).<br>
•	Playlist de rattachement (obligatoire, sélection dans une liste déroulante — une seule playlist par formation).<br>
•	Catégories (facultatif, sélection multiple possible — plusieurs catégories par formation).<br>
•	Description (facultatif).<br>
Lors d'une modification, tous les champs sont préremplis avec les données existantes. Un bouton "Enregistrer" valide le formulaire (avec contrôle des saisies) et un bouton "Retour" permet d'annuler et de revenir à la liste.<br>

### Page 4 : gestion des playlists
Cette page est accessible via le menu "Playlists" du back office.<br>
La partie haute contient un bouton "Ajouter une playlist" permettant d'accéder au formulaire d'ajout.<br>
La partie centrale contient un tableau présentant toutes les playlists avec deux colonnes : "playlist" (avec tri croissant/décroissant et filtre par texte) et "nombre de formations" (avec tri croissant/décroissant).<br>
Pour chaque playlist, deux boutons sont disponibles :<br>
•	"Supprimer" : uniquement disponible si aucune formation n'est rattachée à la playlist (contrôle bloqué côté serveur). Un popup de confirmation est affiché avant la suppression.<br>
•	"Modifier" : accède au formulaire de modification prérempli.<br>

### Page 5 : ajout / modification d'une playlist
Cette page est accessible via le bouton "Ajouter une playlist" ou le bouton "Modifier" de la liste des playlists.<br>
Le titre de la page est affiché dynamiquement : "Ajouter une playlist" ou "Modifier une playlist".<br>
Le formulaire contient deux champs :<br>
•	Nom (obligatoire).<br>
•	Description (facultatif).<br>
Lors d'une modification, les champs sont préremplis avec les données existantes. La liste des formations rattachées à la playlist est affichée en dessous du formulaire, avec un bouton "Modifier" pour accéder directement à la modification de chaque formation (il n'est pas possible d'ajouter ou de retirer une formation depuis ce formulaire). Un bouton "Enregistrer" valide le formulaire et un bouton "Retour" permet d'annuler.<br>

### Page 6 : gestion des catégories
Cette page est accessible via le menu "Catégories" du back office.<br>
La partie haute contient un mini formulaire permettant d'ajouter directement une nouvelle catégorie : un champ "Nom de la catégorie" et un bouton "Ajouter". Le nom saisi doit être unique.<br>
La partie centrale présente la liste de toutes les catégories dans un tableau à deux colonnes : "Catégorie" et "Formations liées" (nombre de formations associées à chaque catégorie).<br>
Pour chaque catégorie, un bouton "Supprimer" est disponible. La suppression n'est possible que si aucune formation n'est rattachée à la catégorie (contrôle bloqué côté serveur).<br>

## Sécurité
Les mesures de sécurité suivantes ont été mises en place :<br>
•	Accès au back office protégé par authentification (rôle ROLE_ADMIN requis, configuré dans security.yaml).<br>
•	Mots de passe stockés sous forme de hash dans la base de données.<br>
•	Protection CSRF : un jeton est envoyé depuis la vue et vérifié par le contrôleur pour toutes les actions sensibles (filtres, ajout, modification, suppression).<br>
•	Requêtes paramétrées via Doctrine ORM pour prévenir les injections SQL.<br>
•	Filtrage global de toutes les entrées utilisateur (GET, POST, PUT, PATCH, DELETE) via la classe InputSanitizerSubscriber.<br>
•	Validation des saisies dans les formulaires (contraintes Symfony : NotBlank, Length, date non future…).<br>

## Tests automatisés
L'application est couverte par une suite de tests PHPUnit (26 tests, 53 assertions) organisés en quatre catégories :<br>
•	**Tests unitaires** : contrôle de la méthode retournant la date de parution d'une formation au format string.<br>
•	**Tests d'intégration sur les règles de validation** : contrôle que la date d'une formation ne peut pas être postérieure à la date du jour.<br>
•	**Tests d'intégration sur les Repository** : contrôle de toutes les méthodes ajoutées dans les classes Repository (front et back office), sur une base de données de test dédiée avec gestion par transactions.<br>
•	**Tests fonctionnels** : contrôle de l'accessibilité de la page d'accueil, des tris, des filtres et de la navigation par lien/bouton dans toutes les pages contenant des listes.<br>

## Documentation technique
Une documentation technique générée avec phpDoc est disponible dans le dossier `docs/` à la racine du projet. Elle couvre l'ensemble des classes du front office, du back office et des fichiers de tests (contrôleurs, entités, repositories, formulaires, abonné d'événement).<br>

## Test de l'application en local
### Prérequis
Vérifier que les outils suivants sont installés : PHP 8.1+, Composer, Git, WampServer, MySQL.

### Installation
- Cloner ce dépôt ou télécharger et dézipper le code dans le dossier `www` de WampServer, dans un sous-dossier nommé `mediatekformation`.<br>
- Ouvrir une fenêtre de commandes en mode admin, se positionner dans le dossier du projet et taper `composer install` pour reconstituer le dossier `vendor`.<br>
- Dans phpMyAdmin, créer la base de données `mediatekformation`.<br>
- Récupérer le fichier `mediatekformation.sql` à la racine du projet et l'utiliser pour remplir la base de données.<br>
- Vérifier le fichier `.env` à la racine du projet et ajuster la ligne `DATABASE_URL` si nécessaire (par défaut : connexion root sans mot de passe).<br>
- Exécuter les migrations Doctrine pour créer la table `user` dans la base de données :<br>
`php bin/console doctrine:migrations:migrate`<br>
- Créer un compte administrateur pour accéder au back office :<br>
  - Générer un hash du mot de passe souhaité avec la commande :<br>
`php bin/console security:hash-password`<br>
  - Insérer l'utilisateur directement dans la table `user` de la base de données avec l'identifiant, le hash obtenu et le rôle `["ROLE_ADMIN"]`.<br>
- L'adresse pour lancer l'application est : `http://localhost/mediatekformation/public/`<br>
- Le back office est accessible à l'adresse : `http://localhost/mediatekformation/public/admin`<br>

### Exécution des tests
- Dans phpMyAdmin, créer une base de données de test nommée `mediatekformation_test`.<br>
- Vérifier le fichier `.env.test` et ajuster la ligne `DATABASE_URL` si nécessaire (par défaut : connexion root sans mot de passe sur la base `mediatekformation_test`).<br>
- Appliquer les migrations sur la base de test :<br>
`php bin/console doctrine:migrations:migrate --env=test`<br>
- Lancer les tests :<br>
`php bin/phpunit`<br>

## Tester l'application en ligne
L'application est déployée et accessible en ligne à l'adresse suivante : **[https://mediatek-formation-acfue8fvf7avfkcs.francecentral-01.azurewebsites.net/]**<br>
Le back office est accessible en ajoutant `/admin` à cette adresse.<br>