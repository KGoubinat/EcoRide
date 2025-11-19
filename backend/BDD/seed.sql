USE ecoride;



-- ================================
-- 1. UTILISATEURS
-- Password : Motdepasse123!
-- ================================
INSERT INTO users 
(firstName, lastName, email, password, credits, status, plate_number, first_registration_date, 
 smoker_preference, pet_preference, role, etat, photo)
VALUES
('Marie', 'Dupont', 'marie.dupont@gmail.com',
 '$2y$10$611rlysI.Awryez4541zCOjAgZ5tZl8hY50.MiG9vH.21S6DYyJzS',
 120, 'passager', NULL, NULL, 'non', 'non', 'utilisateur', 'active', NULL),

('Thomas', 'Leroy', 'thomas.leroy@gmail.com',
 '$2y$10$611rlysI.Awryez4541zCOjAgZ5tZl8hY50.MiG9vH.21S6DYyJzS',
 80, 'chauffeur', NULL, NULL, 'non', 'oui', 'utilisateur', 'active', NULL),

('Sophie', 'Martin', 'sophie.martin@gmail.com',
 '$2y$10$611rlysI.Awryez4541zCOjAgZ5tZl8hY50.MiG9vH.21S6DYyJzS',
 200, 'passager_chauffeur', NULL, NULL, 'oui', 'non', 'utilisateur', 'active', NULL),

('Admin', 'EcoRide', 'admin@gmail.com',
 '$2y$10$611rlysI.Awryez4541zCOjAgZ5tZl8hY50.MiG9vH.21S6DYyJzS',
 0, 'passager', NULL, NULL, 'non', 'non', 'administrateur', 'active', NULL),

('Lucas', 'Morel', 'lucas.morel@gmail.com',
 '$2y$10$611rlysI.Awryez4541zCOjAgZ5tZl8hY50.MiG9vH.21S6DYyJzS',
 30, 'passager', NULL, NULL, 'non', 'oui', 'employe', 'active', NULL);

-- ================================
-- 2. VILLES
-- ================================
INSERT INTO villes (nom) VALUES
('Paris'), ('Lyon'), ('Marseille'), ('Toulouse'), ('Bordeaux'),
('Nice'), ('Lille'), ('Nantes'), ('Rennes'), ('Grenoble');

-- ================================
-- 3. CHAUFFEUR INFO
-- ================================
INSERT INTO chauffeur_info
(user_id, plaque_immatriculation, date_1ere_immat, modele, marque, nb_places_disponibles,
 preferences, smoker_preference, pet_preference, energie, firstName, lastName)
VALUES
(2, 'AB-321-CD', '2019-04-12', 'Clio', 'Renault', 3,
 'Musique calme', 0, 1, 'Essence', 'Thomas', 'Leroy'),

(3, 'GH-876-FR', '2021-07-22', '308', 'Peugeot', 4,
 'AC, pas d’animaux', 1, 0, 'Diesel', 'Sophie', 'Martin');

-- ================================
-- 4. TRAJETS
-- ================================
INSERT INTO covoiturages 
(user_id, conducteur, note, depart, destination, date, prix, places_restantes, passagers,
 ecologique, photo, duree, heure_depart, heure_arrivee, modele_voiture, marque_voiture,
 energie_voiture, vehicule_id, nb_places_disponibles, statut)
VALUES
(2, 'Thomas Leroy', 4.6, 'Paris', 'Lyon', '2025-04-10', 25.00,
 2, 1, 1, 'default.jpg', '05:00:00', '08:00:00', '13:00:00',
 'Clio', 'Renault', 'Essence', 1, 3, 'en attente'),

(3, 'Sophie Martin', 4.8, 'Marseille', 'Nice', '2025-04-15', 15.00,
 3, 0, 0, 'default.jpg', '02:15:00', '09:00:00', '11:15:00',
 '308', 'Peugeot', 'Diesel', 2, 4, 'en attente'),

(3, 'Sophie Martin', 5.0, 'Toulouse', 'Bordeaux', '2025-04-20', 20.00,
 4, 0, 1, 'default.jpg', '02:30:00', '10:00:00', '12:30:00',
 '308', 'Peugeot', 'Diesel', 2, 4, 'en attente');

-- ================================
-- 5. RÉSERVATIONS
-- ================================
INSERT INTO reservations
(user_id, covoiturage_id, statut, depart, destination, heure_depart, date_traject, places_reservees)
VALUES
(1, 1, 'terminé', 'Paris', 'Lyon', '08:00:00', '2025-04-10', 1),
(5, 1, 'en attente', 'Paris', 'Lyon', '08:00:00', '2025-04-10', 1),
(1, 2, 'terminé', 'Marseille', 'Nice', '09:00:00', '2025-04-15', 1);

-- ================================
-- 6. AVIS CHAUFFEURS
-- ================================
INSERT INTO avis_conducteurs
(conducteur_id, utilisateur_id, note, commentaire, utilisateur_email, ride_id)
VALUES
(2, 1, 5, 'Très bon conducteur, ponctuel.', 'marie.dupont@gmail.com', 1),
(3, 1, 4, 'Trajet agréable et voiture propre.', 'marie.dupont@gmail.com', 2);

-- ================================
-- 7. REVIEWS
-- ================================
INSERT INTO reviews (user_id, driver_id, rating, comment, status)
VALUES
(1, 2, 5, 'Excellent trajet, je recommande.', 'approved'),
(5, 3, 4, 'Bonne ambiance, chauffeur sympa.', 'approved');

-- ================================
-- 8. INCIDENTS
-- ================================
INSERT INTO troublesome_rides (ride_id, user_id, driver_id, comment)
VALUES (1, 5, 2, 'Le conducteur avait 10 minutes de retard.');

-- ================================
-- 9. CREDIT
-- ================================
INSERT INTO users_credit (user_id, type_transaction, description, credit)
VALUES
(1, 'ajout', 'Réservation terminée avec succès', 10),
(2, 'retrait', 'Paiement trajet Paris → Lyon', -25);

-- ================================
-- 10. VALIDATION TOKENS
-- ================================
INSERT INTO validation_tokens (ride_id, user_id, token, expiration)
VALUES
(1, 1, SHA2('token1', 256), DATE_ADD(NOW(), INTERVAL 3 DAY)),
(2, 5, SHA2('token2', 256), DATE_ADD(NOW(), INTERVAL 3 DAY));
