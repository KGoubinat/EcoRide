<?php
session_start();

// Vérifier si les données nécessaires sont présentes dans la requête
if (isset($_POST['id']) && isset($_POST['user_email'])) {
    $covoiturage_id = intval($_POST['id']);
    $user_email = $_POST['user_email'];

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
    $stmt = $pdo->prepare("SELECT places_restantes, prix FROM covoiturages WHERE id = ?");
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
    if ($userCredits['credit'] < $covoiturage['prix']) {
        echo json_encode(["success" => false, "message" => "Crédits insuffisants pour effectuer la réservation."]);
        exit;
    }

    if ($covoiturage['places_restantes'] <= 0) {
        echo json_encode(["success" => false, "message" => "Aucune place disponible."]);
        exit;
    }

    // Si les conditions sont remplies, on lance la transaction
    try {
        $pdo->beginTransaction();

        // Insérer la réservation dans la table 'reservations'
        $stmtReservation = $pdo->prepare("INSERT INTO reservations (user_id, covoiturage_id, statut) VALUES (?, ?, 'en attente')");
        $stmtReservation->execute([$user_id, $covoiturage_id]);

        // Mettre à jour le nombre de places restantes
        $stmtUpdate = $pdo->prepare("UPDATE covoiturages SET places_restantes = places_restantes - 1 WHERE id = ?");
        $stmtUpdate->execute([$covoiturage_id]);

        // Déduire les crédits de l'utilisateur
        $stmtDeductCredits = $pdo->prepare("UPDATE users_credit SET credit = credit - ? WHERE user_id = ?");
        $stmtDeductCredits->execute([$covoiturage['prix'], $user_id]);

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
