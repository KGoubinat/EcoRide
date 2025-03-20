<?php

include('db.php');  // Inclusion de la connexion à la base de données

// Fonction Create : Créer un avis sur un conducteur 
function createAvisConducteur($conducteur_id, $utilisateur_id, $note, $commentaire, $ride_id, $utilisateur_email) {
    global $conn;

    $sql = "INSERT INTO avis_conducteurs (conducteur_id, utilisateur_id, note, commentaire, ride_id, utilisateur_email) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$conducteur_id, $utilisateur_id, $note, $commentaire, $ride_id, $utilisateur_email]);
}

// Fonction Read : Lire un avis spécifique
function getAvisConducteur($id) {
    global $conn;

    $sql = "SELECT * FROM avis_conducteurs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction update : Mettre a jour un avis 
function updateAvisConducteur($id, $note, $commentaire) {
    global $conn;

    $sql = "UPDATE avis_conducteurs SET note = ?, commentaire = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$note, $commentaire, $id]);
}

// Fonction delete : Supprimer un avis
function deleteAvisConducteur($id) {
    global $conn;

    $sql = "DELETE FROM avis_conducteurs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

//fonction create : Ajouter un crédit à un utilisateur 
function addCredit($user_id, $credit_amount, $description) {
    global $conn;

    $sql = "INSERT INTO users_credit (user_id, type_transaction, credit, description) 
            VALUES (?, 'ajout', ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $credit_amount, $description]);
}

//fonction create : Retirer un crédit d'un utilisateur
function withdrawCredit($user_id, $credit_amount, $description) {
    global $conn;

    $sql = "INSERT INTO users_credit (user_id, type_transaction, credit, description) 
            VALUES (?, 'retrait', ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $credit_amount, $description]);
}

// fonction create : Créer un covoiturage 
function createCovoiturage($user_id, $conducteur, $note, $depart, $destination, $date, $prix, $places_restantes, $passagers, $ecologique, $photo, $duree, $heure_depart, $heure_arrivee, $modele_voiture, $marque_voiture, $energie_voiture, $vehicule_id, $nb_places_disponibles, $statut) {
    global $conn;

    $sql = "INSERT INTO covoiturages (user_id, conducteur, note, depart, destination, date, prix, places_restantes, passagers, ecologique, photo, duree, heure_depart, heure_arrivee, modele_voiture, marque_voiture, energie_voiture, vehicule_id, nb_places_disponibles, statut) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $conducteur, $note, $depart, $destination, $date, $prix, $places_restantes, $passagers, $ecologique, $photo, $duree, $heure_depart, $heure_arrivee, $modele_voiture, $marque_voiture, $energie_voiture, $vehicule_id, $nb_places_disponibles, $statut]);
}

// fonction read : Lire un covoiturage 
function getCovoiturage($id) {
    global $conn;

    $sql = "SELECT * FROM covoiturages WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// fonction update : Mettre à jour un covoiturage
function updateCovoiturage($id, $conducteur, $note, $depart, $destination, $date, $prix, $places_restantes, $passagers, $ecologique, $photo, $duree, $heure_depart, $heure_arrivee, $modele_voiture, $marque_voiture, $energie_voiture, $vehicule_id, $nb_places_disponibles, $statut) {
    global $conn;

    $sql = "UPDATE covoiturages 
            SET conducteur = ?, note = ?, depart = ?, destination = ?, date = ?, prix = ?, places_restantes = ?, passagers = ?, ecologique = ?, photo = ?, duree = ?, heure_depart = ?, heure_arrivee = ?, modele_voiture = ?, marque_voiture = ?, energie_voiture = ?, vehicule_id = ?, nb_places_disponibles = ?, statut = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$conducteur, $note, $depart, $destination, $date, $prix, $places_restantes, $passagers, $ecologique, $photo, $duree, $heure_depart, $heure_arrivee, $modele_voiture, $marque_voiture, $energie_voiture, $vehicule_id, $nb_places_disponibles, $statut, $id]);
}

// fonction delete : Supprimer un covoiturage
function deleteCovoiturage($id) {
    global $conn;

    $sql = "DELETE FROM covoiturages WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// fonction create : Ajouter une revue 
function addReview($user_id, $driver_id, $rating, $comment, $status) {
    global $conn;

    $sql = "INSERT INTO reviews (user_id, driver_id, rating, comment, status) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $driver_id, $rating, $comment, $status]);
}

// fonction read : Lire une revue
function getReview($id) {
    global $conn;

    $sql = "SELECT * FROM reviews WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// fonction update : Mettre à jour une revue
function updateReview($id, $rating, $comment, $status) {
    global $conn;

    $sql = "UPDATE reviews 
            SET rating = ?, comment = ?, status = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$rating, $comment, $status, $id]);
}

// fonction delete : Supprimer une revue
function deleteReview($id) {
    global $conn;

    $sql = "DELETE FROM reviews WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// fonction create : Creer un utilisateur
function createUser($firstName, $lastName, $email, $password, $credits, $status, $plate_number, $first_registration_date, $smoker_preference, $pet_preference, $role, $etat, $photo) {
    global $conn;

    $sql = "INSERT INTO users (firstName, lastName, email, password, credits, status, plate_number, first_registration_date, smoker_preference, pet_preference, role, etat, photo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$firstName, $lastName, $email, $password, $credits, $status, $plate_number, $first_registration_date, $smoker_preference, $pet_preference, $role, $etat, $photo]);
}

// fonction read : Lire un utilisateur
function getUser($id) {
    global $conn;

    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// fonction update : Mettre à jour un utilisateur
function updateUser($id, $firstName, $lastName, $email, $password, $credits, $status, $plate_number, $first_registration_date, $smoker_preference, $pet_preference, $role, $etat, $photo) {
    global $conn;

    $sql = "UPDATE users 
            SET firstName = ?, lastName = ?, email = ?, password = ?, credits = ?, status = ?, plate_number = ?, first_registration_date = ?, smoker_preference = ?, pet_preference = ?, role = ?, etat = ?, photo = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$firstName, $lastName, $email, $password, $credits, $status, $plate_number, $first_registration_date, $smoker_preference, $pet_preference, $role, $etat, $photo, $id]);
}

// fonction delete : Supprimer un utilisateur
function deleteUser($id) {
    global $conn;

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

// Verifier si l'email existe déjà
function checkIfEmailExists($email) {
    global $conn;

    $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0;  // Retourne true si l'email existe
}

//Mettre à jour le statut de l'utilisateur
function updateUserStatus($id, $etat) {
    global $conn;

    $sql = "UPDATE users 
            SET etat = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$etat, $id]);
}

//creer une reservation
function createReservation($user_id, $covoiturage_id, $statut, $depart, $destination, $heure_depart, $date_traject, $places_reservees) {
    global $conn;

    $sql = "INSERT INTO reservations (user_id, covoiturage_id, statut, depart, destination, heure_depart, date_traject, places_reservees)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $covoiturage_id, $statut, $depart, $destination, $heure_depart, $date_traject, $places_reservees]);
}

//lire une reservation
function getReservation($id) {
    global $conn;

    $sql = "SELECT * FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//mettre à jour une reservation
function updateReservation($id, $statut, $depart, $destination, $heure_depart, $date_traject, $places_reservees) {
    global $conn;

    $sql = "UPDATE reservations 
            SET statut = ?, depart = ?, destination = ?, heure_depart = ?, date_traject = ?, places_reservees = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$statut, $depart, $destination, $heure_depart, $date_traject, $places_reservees, $id]);
}

//supprimer une reservation
function deleteReservation($id) {
    global $conn;

    $sql = "DELETE FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

//creer une ville
function createVille($nom) {
    global $conn;

    $sql = "INSERT INTO villes (nom) VALUES (?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nom]);
}

//lire une ville
function getVille($id) {
    global $conn;

    $sql = "SELECT * FROM villes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//mettre à jour une ville
function updateVille($id, $nom) {
    global $conn;

    $sql = "UPDATE villes SET nom = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$nom, $id]);
}

//supprimer une ville
function deleteVille($id) {
    global $conn;

    $sql = "DELETE FROM villes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

//creer un vehicule
function createVehicule($user_id, $plaque_immatriculation, $modele, $marque, $nb_places_disponibles, $preferences, $smoker_preference, $pet_preference, $energie) {
    global $conn;

    $sql = "INSERT INTO chauffeur_info (user_id, plaque_immatriculation, modele, marque, nb_places_disponibles, preferences, smoker_preference, pet_preference, energie)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id, $plaque_immatriculation, $modele, $marque, $nb_places_disponibles, $preferences, $smoker_preference, $pet_preference, $energie]);
}

//lire un vehicule
function getVehicule($id) {
    global $conn;

    $sql = "SELECT * FROM chauffeur_info WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//mettre à jour un vehicule
function updateVehicule($id, $plaque_immatriculation, $modele, $marque, $nb_places_disponibles, $preferences, $smoker_preference, $pet_preference, $energie) {
    global $conn;

    $sql = "UPDATE chauffeur_info SET plaque_immatriculation = ?, modele = ?, marque = ?, nb_places_disponibles = ?, preferences = ?, smoker_preference = ?, pet_preference = ?, energie = ?
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$plaque_immatriculation, $modele, $marque, $nb_places_disponibles, $preferences, $smoker_preference, $pet_preference, $energie, $id]);
}

//supprimer un vehicule
function deleteVehicule($id) {
    global $conn;

    $sql = "DELETE FROM chauffeur_info WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

?>
