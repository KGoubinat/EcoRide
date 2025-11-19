-- =========================================
--   Base de données EcoRide - Structure
--   Auteur : Kévin Goubinat
--   TP DWWM - 2025
-- =========================================

CREATE DATABASE IF NOT EXISTS ecoride CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ecoride;

-- =========================================
-- Table : users
-- =========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstName VARCHAR(255) NOT NULL,
    lastName VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    credits INT DEFAULT 0,
    status ENUM('passager','chauffeur','passager_chauffeur') DEFAULT 'passager',
    plate_number VARCHAR(20),
    first_registration_date DATE,
    smoker_preference ENUM('oui','non') DEFAULT 'non',
    pet_preference ENUM('oui','non') DEFAULT 'non',
    role ENUM('utilisateur','employe','administrateur') DEFAULT 'utilisateur',
    etat ENUM('active','suspended') DEFAULT 'active',
    photo VARCHAR(255)
);

-- =========================================
-- Table : chauffeur_info
-- =========================================
CREATE TABLE chauffeur_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plaque_immatriculation VARCHAR(20),
    date_1ere_immat DATE,
    modele VARCHAR(100),
    marque VARCHAR(50),
    nb_places_disponibles INT,
    preferences TEXT,
    smoker_preference TINYINT(1) DEFAULT 0,
    pet_preference TINYINT(1) DEFAULT 0,
    energie VARCHAR(20) DEFAULT 'Essence',
    firstName VARCHAR(255) NOT NULL,
    lastName VARCHAR(255) NOT NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================================
-- Table : villes
-- =========================================
CREATE TABLE villes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL
);

-- =========================================
-- Table : covoiturages
-- =========================================
CREATE TABLE covoiturages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    conducteur VARCHAR(255) NOT NULL,
    note DECIMAL(2,1) DEFAULT NULL,
    depart VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    places_restantes INT NOT NULL,
    passagers INT NOT NULL,
    ecologique TINYINT(1) NOT NULL,
    photo VARCHAR(255),
    duree TIME,
    heure_depart TIME,
    heure_arrivee TIME,
    modele_voiture VARCHAR(100),
    marque_voiture VARCHAR(100),
    energie_voiture VARCHAR(50),
    vehicule_id INT NOT NULL,
    nb_places_disponibles INT,
    statut VARCHAR(255) DEFAULT 'en attente',

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================================
-- Table : reservations
-- =========================================
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    covoiturage_id INT NOT NULL,
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut VARCHAR(255) DEFAULT 'en attente',
    depart VARCHAR(255),
    destination VARCHAR(255),
    heure_depart TIME,
    date_traject DATE,
    places_reservees INT DEFAULT 1,

    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturages(id) ON DELETE CASCADE
);

-- =========================================
-- Table : avis_conducteurs
-- =========================================
CREATE TABLE avis_conducteurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conducteur_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    note TINYINT(4),
    commentaire TEXT,
    date_avis DATETIME DEFAULT CURRENT_TIMESTAMP,
    utilisateur_email VARCHAR(255) NOT NULL,
    ride_id INT,

    UNIQUE(utilisateur_id, conducteur_id, commentaire(191)),
    FOREIGN KEY (conducteur_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================================
-- Table : reviews
-- =========================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    driver_id INT,
    rating INT,
    comment TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    moderated_by INT,
    moderated_at DATETIME
);

-- =========================================
-- Table : troublesome_rides
-- =========================================
CREATE TABLE troublesome_rides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    user_id INT NOT NULL,
    driver_id INT NOT NULL,
    comment TEXT,
    status ENUM('en attente','résolu') DEFAULT 'en attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(ride_id, user_id),
    FOREIGN KEY (ride_id) REFERENCES covoiturages(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (driver_id) REFERENCES users(id)
);

-- =========================================
-- Table : users_credit
-- =========================================
CREATE TABLE users_credit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type_transaction ENUM('ajout','retrait') NOT NULL,
    date_transaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description VARCHAR(255),
    credit INT DEFAULT 0,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================================
-- Table : validation_tokens
-- =========================================
CREATE TABLE validation_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    user_id INT NOT NULL,
    token CHAR(64) NOT NULL UNIQUE,
    expiration DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,

    UNIQUE(ride_id, user_id),
    FOREIGN KEY (ride_id) REFERENCES covoiturages(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
