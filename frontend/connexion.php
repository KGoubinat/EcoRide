<?php
session_start(); // Démarrer la session

// Connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=ecoride';
$username = 'root';
$password = 'nouveau_mot_de_passe'; // Mets ton mot de passe ici si nécessaire
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

    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    
    // Vérification si les champs sont vides
    if (empty($email) || empty($password)) {
        $errorMessage = "Veuillez remplir tous les champs.";
    } else {
        // Vérifier si l'email existe dans la base de données
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Vérifier le mot de passe
            if (password_verify($password, $user['password'])) {
                // Démarrer une session et stocker l'ID et l'email
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $email;

                // Rediriger vers la page d'accueil après une connexion réussie
                header("Location: accueil.php");
                exit;
            } else {
                $errorMessage = "Mot de passe incorrect.";
            }
        } else {
            $errorMessage = "Aucun utilisateur trouvé avec cet email.";
        }
    }
}

// Affichage des messages d'erreur
if (isset($errorMessage)) {
    echo $errorMessage;
}
?>
