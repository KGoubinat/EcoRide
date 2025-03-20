<?php
session_start();

// Vérifier si l'utilisateur est administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header("Location: accueil.php");
    exit;
}

// Vérifier si l'ID de l'utilisateur est présent et valide
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID utilisateur invalide.");
}

// Convertir l'ID en entier pour éviter les injections SQL
$userId = (int) $_GET['id'];

// Récupérer l'URL de la base de données depuis JAWSDB_URL
$databaseUrl = getenv('JAWSDB_URL');

if (!$databaseUrl) {
    die("Erreur : La variable d'environnement JAWSDB_URL n'est pas définie.");
}

// Extraire les informations de connexion depuis l'URL
$parsedUrl = parse_url($databaseUrl);
$servername = $parsedUrl['host'];
$username = $parsedUrl['user'];
$password = $parsedUrl['pass'];
$dbname = ltrim($parsedUrl['path'], '/');

// Connexion à la base de données avec PDO
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mettre à jour l'état de l'utilisateur pour l'activer
    $stmt = $conn->prepare("UPDATE users SET etat = 'active' WHERE id = ?");
    $stmt->execute([$userId]);

    // Rediriger vers la page de gestion des utilisateurs
    header("Location: /frontend/manage_users.php");
    exit;

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
