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
$password = 'nouveau_mot_de_passe';  // Remplacer par ton mot de passe
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

// Récupérer les informations de l'utilisateur
$user_email = $_SESSION['user_email'];
$stmtUser = $pdo->prepare("SELECT id, firstName, lastName, email, credits, status FROM users WHERE email = ?");
$stmtUser->execute([$user_email]);
$user = $stmtUser->fetch();

if (!$user) {
    echo "Utilisateur non trouvé.";
    exit;
}

// Vérifie que l'ID du covoiturage est passé via GET et n'est pas vide
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Erreur : ID de covoiturage manquant.");
}

// Récupère l'ID du covoiturage envoyé dans l'URL
$covoiturageId = $_GET['id'];
echo "ID de covoiturage reçu : " . htmlspecialchars($covoiturageId);

// Prépare et exécute la suppression du covoiturage
$stmt = $pdo->prepare("DELETE FROM covoiturages WHERE id = :id AND user_id = :user_id");
$stmt->bindParam(':id', $covoiturageId, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
$stmt->execute();

// Vérifie si l'annulation a réussi
if ($stmt->rowCount() > 0) {
    // Rediriger vers la page du profil avec un message de succès
    header('Location: profil.php?message=Annulation réussie');
    exit;
} else {
    // Si aucune ligne n'a été supprimée
    echo "Erreur : Aucun covoiturage trouvé avec cet ID ou vous n'êtes pas autorisé à annuler ce covoiturage.";
}
