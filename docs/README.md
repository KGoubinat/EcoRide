# Nom du projet

Description courte et concise de ton projet. Par exemple : 
"Application de covoiturage permettant aux utilisateurs de publier et de réserver des trajets."

---

## Table des matières

1. [Introduction](#introduction)
2. [Technologies utilisées](#technologies-utilisées)
3. [Installation](#installation)
4. [Utilisation](#utilisation)
5. [Structure du projet](#structure-du-projet)
6. [Base de données](#base-de-données)
7. [Contribuer](#contribuer)
8. [Licence](#licence)

---

## Introduction

L’application de covoiturage développée permet aux utilisateurs de partager des trajets en voiture afin de faciliter leurs déplacements tout en réduisant les coûts et l’empreinte écologique. Elle offre une solution de transport économique et pratique en mettant en relation conducteurs et passagers.

Les utilisateurs ont la possibilité de créer un compte, se connecter et proposer des trajets, en renseignant des informations essentielles telles que le lieu de départ, la destination, la date et l'heure de départ, ainsi que le nombre de places disponibles dans leur véhicule. Les passagers, de leur côté, peuvent rechercher des trajets en fonction de leur destination et réserver une place. Un système de messagerie intégré permet également aux utilisateurs d’échanger des informations et de confirmer les détails des trajets.

Le développement de l'application repose sur les technologies HTML, CSS et PHP pour le frontend et le backend, tandis que MySQL est utilisé pour la gestion des données. Le projet est déployé localement sur un serveur XAMPP, permettant une configuration rapide et simple pendant la phase de développement.

L'objectif principal de ce projet est d’encourager le covoiturage afin de rendre les déplacements plus accessibles et écologiques. L’application est conçue pour être responsive, offrant ainsi une expérience fluide sur tous types d'appareils.


---

## Technologies utilisées

Liste des technologies que tu as utilisées pour développer ce projet. Par exemple :

- **HTML** : Structure des pages web.
- **CSS** : Mise en page et design des pages.
- **PHP** : Traitement côté serveur pour la gestion des utilisateurs, des trajets et des réservations.
- **MySQL** : Base de données pour stocker les utilisateurs, les trajets, et les réservations.
- **XAMPP** : Environnement de développement local avec serveur Apache et base de données MySQL.

---

## Installation


1. **Cloner le dépôt** :
    ```bash
    git clone https://lien-vers-ton-depot.git
    ```

2. **Installer XAMPP** et démarrer les serveurs Apache et MySQL.

3. **Créer la base de données** :
   - Ouvre phpMyAdmin via `http://localhost/phpmyadmin/`.
   - Création  d'une nouvelle base de données, `Ecoride`.

4. **Configurer les fichiers PHP** :
   - Modifie le fichier `includes/db.php` pour y mettre tes paramètres de connexion à la base de données si nécessaire (si tu utilises des identifiants différents de ceux par défaut de XAMPP).


---

## Utilisation


1. Ouvre le fichier `index.php` dans ton navigateur.
2. Crée un compte utilisateur ou connecte-toi avec un compte existant.
3. Publie un trajet en renseignant les informations de départ et d'arrivée.
4. Recherche et réserve un trajet disponible.

---


