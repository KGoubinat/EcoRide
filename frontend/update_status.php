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

// Récupérer l'ID de l'utilisateur
$stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
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
    $stmtUpdateStatus = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmtUpdateStatus->execute([$status, $user_id]);

    // Répondre avec succès
    echo json_encode(['status' => 'success', 'message' => 'Statut mis à jour avec succès.']);
} else {
    // En cas d'erreur
    echo json_encode(['status' => 'error', 'message' => 'Le statut n\'a pas été fourni.']);
}
?>
