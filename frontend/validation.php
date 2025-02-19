<?php
session_start();

// Vérifier si un utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Aucun utilisateur connecté.']);
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
    echo "Connexion réussie à la base de données MySQL.";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}


// Récupérer l'ID du trajet
if (isset($_GET['ride_id']) && is_numeric($_GET['ride_id'])) {
    $rideId = $_GET['ride_id'];
} else {
    echo "Aucun ID de trajet trouvé ou ID invalide.";
    exit;
}

// Vérification du token de validation
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $query = "SELECT * FROM validation_tokens WHERE token = :token AND expiration > NOW() LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['token' => $token]);
    $validToken = $stmt->fetch();

    if ($validToken) {
        $_SESSION['user_id'] = $validToken['user_id'];
    } else {
        echo "Lien de validation invalide ou expiré.";
        exit;
    }
} else {
    echo "Aucun token trouvé.";
    exit;
}

// Récupérer les informations du trajet
$query = "SELECT * FROM covoiturages WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $rideId]);
$ride = $stmt->fetch();

// Vérifier si l'utilisateur a soumis un avis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si le participant soumet un avis
    $feedback = $_POST['feedback'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'] ?? null; // Si le commentaire existe (uniquement si le trajet s'est mal passé)
    $utilisateurEmail = getUserEmail(); // Fonction à définir pour récupérer l'email de l'utilisateur

    // Valider si tout s’est bien passé
    if ($feedback == "good") {
        // Mise à jour des crédits du chauffeur
        // Soumettre l'avis et la note
        submitFeedback($pdo, $ride['user_id'], getUserId(), $rating, $comment, $utilisateurEmail); // Passage de $pdo en paramètre
        updateChauffeurCredits($pdo, $ride['user_id'], 5);  // Passage de $pdo en paramètre
    } else {
        // Si le feedback est "bad", on ajoute le commentaire dans 'troublesome_rides'
        $result = addCommentForEmployee($pdo, $rideId, $comment); // Ajout dans troublesome_rides
        if ($result) {
            $_SESSION['confirmation_message'] = "Votre commentaire a été soumis pour examen.";
        } else {
            $_SESSION['confirmation_message'] = "Erreur lors de l'ajout du commentaire.";
        }
    }

    
    
    
    $_SESSION['confirmation_message'] = "Avis soumis avec succès.";
    // Rediriger vers une page de confirmation
    header('Location: ' . $_SERVER['REQUEST_URI']);

    
    
    exit;
}

function getUserEmail() {
    // Récupérer l'email de l'utilisateur connecté
    return $_SESSION['user_email']; // Assure-toi que l'email est stocké dans la session
}

function getUserId() {
    // Récupérer l'ID de l'utilisateur
    return $_SESSION['user_id']; // Assure-toi que l'ID est stocké dans la session
}

function updateChauffeurCredits($pdo, $conducteurId, $credits) {
    // Mise à jour des crédits du chauffeur
    $query = "UPDATE users SET credits = credits + :credits WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['credits' => $credits, 'id' => $conducteurId]);
}

function addCommentForEmployee($pdo, $rideId, $comment) {
    // Récupérer le covoiturage associé à l'ID
    $query = "SELECT * FROM covoiturages WHERE id = :ride_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['ride_id' => $rideId]);
    $ride = $stmt->fetch();

    if ($ride) {
        // Vérifier si l'ID du conducteur existe dans la table users
        $queryCheckUser = "SELECT id FROM users WHERE id = :conducteur_id";
        $stmtCheckUser = $pdo->prepare($queryCheckUser);
        $stmtCheckUser->execute(['conducteur_id' => $ride['user_id']]);
        $user = $stmtCheckUser->fetch();

        if ($user) {
            // Si le conducteur existe, insérer le commentaire dans la table 'troublesome_rides'
            $query = "INSERT INTO troublesome_rides (ride_id, user_id, driver_id, comment, status) 
                      VALUES (:ride_id, :user_id, :driver_id, :comment, 'en attente')";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'ride_id' => $rideId,
                'user_id' => getUserId(),
                'driver_id' => $ride['user_id'],
                'comment' => $comment,
            ]);
        } else {
            // Si l'utilisateur n'existe pas, afficher une erreur ou gérer cette situation
            echo "Erreur : le conducteur avec l'ID {$ride['user_id']} n'existe pas dans la base de données.";
        }
    } else {
        // Si le covoiturage n'existe pas
        echo "Erreur : le covoiturage avec l'ID $rideId n'existe pas.";
    }
}




function submitFeedback($pdo, $conducteurId, $utilisateurId, $rating, $comment, $utilisateurEmail) {
    // Vérifier si l'utilisateur existe dans la table users
    $queryCheckUser = "SELECT id FROM users WHERE id = :utilisateur_id";
    $stmtCheckUser = $pdo->prepare($queryCheckUser);
    $stmtCheckUser->execute(['utilisateur_id' => $utilisateurId]);
    $user = $stmtCheckUser->fetch();

    if ($user) {
        // Si l'utilisateur existe, insérer l'avis et la note dans la table reviews
        try {
            // Insérer l'avis dans la table 'reviews'
            $query = "INSERT INTO reviews (user_id, driver_id, rating, comment, status, created_at) 
                      VALUES (:utilisateur_id, :conducteur_id, :rating, :comment, 'pending', NOW())";
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'utilisateur_id' => $utilisateurId,
                'conducteur_id' => $conducteurId,
                'rating' => $rating,
                'comment' => $comment,
            ]);
            echo "Avis soumis avec succès.";
        } catch (PDOException $e) {
            // Gestion des erreurs de base de données
            echo "Erreur lors de l'insertion de l'avis : " . $e->getMessage();
        }
    } else {
        // Si l'utilisateur n'existe pas, afficher une erreur appropriée
        echo "Erreur : l'utilisateur avec l'ID $utilisateurId n'existe pas dans la base de données.";
    }
}




?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du covoiturage</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
    <div class="header-container">
        <div class="logo">
            <h1>Détail du covoiturage</h1>
        </div>
        <nav>
            <ul>
                <li><a href="accueil.php">Accueil</a></li>
                <li><a href="contact-info">Contact</a></li>
                <li><a href="Covoiturages.php">Covoiturages</a></li>
                <li id="profilButton" data-logged-in="<?= isset($_SESSION['user_email']) ? 'true' : 'false'; ?>"></li>
                <li id="authButton" data-logged-in="<?= isset($_SESSION['user_email']) ? 'true' : 'false'; ?>" data-user-email="<?= isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''; ?>"></li>
            </ul>
        </nav>
    </div>
</header>

<main class=covoit>
    <?php
        if (!isset($_SESSION['user_email'])) {
            echo "Veuillez vous connecter pour laisser un avis.";
        } else {
            // Récupérer l'utilisateur connecté
            $user_email = $_SESSION['user_email'];
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmtUser->execute([$user_email]);
            $user = $stmtUser->fetch();

            // Si l'utilisateur existe, pré-remplir le champ "Votre nom"
            $user_name = $user ? htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) : ''; // Si l'utilisateur est trouvé, utiliser son nom, sinon une chaîne vide.
            ?>
            
            <div class="feedbackForm">
                <h2>Comment s'est passé le voyage?</h2>
                <form class="insideFeedbackForm" method="POST">
                    <div class="form-group">
                        <label for="good">Bien</label>
                        <input type="radio" id="good" name="feedback" value="good" required><br>
                        <label for="bad">Mal</label>
                        <input type="radio" id="bad" name="feedback" value="bad" required><br>
                    </div>
                    <div class="form-group">
                        <label for="rating">Note :</label><br>
                        <input type="number" id="rating" name="rating" min="1" max="5" required><br><br>
                    </div>
                    <div id="comment-section">
                        <label for="comment">Commentaires :</label><br>
                        <textarea id="comment" name="comment" rows="4" cols="50"></textarea><br><br>
                    </div>

                    <button type="submit" value="Soumettre">Soumettre</button>
                </form>
            </div>

            
            <?php
                if (isset($_SESSION['confirmation_message'])) {
                    $confirmationMessage = $_SESSION['confirmation_message'];
                    // Effacer le message après l'avoir affiché
                    unset($_SESSION['confirmation_message']);
            ?>
            <div id="confirmationModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">&times;</span>
                    <p><?php echo htmlspecialchars($confirmationMessage); ?></p>
                </div>
            </div>
            <?php } ?>

            </div>
            <?php
        }
    ?>
</main>

<footer>
    <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>

<script>
    
    // Afficher la modale si un message de confirmation est défini
    if (document.getElementById('confirmationModal')) {
        var modal = document.getElementById("confirmationModal");
        var closeBtn = document.querySelector(".close-btn");

        // Ouvrir la modale
        modal.style.display = "flex";

        // Fermer la modale lorsque l'utilisateur clique sur le bouton de fermeture
        closeBtn.onclick = function() {
            modal.style.display = "none";
            window.location.href = "accueil.php";
        }

        // Fermer la modale si l'utilisateur clique en dehors de celle-ci
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
                window.location.href = "accueil.php";
            }
        }
    }
</script>

<script src="js/validation.js"></script>
</body>
</html>
