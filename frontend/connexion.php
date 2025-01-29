<?php
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

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validation des données
    if (empty($email) || empty($password)) {
        die("Tous les champs doivent être remplis.");
    }

    // Vérifier si l'email est valide
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Adresse email invalide.");
    }

    // Vérifier si l'email existe
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Si l'utilisateur existe et le mot de passe est correct
    if ($user && password_verify($password, $user['password'])) {
        session_start(); // Démarrer la session
        $_SESSION['user_id'] = $user['id']; // Stocker l'ID de l'utilisateur
        $_SESSION['user_email'] = $email; // Stocker l'email de l'utilisateur

        // Connexion réussie
        echo "Connexion réussie !";

        // Rediriger vers la page d'accueil ou autre page protégée
        header("Location: index.html");
        exit;
    } else {
        echo "Identifiants incorrects.";
    }
}
?>
