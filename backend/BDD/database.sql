-- Création de la base de données
CREATE DATABASE IF NOT EXISTS covoiturage_db;
USE covoiturage_db;

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstName VARCHAR(100) NOT NULL,
    lastName VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    credits DECIMAL(10,2) DEFAULT 0,
    status ENUM('passager', 'chauffeur', 'passager_chauffeur') NOT NULL,
    plate_number VARCHAR(20) UNIQUE,
    first_registration_date DATE,
    smoker_preference BOOLEAN DEFAULT NULL,
    pet_preference BOOLEAN DEFAULT NULL,
    role ENUM('utilisateur', 'employe', 'administrateur') NOT NULL DEFAULT 'utilisateur',
    etat ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    photo VARCHAR(255) DEFAULT NULL
);

-- Table des villes
CREATE TABLE IF NOT EXISTS villes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE
);

-- Table des covoiturages
CREATE TABLE IF NOT EXISTS covoiturages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    conducteur VARCHAR(100) NOT NULL,
    note DECIMAL(3,2) DEFAULT 0,
    depart VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    places_restantes INT NOT NULL,
    passagers INT NOT NULL DEFAULT 0,
    nb_places_disponibles INT NOT NULL,
    ecologique BOOLEAN DEFAULT FALSE,
    photo VARCHAR(255) DEFAULT NULL,
    duree TIME NOT NULL,
    heure_depart TIME NOT NULL,
    heure_arrivee TIME NOT NULL,
    modele_voiture VARCHAR(50),
    marque_voiture VARCHAR(50),
    energie_voiture VARCHAR(50),
    vehicule_id INT,
    statut ENUM('ouvert', 'complet', 'annulé') NOT NULL DEFAULT 'ouvert',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des réservations
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    covoiturage_id INT NOT NULL,
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('confirmé', 'annulé', 'en attente') NOT NULL DEFAULT 'en attente',
    depart VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    heure_depart TIME NOT NULL,
    date_traject DATE NOT NULL,
    places_reservees INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (covoiturage_id) REFERENCES covoiturages(id) ON DELETE CASCADE
);

-- Table des avis (reviews)
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    driver_id INT NOT NULL,
    rating DECIMAL(3,2) NOT NULL CHECK (rating >= 0 AND rating <= 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des avis pour les conducteurs
CREATE TABLE IF NOT EXISTS avis_conducteurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conducteur_id INT NOT NULL,
    utilisateur_id INT NOT NULL,
    note DECIMAL(3,2) NOT NULL CHECK (note >= 0 AND note <= 5),
    commentaire TEXT,
    date_avis TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilisateur_email VARCHAR(150) NOT NULL,
    ride_id INT NOT NULL,
    FOREIGN KEY (conducteur_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (utilisateur_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ride_id) REFERENCES covoiturages(id) ON DELETE CASCADE
);

-- Table des chauffeurs
CREATE TABLE IF NOT EXISTS chauffeur_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plaque_immatriculation VARCHAR(20) UNIQUE NOT NULL,
    date_1ere_immat DATE NOT NULL,
    modele VARCHAR(50) NOT NULL,
    marque VARCHAR(50) NOT NULL,
    nb_places_disponibles INT NOT NULL,
    preferences TEXT,
    smoker_preference BOOLEAN DEFAULT NULL,
    pet_preference BOOLEAN DEFAULT NULL,
    energie VARCHAR(50) NOT NULL,
    firstName VARCHAR(100) NOT NULL,
    lastName VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des incidents
CREATE TABLE IF NOT EXISTS troublesome_rides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ride_id INT NOT NULL,
    user_id INT NOT NULL,
    driver_id INT NOT NULL,
    comment TEXT NOT NULL,
    status ENUM('en attente', 'résolu') DEFAULT 'en attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ride_id) REFERENCES covoiturages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des crédits utilisateurs
CREATE TABLE IF NOT EXISTS users_credit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type_transaction ENUM('ajout', 'retrait') NOT NULL,
    date_transaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT,
    credit DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des tokens de validation
CREATE TABLE IF NOT EXISTS validation_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ride_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expiration TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ride_id) REFERENCES covoiturages(id) ON DELETE CASCADE
);

