<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_email']);
$user_email = $_SESSION['user_email'] ?? null;

if (!$isLoggedIn) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté.']);
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
    
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

// Récupérer l'ID de l'utilisateur
$stmtUser = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmtUser->execute([$user_email]);
$user = $stmtUser->fetch();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non trouvé.']);
    exit;
}

$user_id = $user['id'];

// Vérifier si un statut a été envoyé
if (isset($_POST['status'])) {
    $status = $_POST['status'];

    // Mettre à jour le statut de l'utilisateur dans la base de données
    $stmtUpdateStatus = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmtUpdateStatus->execute([$status, $user_id]);

    // Répondre avec succès
    echo json_encode(['status' => 'success', 'message' => 'Statut mis à jour avec succès.']);
} else {
    // En cas d'erreur
    echo json_encode(['status' => 'error', 'message' => 'Le statut n\'a pas été fourni.']);
}
?>
