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
    
    // Récupérer la redirection envoyée dans le formulaire
    $redirect = isset($_POST['redirect']) ? urldecode($_POST['redirect']) : 'accueil.php'; // Décoder l'URL


    if (empty($email) || empty($password)) {
        $errorMessage = "Veuillez remplir tous les champs.";
    } else {
        // Vérifier si l'email existe dans la base de données
        $stmt = $pdo->prepare("SELECT id, firstName, lastName, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Stocker les infos de l'utilisateur dans la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['firstName'] = $user['firstName'];
                $_SESSION['lastName'] = $user['lastName'];

                // Affichage de débogage sur la redirection
                var_dump($_SESSION); // Affiche les variables de session après connexion

                // Rediriger l'utilisateur
                if ($_SESSION['user_role'] == 'administrateur') {
                    
                    header("Location: admin_dashboard.php");
                } elseif ($_SESSION['user_role'] == 'employe') {
                    
                    header("Location: employee_dashboard.php");
                } else {
                    // Si l'URL est relative, la rendre absolue
                    if (substr($redirect, 0, 1) === '/') {
                        $redirectUrl = "http://" . $_SERVER['HTTP_HOST'] . $redirect;
                    } else {
                        $redirectUrl = $redirect; // Si déjà absolu, on garde tel quel
                    }
                    header("Location: " . htmlspecialchars($redirectUrl)); // Sécurisation de l'URL
                }
                exit;
            } else {
                $errorMessage = "Mot de passe incorrect.";
            }
        } else {
            $errorMessage = "Aucun utilisateur trouvé avec cet email.";
        }
    }
}

