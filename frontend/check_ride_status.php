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

// Récupérer les données envoyées par AJAX
$data = json_decode(file_get_contents('php://input'), true);
$covoiturageId = $data['covoiturageId']; // L'ID du covoiturage

// Vérifier si l'ID du covoiturage est fourni
if (isset($covoiturageId)) {
    // Requête pour récupérer l'état du covoiturage
    $query = "SELECT statut FROM covoiturages WHERE id = ?";
    
    // Préparer la requête
    $stmt = $pdo->prepare($query);
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
