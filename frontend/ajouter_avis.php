<?php

session_start();

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo "Veuillez vous connecter pour laisser un avis.";
    exit(); // Sortir du script si l'utilisateur n'est pas connecté
}

$dsn = 'mysql:host=localhost;dbname=ecoride';
$username = 'root';
$password = 'nouveau_mot_de_passe';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
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
            header("Location: details.php?id=" . $conducteur_id);
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
