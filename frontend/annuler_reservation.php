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

// Préparer et exécuter la suppression de la réservation
$stmt = $pdo->prepare("DELETE FROM reservations WHERE id = :id AND user_id = :user_id");
$stmt->bindParam(':id', $reservationId, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
$stmt->execute();

// Vérifie si l'annulation a réussi
if ($stmt->rowCount() > 0) {
    // Rediriger vers la page du profil avec un message de succès
    header('Location: profil.php?message=Annulation réussie');
    exit;
} else {
    // Si aucune ligne n'a été supprimée
    echo "Erreur : Aucune réservation trouvée avec cet ID ou vous n'êtes pas autorisé à annuler cette réservation.";
}
?>
