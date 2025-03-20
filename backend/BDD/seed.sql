USE covoiturage_db;

-- Insérer des utilisateurs
INSERT INTO users (firstName, lastName, email, password, credits, status, plate_number, first_registration_date, smoker_preference, pet_preference, role, etat, photo)
VALUES
('Alice', 'Dupont', 'alice@example.com', 'hashed_password1', 50.00, 'chauffeur', 'AB-123-CD', '2020-01-15', TRUE, FALSE, 'utilisateur', 'active', NULL),
('Bob', 'Martin', 'bob@example.com', 'hashed_password2', 20.00, 'passager', NULL, NULL, FALSE, TRUE, 'utilisateur', 'active', NULL),
('Charlie', 'Durand', 'charlie@example.com', 'hashed_password3', 75.00, 'passager_chauffeur', 'XY-456-ZT', '2018-06-30', NULL, NULL, 'administrateur', 'active', NULL);

-- Insérer des villes
INSERT INTO villes (nom) VALUES
('Paris'), ('Lyon'), ('Marseille'), ('Toulouse'), ('Bordeaux');

-- Insérer des covoiturages
INSERT INTO covoiturages (user_id, conducteur, note, depart, destination, date, prix, places_restantes, passagers, nb_places_disponibles, ecologique, duree, heure_depart, heure_arrivee, modele_voiture, marque_voiture, energie_voiture, statut)
VALUES
(1, 'Alice Dupont', 4.8, 'Paris', 'Lyon', '2025-03-25', 30.00, 2, 1, 3, TRUE, '04:30:00', '08:00:00', '12:30:00', 'Model X', 'Tesla', 'Électrique', 'ouvert'),
(3, 'Charlie Durand', 4.5, 'Marseille', 'Bordeaux', '2025-04-10', 50.00, 3, 1, 4, FALSE, '06:00:00', '07:00:00', '13:00:00', 'Golf', 'Volkswagen', 'Diesel', 'ouvert');

-- Insérer des réservations
INSERT INTO reservations (user_id, covoiturage_id, statut, depart, destination, heure_depart, date_traject, places_reservees)
VALUES
(2, 1, 'confirmé', 'Paris', 'Lyon', '08:00:00', '2025-03-25', 1),
(2, 2, 'en attente', 'Marseille', 'Bordeaux', '07:00:00', '2025-04-10', 1);

-- Insérer des avis (reviews)
INSERT INTO reviews (user_id, driver_id, rating, comment, status, created_at)
VALUES
(2, 1, 5.0, 'Super conducteur, très ponctuel !', 'approved', NOW()),
(3, 1, 4.2, 'Bonne expérience, voiture propre.', 'approved', NOW());

-- Insérer des avis conducteurs
INSERT INTO avis_conducteurs (conducteur_id, utilisateur_id, note, commentaire, date_avis, utilisateur_email, ride_id)
VALUES
(1, 2, 5.0, 'Très bonne conduite et ponctuel.', NOW(), 'bob@example.com', 1),
(3, 2, 4.0, 'Conducteur agréable et sympathique.', NOW(), 'bob@example.com', 2);

-- Insérer des informations sur les chauffeurs
INSERT INTO chauffeur_info (user_id, plaque_immatriculation, date_1ere_immat, modele, marque, nb_places_disponibles, preferences, smoker_preference, pet_preference, energie, firstName, lastName)
VALUES
(1, 'AB-123-CD', '2020-01-15', 'Model X', 'Tesla', 3, 'Climatisation, WiFi', TRUE, FALSE, 'Électrique', 'Alice', 'Dupont'),
(3, 'XY-456-ZT', '2018-06-30', 'Golf', 'Volkswagen', 4, 'Climatisation', NULL, NULL, 'Diesel', 'Charlie', 'Durand');

-- Insérer des incidents (troublesome_rides)
INSERT INTO troublesome_rides (ride_id, user_id, driver_id, comment, status, created_at)
VALUES
(1, 2, 1, 'Conducteur en retard de 30 minutes.', 'en attente', NOW());

-- Insérer des transactions de crédit
INSERT INTO users_credit (user_id, type_transaction, date_transaction, description, credit)
VALUES
(1, 'ajout', NOW(), 'Récompense pour covoiturage', 10.00),
(2, 'retrait', NOW(), 'Paiement du covoiturage', -30.00);

-- Insérer des tokens de validation
INSERT INTO validation_tokens (user_id, ride_id, token, expiration, created_at)
VALUES
(2, 1, 'TOKEN123456', DATE_ADD(NOW(), INTERVAL 2 DAY), NOW());
