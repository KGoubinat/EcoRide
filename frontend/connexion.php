<?php
session_start(); // Démarrer la session

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

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    
    // Récupérer la redirection envoyée dans le formulaire
    $redirect = isset($_POST['redirect']) ? urldecode($_POST['redirect']) : '/frontend/accueil.php'; // Décoder l'URL


    if (empty($email) || empty($password)) {
        $errorMessage = "Veuillez remplir tous les champs.";
    } else {
        // Vérifier si l'email existe dans la base de données
        $stmt = $conn->prepare("SELECT id, firstName, lastName, password, role FROM users WHERE email = ?");
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
               

                // Rediriger l'utilisateur
                if ($_SESSION['user_role'] == 'administrateur') {
                    
                    header("Location: /frontend/admin_dashboard.php");
                } elseif ($_SESSION['user_role'] == 'employe') {
                    
                    header("Location: /frontend/employee_dashboard.php");
                } else {
                    // Si l'URL est relative, la rendre absolue
                    if (substr($redirect, 0, 1) === '/') {
                        $redirectUrl = "http://" . $_SERVER['HTTP_HOST'] . $redirect;
                    } else {
                        $redirectUrl = $redirect; // Si déjà absolu, on garde tel quel
                    }
                    header("Location:" . htmlspecialchars($redirectUrl)); // Sécurisation de l'URL
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

