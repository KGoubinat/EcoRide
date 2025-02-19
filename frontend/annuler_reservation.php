<?php
session_start();

// Vérifier si un utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo "Aucun utilisateur connecté.";
    exit;
}

// Connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=ecoride';
$username = 'root';
$password = 'nouveau_mot_de_passe';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Impossible de se connecter à la base de données : " . $e->getMessage());
}

// Vérifier que l'ID de la réservation est passé via POST
if (!isset($_POST['reservation_id']) || empty($_POST['reservation_id'])) {
    die("Erreur : ID de réservation manquant.");
}

$reservationId = $_POST['reservation_id'];

// Récupérer les informations de l'utilisateur
$user_email = $_SESSION['user_email'];
$stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmtUser->execute([$user_email]);
$user = $stmtUser->fetch();

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}

// Récupérer l'ID de la course associée à cette réservation
$stmtReservation = $pdo->prepare("SELECT covoiturage_id FROM reservations WHERE id = :id AND user_id = :user_id");
$stmtReservation->bindParam(':id', $reservationId, PDO::PARAM_INT);
$stmtReservation->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
$stmtReservation->execute();
$reservation = $stmtReservation->fetch();

if (!$reservation) {
    echo "Erreur : Aucune réservation trouvée avec cet ID ou vous n'êtes pas autorisé à annuler cette réservation.";
    exit;
}

$rideId = $reservation['covoiturage_id'];

// Récupérer le nombre actuel de places restantes dans la table 'covoiturage'
$stmtCovoiturage = $pdo->prepare("SELECT places_restantes FROM covoiturages WHERE id = :ride_id");
$stmtCovoiturage->bindParam(':ride_id', $rideId, PDO::PARAM_INT);
$stmtCovoiturage->execute();
$covoiturage = $stmtCovoiturage->fetch();

if (!$covoiturage) {
    echo "Erreur : Course non trouvée dans la table covoiturage.";
    exit;
}

$newPassagers = $covoiturage['passengers'] - 1;  // Réduire le nombre de passagers
$newPlacesRestantes = $covoiturage['places_restantes'] + 1;
// Mettre à jour le nombre de passagers et de places restantes
$stmtUpdateCovoiturage = $pdo->prepare("UPDATE covoiturages SET passengers = :passengers, places_restantes = :places_restantes WHERE id = :ride_id");
$stmtUpdateCovoiturage->bindParam(':passengers', $newPassagers, PDO::PARAM_INT);
$stmtUpdateCovoiturage->bindParam(':places_restantes', $newPlacesRestantes, PDO::PARAM_INT);
$stmtUpdateCovoiturage->bindParam(':ride_id', $rideId, PDO::PARAM_INT);
$stmtUpdateCovoiturage->execute();
// Supprimer la réservation
$stmtDeleteReservation = $pdo->prepare("DELETE FROM reservations WHERE id = :id AND user_id = :user_id");
$stmtDeleteReservation->bindParam(':id', $reservationId, PDO::PARAM_INT);
$stmtDeleteReservation->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
$stmtDeleteReservation->execute();

// Vérifie si l'annulation a réussi
if ($stmtDeleteReservation->rowCount() > 0) {
    // Rediriger vers la page du profil avec un message de succès
    header('Location: profil.php?message=Annulation réussie');
    exit;
} else {
    // Si aucune ligne n'a été supprimée
    echo "Erreur : Aucune réservation trouvée avec cet ID ou vous n'êtes pas autorisé à annuler cette réservation.";
}
?>
