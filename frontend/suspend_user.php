<?php
session_start();

// Vérifier si l'utilisateur est administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header("Location: accueil.php");
    exit;

}

if (isset($_GET['id'])) {
    $userId = $_GET['id'];

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

    // Mettre à jour le statut de l'utilisateur pour le suspendre
    $stmt = $conn->prepare("UPDATE users SET etat = 'suspended' WHERE id = ?");
    $stmt->execute([$userId]);

    // Rediriger vers la page de gestion des utilisateurs
    header("Location: /frontend/manage_users.php");
    exit;
}
?>
