<?php

session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo "Veuillez vous connecter pour laisser un avis.";
    exit(); // Sortir du script si l'utilisateur n'est pas connecté
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Récupérer les données du formulaire
    $conducteur_id = $_POST['user_id'];
    $email_utilisateur = $_SESSION['user_email']; // Utiliser l'email de l'utilisateur connecté
    $note = $_POST['note'];
    $commentaire = $_POST['commentaire'];

    // Validation basique des entrées
    if (empty($conducteur_id) || empty($note) || empty($commentaire)) {
        echo "Tous les champs sont requis.";
        exit();
    }

    // Récupérer l'ID de l'utilisateur à partir de son email
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email_utilisateur]);
        $user = $stmt->fetch();

        if ($user) {
            $utilisateur_id = $user['id']; // Récupérer l'ID de l'utilisateur

            // Insertion de l'avis dans la base de données
            $stmt = $pdo->prepare("INSERT INTO reviews (user_id, driver_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$conducteur_id, $utilisateur_id, $note, $commentaire]);

            // Redirection vers les détails du covoiturage
            header("Location: /frontend/details.php?id=" . $conducteur_id);
            exit();
        } else {
            echo "L'utilisateur n'existe pas.";
            exit();
        }
    } catch (PDOException $e) {
        echo "Erreur lors de l'ajout de l'avis : " . $e->getMessage();
        exit();
    }
}
?>
