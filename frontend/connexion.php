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
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Stocker les infos de l'utilisateur dans la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['firstName'] = $user['firstName'];
                $_SESSION['lastName'] = $user['lastName'];

                // Rediriger l'utilisateur
                if ($_SESSION['user_role'] == 'administrateur') {
                    header("Location: /frontend/admin_dashboard.php");
                } elseif ($_SESSION['user_role'] == 'employe') {
                    header("Location: /frontend/employee_dashboard.php");
                } else {
                    // Vérifier et sécuriser la redirection
                    if (filter_var($redirect, FILTER_VALIDATE_URL) || substr($redirect, 0, 1) === '/') {
                        header("Location: " . htmlspecialchars($redirect));
                    } else {
                        header("Location: /frontend/accueil.php");
                    }
                }
                exit; // Arrêter l'exécution après la redirection
            } else {
                $errorMessage = "Mot de passe incorrect.";
            }
        } else {
            $errorMessage = "Aucun utilisateur trouvé avec cet email.";
        }
    }
}

// Afficher le message d'erreur si présent
if (isset($errorMessage)) {
    echo "<p style='color:red;'>" . htmlspecialchars($errorMessage) . "</p>";
}
?>
