
README pour le Repo Recherche Créneaux

# Description du projet

Ce projet vise à développer un outil pour la recherche de créneaux automatique dans un planning.

Le but est de fournir une interface simple et intuitive pour rechercher des créneaux disponibles en fonction de différents critères, tels que les agendas par utilisateur, la date, l'heure, la durée

## Principe de fonctionnement

Le projet se base sur la récupération des évenements FREE/BUSY depuis l'api d'un calendrier (kronoligh/google/...).

* Pour trouver les créneaux, il y'a tout d'abord une génération des créneaux avec les paramètres demandés (jours, durée...) grâce à la
librairie composer : rlanvin/php-rrule
* Les créneaux busys sont récupérés sur l'api iCalendar FREE/BUSYS puis normalisés cad formattés aux creneaux générés pour préparer la comparaison
* Les créneaux sont par la suite comparés avec les créneaux busys récupérés, class FBCompare


### Workflow

+ Génération des creneaux demandés : FBCreneauxGeneres
+ Récupération des busys Utilisateurs: FBUser
+ Normalisation des créneaux busys : FBUser
+ Comparaison entre créneaux générés / creneaux Busys : FBCompare


# Fonctionnalités

+ Recherche de créneaux par date, heure, durée
+ Affichage des créneaux disponibles sous forme de liste
+ Possibilité d'envoyer une invitation aux participants réserver des créneaux
+ Création Evento : crée automatiquement un sondage listant plusieurs créneaux par appels ajax sur l'application evento de chez Renater


# Technologies utilisées

+ PHP/Composer
+ JS

# Librairies / Dépendance

## iCalendar Free/Busy Component

Utilisation des composants api

## Wsgroups

+ Pour la séléction des utilisateurs, le projet fait appel à une api : [UnivParis1/wsgroups](https://github.com/UnivParis1/wsgroups)

+ l'api qui renvoie les identifiants des utilisateurs de type uid/displayname

+ Cette api est centrale à l'application mais elle peut être configurée en option dans .env -> WSGROUP : false

+ Si il n'y a pas d'appel à wsgroup, l'application est limitée fonctionnellement

Dans le cadre du développement du projet, l'appel se fait sur un agenda kronolith
avec le paramètre du fichier .env : URL_FREEBUSY

## Evento

Possibilité de créer un sondage sur plusieurs créneaux

+ Projet Moment de chez Renater: [https://sourcesup.renater.fr/projects/moment/](https://sourcesup.renater.fr/projects/moment/) 

# Quickstart

La configuration se fait en créant un fichier .env sur la base du fichier .env.example

# Configuration

## Fichier .env

+ Fichier .env : mis à la racine du projet, variables de configuration

### Variables indispensables

+ URL_FREEBUSY : url api récupération du calendrier.

### Répertoires hors git

Les répertoires vendor et node_modules sont hors git il faut les synchroniser sur les instances de test/prod :

+ vendor/
+ node_modules/

# Tests Phpunit

Des tests phpunit pour éviter des régréssions sont faits.

Pour le moment, la classe testée est FBCompare

Ceux-ci se trouvent dans le répertoire tests/FBCompareTest-ID/

Une variable d'environnement doit être initialisée pour choisir le numéro du test

+ Exemple commande pour choisir le test 1:
```FBCompareTestId=1 vendor/bin/phpunit tests/FBCompareTest.php```

Les données de test sont sous forme de json, ce sont des données serialisées, il y'a 3 fichiers qui correspondent aux classes instanciées:
+ stdenv.json : stdClass stdEnv
+ fbparams.json : class FBParams
+ fbform.json : class FBForm


Ce projet est sous licence Apache 2.
