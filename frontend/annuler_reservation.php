<?php
session_start();
ob_start(); // Active le buffer de sortie pour éviter les erreurs avec header()

// Vérifie si un utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo "Aucun utilisateur connecté.";
    exit;
}

// Vérifie que l'ID de la réservation est passé via POST
if (!isset($_POST['reservation_id']) || !is_numeric($_POST['reservation_id'])) {
    echo "Erreur : ID de réservation invalide ou manquant.";
    exit;
}

$reservationId = intval($_POST['reservation_id']); // Sécurise l'entrée

// Connexion à la base de données via JAWSDB_URL
$databaseUrl = getenv('JAWSDB_URL');
$parsedUrl = parse_url($databaseUrl);

$servername = $parsedUrl['host'];
$username = $parsedUrl['user'];
$password = $parsedUrl['pass'];
$dbname = ltrim($parsedUrl['path'], '/');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
    exit;
}

// Récupérer les informations de l'utilisateur connecté
$user_email = $_SESSION['user_email'];
$stmtUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmtUser->execute([$user_email]);
$user = $stmtUser->fetch();

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}

// Récupérer le covoiturage associé à la réservation
$stmtReservation = $conn->prepare("SELECT covoiturage_id, places_reservees FROM reservations WHERE id = :id AND user_id = :user_id");
$stmtReservation->bindParam(':id', $reservationId, PDO::PARAM_INT);
$stmtReservation->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
$stmtReservation->execute();
$reservation = $stmtReservation->fetch();

if (!$reservation) {
    echo "Erreur : Réservation non trouvée ou accès non autorisé.";
    exit;
}

$rideId = $reservation['covoiturage_id'];
$placesReservees = $reservation['places_reservees'];

// Récupérer les données actuelles du covoiturage
$stmtCovoiturage = $conn->prepare("SELECT places_restantes, passagers FROM covoiturages WHERE id = :ride_id");
$stmtCovoiturage->bindParam(':ride_id', $rideId, PDO::PARAM_INT);
$stmtCovoiturage->execute();
$covoiturage = $stmtCovoiturage->fetch();

if (!$covoiturage) {
    echo "Erreur : Covoiturage introuvable.";
    exit;
}

// Mettre à jour les données du covoiturage
$newPassagers = $covoiturage['passagers'] - $placesReservees;
$newPlacesRestantes = $covoiturage['places_restantes'] + $placesReservees;

try {
    $conn->beginTransaction();

    // Mise à jour du covoiturage
    $stmtUpdateCovoiturage = $conn->prepare("UPDATE covoiturages SET passagers = :passagers, places_restantes = :places_restantes WHERE id = :ride_id");
    $stmtUpdateCovoiturage->bindParam(':passagers', $newPassagers, PDO::PARAM_INT);
    $stmtUpdateCovoiturage->bindParam(':places_restantes', $newPlacesRestantes, PDO::PARAM_INT);
    $stmtUpdateCovoiturage->bindParam(':ride_id', $rideId, PDO::PARAM_INT);
    $stmtUpdateCovoiturage->execute();

    // Suppression de la réservation
    $stmtDeleteReservation = $conn->prepare("DELETE FROM reservations WHERE id = :id AND user_id = :user_id");
    $stmtDeleteReservation->bindParam(':id', $reservationId, PDO::PARAM_INT);
    $stmtDeleteReservation->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
    $stmtDeleteReservation->execute();

    $conn->commit();

    // Redirection après succès
    header('Location: /frontend/profil.php?message=Annulation réussie');
    exit;

} catch (Exception $e) {
    $conn->rollBack();
    echo "Erreur lors de l'annulation : " . $e->getMessage();
    exit;
}
?>
