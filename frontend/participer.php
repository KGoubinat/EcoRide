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

   
    // Récupérer l'URL de la base de données depuis la variable d'environnement JAWSDB_URL
$databaseUrl = getenv('JAWSDB_URL');

// Utiliser une expression régulière pour extraire les éléments nécessaires de l'URL
$parsedUrl = parse_url($databaseUrl);

// Définir les variables pour la connexion à la base de données
$servername = $parsedUrl['host'];  // Hôte MySQL
$username = $parsedUrl['user'];  // Nom d'utilisateur MySQL
$password = $parsedUrl['pass'];  // Mot de passe MySQL
$dbname = ltrim($parsedUrl['path'], '/');  // Nom de la base de données (en enlevant le premier "/")

// Connexion à la base de données avec PDO
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

    // Récupérer l'ID utilisateur à partir de l'email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$user_email]);
    $user = $stmt->fetch();

    // Vérifier si l'utilisateur existe
    if (!$user) {
        echo json_encode(["success" => false, "message" => "Utilisateur non trouvé."]);
        exit;
    }
    $user_id = $user['id'];

    // Vérifier si le covoiturage existe et obtenir les informations
    $stmt = $conn->prepare("SELECT places_restantes, prix, passagers FROM covoiturages WHERE id = ?");
    $stmt->execute([$covoiturage_id]);
    $covoiturage = $stmt->fetch();

    if (!$covoiturage) {
        echo json_encode(["success" => false, "message" => "Covoiturage non trouvé."]);
        exit;
    }

    // Vérifier les crédits de l'utilisateur
    $stmtCredits = $conn->prepare("SELECT credits FROM users WHERE id = ?");
    $stmtCredits->execute([$user_id]);
    $userCredits = $stmtCredits->fetch();

    if (!$userCredits) {
        echo json_encode(["success" => false, "message" => "Crédits non trouvés pour cet utilisateur."]);
        exit;
    }

    // Vérifier si l'utilisateur a suffisamment de crédits et s'il y a des places disponibles
    if ($userCredits['credits'] < $covoiturage['prix'] * $passengers) {
        echo json_encode(["success" => false, "message" => "Crédits insuffisants pour effectuer la réservation."]);
        exit;
    }

    if ($covoiturage['places_restantes'] < $passengers) {
        echo json_encode(["success" => false, "message" => "Pas assez de places disponibles."]);
        exit;
    }

    // Si les conditions sont remplies, on lance la transaction
    try {
        $conn->beginTransaction();

        // Insérer la réservation dans la table 'reservations'
        $stmtReservation = $conn->prepare("INSERT INTO reservations (user_id, covoiturage_id, statut, places_reservees) VALUES (?, ?, 'en attente', ?)");
        $stmtReservation->execute([$user_id, $covoiturage_id, $passengers]);

        // Mettre à jour le nombre de places restantes
        $stmtUpdate = $conn->prepare("UPDATE covoiturages SET places_restantes = places_restantes - ? WHERE id = ?");
        $stmtUpdate->execute([$passengers, $covoiturage_id]);

        // Mettre à jour le nombre total de passagers
        $stmtUpdatePassagers = $conn->prepare("UPDATE covoiturages SET passagers = passagers + ? WHERE id = ?");
        $stmtUpdatePassagers->execute([$passengers, $covoiturage_id]);

        // Déduire les crédits de l'utilisateur
        $stmtDeductCredits = $conn->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
        $stmtDeductCredits->execute([$covoiturage['prix'] * $passengers, $user_id]);

        // Commit la transaction
        $conn->commit();

        echo json_encode(["success" => true, "message" => "Réservation effectuée avec succès."]);

    } catch (Exception $e) {
        // Si une erreur survient, annuler la transaction
        $conn->rollBack();
        echo json_encode(["success" => false, "message" => "Erreur lors de la réservation : " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Données manquantes."]);
}
?>
