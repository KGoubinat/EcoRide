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
    $conn = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Impossible de se connecter à la base de données : " . $e->getMessage());
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

// Récupérer les données envoyées par AJAX
$data = json_decode(file_get_contents('php://input'), true);
$covoiturageId = $data['covoiturageId']; // L'ID du covoiturage

// Vérifier si l'ID du covoiturage est fourni
if (isset($covoiturageId)) {
    // Requête pour récupérer l'état du covoiturage
    $query = "SELECT statut FROM covoiturages WHERE id = ?";
    
    // Préparer la requête
    $stmt = $conn->prepare($query);
    $stmt->execute([$covoiturageId]); // Exécuter la requête avec l'ID du covoiturage
    
    // Récupérer le statut du covoiturage
    $row = $stmt->fetch();
    
    // Si le covoiturage existe, renvoyer son statut
    if ($row) {
        echo json_encode(['success' => true]);
    } else {
        // Si le covoiturage n'existe pas
        echo json_encode(['success' => false]);
    }
} else {
    // Si l'ID du covoiturage n'est pas fourni
    echo json_encode(['success' => false]);
}
?>
