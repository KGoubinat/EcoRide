<?php
require __DIR__ . '/init.php'; // charge bootstrap + db + session

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo "Veuillez vous connecter pour accéder à cette page.";
    exit;
}

// Vérifier que l'ID du trajet est fourni
if (!isset($_GET['ride_id']) || !is_numeric($_GET['ride_id'])) {
    echo "Aucun ID de trajet trouvé ou ID invalide.";
    exit;
}
$rideId = (int) $_GET['ride_id'];

// Vérifier que le token est fourni
if (!isset($_GET['token'])) {
    echo "Aucun token fourni.";
    exit;
}
$token = $_GET['token'];

// Vérifier le token dans la table validation_tokens
$stmt = $pdo->prepare("SELECT * FROM validation_tokens WHERE token = :token AND expiration > NOW() LIMIT 1");
$stmt->execute(['token' => $token]);
$validToken = $stmt->fetch();

if (!$validToken) {
    echo "Lien de validation invalide ou expiré.";
    exit;
}

// Associer l’utilisateur de la session
$_SESSION['user_id'] = $validToken['user_id'];

// Récupérer les infos du trajet
$stmt = $pdo->prepare("SELECT * FROM covoiturages WHERE id = :id");
$stmt->execute(['id' => $rideId]);
$ride = $stmt->fetch();

if (!$ride) {
    echo "Trajet introuvable.";
    exit;
}

// Soumission du formulaire d’avis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $feedback = $_POST['feedback'] ?? null;
    $rating   = $_POST['rating'] ?? null;
    $comment  = $_POST['comment'] ?? null;
    $userId   = $_SESSION['user_id'];

    if ($feedback === "good") {
        // Insérer un avis dans reviews
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, driver_id, rating, comment, status, created_at) 
            VALUES (:user_id, :driver_id, :rating, :comment, 'pending', NOW())
        ");
        $stmt->execute([
            'user_id'    => $userId,
            'driver_id'  => $ride['user_id'],
            'rating'     => $rating,
            'comment'    => $comment
        ]);

        // Donner des crédits au chauffeur
        $stmt = $pdo->prepare("UPDATE users SET credits = credits + 5 WHERE id = :id");
        $stmt->execute(['id' => $ride['user_id']]);

        $_SESSION['confirmation_message'] = "Avis soumis avec succès.";
    } elseif ($feedback === "bad") {
        // Enregistrer comme trajet problématique
        $stmt = $pdo->prepare("
            INSERT INTO troublesome_rides (ride_id, user_id, driver_id, comment, status, created_at) 
            VALUES (:ride_id, :user_id, :driver_id, :comment, 'en attente', NOW())
        ");
        $stmt->execute([
            'ride_id'   => $rideId,
            'user_id'   => $userId,
            'driver_id' => $ride['user_id'],
            'comment'   => $comment
        ]);

        $_SESSION['confirmation_message'] = "Votre commentaire a été transmis à un employé.";
    }

    // Recharge la page pour afficher la modale
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du covoiturage</title>
    <base href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo"><h1>Détail du covoiturage</h1></div>
        <div class="menu-toggle" id="menu-toggle">☰</div>
        <nav id="navbar">
            <ul>
                <li><a href="accueil.php">Accueil</a></li>
                <li><a href="contact_info.php">Contact</a></li>
                <li><a href="covoiturages.php">Covoiturages</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="covoit">
    <div class="feedbackForm">
        <h2>Comment s'est passé le voyage ?</h2>
        <form class="insideFeedbackForm" method="POST">
            <div class="form-group">
                <label><input type="radio" name="feedback" value="good" required> Bien</label><br>
                <label><input type="radio" name="feedback" value="bad" required> Mal</label><br>
            </div>
            <div class="form-group">
                <label for="rating">Note :</label><br>
                <input type="number" id="rating" name="rating" min="1" max="5" required><br><br>
            </div>
            <div id="comment-section">
                <label for="comment">Commentaires :</label><br>
                <textarea id="comment" name="comment" rows="4" cols="50"></textarea><br><br>
            </div>
            <button type="submit">Soumettre</button>
        </form>
    </div>

    <?php if (isset($_SESSION['confirmation_message'])): ?>
        <div id="confirmationModal" class="modal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <p><?= htmlspecialchars($_SESSION['confirmation_message']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['confirmation_message']); ?>
    <?php endif; ?>
</main>

<footer>
    <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>

<script>
if (document.getElementById('confirmationModal')) {
    var modal = document.getElementById("confirmationModal");
    var closeBtn = document.querySelector(".close-btn");
    modal.style.display = "flex";
    closeBtn.onclick = () => { modal.style.display = "none"; window.location.href = "accueil.php"; }
    window.onclick = (e) => { if (e.target === modal) { modal.style.display = "none"; window.location.href = "accueil.php"; }}
}
</script>
</body>
</html>
