<?php
session_start();

// Vérifier si l'utilisateur est administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header("Location: accueil.php");
    exit;
}

if (isset($_GET['id'])) {
    $userId = $_GET['id'];

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

    // Mettre à jour le statut de l'utilisateur pour le suspendre
    $stmt = $pdo->prepare("UPDATE users SET etat = 'suspended' WHERE id = ?");
    $stmt->execute([$userId]);

    // Rediriger vers la page de gestion des utilisateurs
    header("Location: manage_users.php");
    exit;
}
?>
