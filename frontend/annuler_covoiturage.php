<?php
session_start();

// Vérifier si un utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo "Aucun utilisateur connecté.";
    exit;
}


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
    echo "Connexion réussie à la base de données MySQL.";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

// Récupérer les informations de l'utilisateur
$user_email = $_SESSION['user_email'];
$stmtUser = $conn->prepare("SELECT id, firstName, lastName, email, credits, status FROM users WHERE email = ?");
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
$stmt = $conn->prepare("DELETE FROM covoiturages WHERE id = :id AND user_id = :user_id");
$stmt->bindParam(':id', $covoiturageId, PDO::PARAM_INT);
$stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
$stmt->execute();

// Vérifie si l'annulation a réussi
if ($stmt->rowCount() > 0) {
    // Rediriger vers la page du profil avec un message de succès
    header('Location: /frontend/profil.php?message=Annulation réussie');
    exit;
} else {
    // Si aucune ligne n'a été supprimée
    echo "Erreur : Aucun covoiturage trouvé avec cet ID ou vous n'êtes pas autorisé à annuler ce covoiturage.";
}
