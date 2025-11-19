# EcoRide

**Application de covoiturage** permettant aux utilisateurs de publier et de réserver des trajets facilement. L’objectif est de faciliter les déplacements tout en réduisant les coûts et l’empreinte écologique.

🌍 [Voir la démo en ligne](https://ecoride-covoiturage-app-fe35411c6ec7.herokuapp.com/)

---

## Table des matières

1. [Introduction](#introduction)
2. [Aperçu](#aperçu)
3. [Technologies utilisées](#technologies-utilisées)
4. [Installation](#installation)
5. [Utilisation](#utilisation)
6. [Structure du projet](#structure-du-projet)
7. [Base de données](#base-de-données)
8. [Contribuer](#contribuer)
9. [Licence](#licence)

---

## Introduction

EcoRide est une application web responsive de covoiturage. Elle permet aux utilisateurs :
- de s’inscrire et se connecter,
- de publier des trajets (départ, destination, date, heure, places disponibles),
- de rechercher des trajets et réserver une place,
- d’échanger via un système de messagerie intégré.

Le projet est développé en PHP pour le backend, HTML/CSS pour l'interface utilisateur, et MySQL pour la base de données. Il est déployé sur **Heroku** pour offrir un accès en ligne.

---

## Aperçu


![Aperçu de l'application](./docs/screenshot.png)

---

## Technologies utilisées

- **HTML/CSS** : Structure et design des pages.
- **PHP** : Logique métier et traitement des données.
- **MySQL** : Stockage des utilisateurs, trajets, réservations.
- **Docker** : Conteneurisation de l'environnement.
- **XAMPP** : Développement local.
- **Heroku** : Déploiement en ligne.

---

## Installation

1. Cloner le dépôt
git clone https://github.com/ton-utilisateur/ecoride.git
cd ecoride

2. Installer XAMPP et démarrer Apache + MySQL

3. Créer la base de données
Accède à http://localhost/phpmyadmin

Crée une nouvelle base appelée Ecoride

4. Importer la structure de la base
Utilise le fichier docs/ecoride.sql 

5. Configurer la connexion DB
Modifie includes/db.php si tes identifiants MySQL diffèrent :

php
$host = 'localhost';
$db = 'Ecoride';
$user = 'root';
$pass = '';
Utilisation
Accède à http://localhost/ecoride/index.php ou le site Heroku

Inscris-toi ou connecte-toi.

Publie ou recherche un trajet.

Réserve une place et échange avec les conducteurs.

Structure du projet

Ecoride/
├── backend/
├── cloudinary_php-master/
├── docs/
│   └── ecoride.sql
├── frontend/
├── node_modules/
├── src/
├── vendor/
├── index.php
├── .env
├── docker-compose.yml
├── package.json
├── composer.json
├── tailwind.config.js
└── test.js

