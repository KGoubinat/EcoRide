<?php
session_start();

header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Vérifier si les données nécessaires sont présentes dans la requête POST
if (isset($_POST['id']) && isset($_POST['user_email']) && isset($_POST['passengers'])) {
    $covoiturage_id = intval($_POST['id']); // Récupérer l'ID du covoiturage via POST
    $user_email = $_POST['user_email'];     // Récupérer l'email de l'utilisateur via POST
    $passengers = intval($_POST['passengers']); // Récupérer le nombre de passagers via POST

   
    // Connexion à la base de données
    $dsn = 'mysql:host=localhost;dbname=ecoride';
    $username = 'root';
    $password = 'nouveau_mot_de_passe';

    try {
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }

    // Récupérer l'ID utilisateur à partir de l'email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$user_email]);
    $user = $stmt->fetch();

    // Vérifier si l'utilisateur existe
    if (!$user) {
        echo json_encode(["success" => false, "message" => "Utilisateur non trouvé."]);
        exit;
    }
    $user_id = $user['id'];

    // Vérifier si le covoiturage existe et obtenir les informations
    $stmt = $pdo->prepare("SELECT places_restantes, prix, passagers FROM covoiturages WHERE id = ?");
    $stmt->execute([$covoiturage_id]);
    $covoiturage = $stmt->fetch();

    if (!$covoiturage) {
        echo json_encode(["success" => false, "message" => "Covoiturage non trouvé."]);
        exit;
    }

    // Vérifier les crédits de l'utilisateur
    $stmtCredits = $pdo->prepare("SELECT credit FROM users_credit WHERE user_id = ?");
    $stmtCredits->execute([$user_id]);
    $userCredits = $stmtCredits->fetch();

    if (!$userCredits) {
        echo json_encode(["success" => false, "message" => "Crédits non trouvés pour cet utilisateur."]);
        exit;
    }

    // Vérifier si l'utilisateur a suffisamment de crédits et s'il y a des places disponibles
    if ($userCredits['credit'] < $covoiturage['prix'] * $passengers) {
        echo json_encode(["success" => false, "message" => "Crédits insuffisants pour effectuer la réservation."]);
        exit;
    }

    if ($covoiturage['places_restantes'] < $passengers) {
        echo json_encode(["success" => false, "message" => "Pas assez de places disponibles."]);
        exit;
    }

    // Si les conditions sont remplies, on lance la transaction
    try {
        $pdo->beginTransaction();

        // Insérer la réservation dans la table 'reservations'
        $stmtReservation = $pdo->prepare("INSERT INTO reservations (user_id, covoiturage_id, statut, places_reservees) VALUES (?, ?, 'en attente', ?)");
        $stmtReservation->execute([$user_id, $covoiturage_id, $passengers]);

        // Mettre à jour le nombre de places restantes
        $stmtUpdate = $pdo->prepare("UPDATE covoiturages SET places_restantes = places_restantes - ? WHERE id = ?");
        $stmtUpdate->execute([$passengers, $covoiturage_id]);

        // Mettre à jour le nombre total de passagers
        $stmtUpdatePassagers = $pdo->prepare("UPDATE covoiturages SET passagers = passagers + ? WHERE id = ?");
        $stmtUpdatePassagers->execute([$passengers, $covoiturage_id]);

        // Déduire les crédits de l'utilisateur
        $stmtDeductCredits = $pdo->prepare("UPDATE users_credit SET credit = credit - ? WHERE user_id = ?");
        $stmtDeductCredits->execute([$covoiturage['prix'] * $passengers, $user_id]);

        // Commit la transaction
        $pdo->commit();

        echo json_encode(["success" => true, "message" => "Réservation effectuée avec succès."]);

    } catch (Exception $e) {
        // Si une erreur survient, annuler la transaction
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Erreur lors de la réservation : " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Données manquantes."]);
}
?>
