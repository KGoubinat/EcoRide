<?php
require __DIR__ . '/init.php'; // bootstrap + db + session

// 1) Vérifie paramètres GET de base
if (!isset($_GET['ride_id']) || !ctype_digit((string)$_GET['ride_id'])) {
    http_response_code(400);
    exit("Aucun ID de trajet trouvé ou ID invalide.");
}
$rideId = (int) $_GET['ride_id'];

if (!isset($_GET['token'])) {
    http_response_code(400);
    exit("Aucun token fourni.");
}
$token = (string)$_GET['token'];

// 2) Vérifie le token de validation AVANT toute exigence de login
$stmt = $pdo->prepare("SELECT * FROM validation_tokens WHERE token = :token AND expiration > NOW() LIMIT 1");
$stmt->execute(['token' => $token]);
$validToken = $stmt->fetch();

if (!$validToken) {
    http_response_code(400);
    exit("Lien de validation invalide ou expiré.");
}

// 3) Associe l’utilisateur à la session via le token (auth implicite)
$_SESSION['user_id'] = (int)$validToken['user_id'];

// 4) Maintenant seulement, on peut considérer l’utilisateur “connecté”
if (!isLoggedIn()) {
    // Par sécurité, ça ne devrait pas arriver si le token était valide
    http_response_code(401);
    exit("Veuillez vous connecter pour accéder à cette page.");
}

// 5) Récupère les infos du trajet
$stmt = $pdo->prepare("SELECT * FROM covoiturages WHERE id = :id");
$stmt->execute(['id' => $rideId]);
$ride = $stmt->fetch();

if (!$ride) {
    http_response_code(404);
    exit("Trajet introuvable.");
}

// 6) CSRF : crée si absent (pour le formulaire POST)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        http_response_code(400);
        exit("Requête invalide (CSRF).");
    }

    $feedback = $_POST['feedback'] ?? null;
    $rating   = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $comment  = trim((string)($_POST['comment'] ?? ''));
    $userId   = (int)$_SESSION['user_id'];

    // Validation serveur
    if (!in_array($feedback, ['good','bad'], true)) {
        http_response_code(400);
        exit("Choix de feedback invalide.");
    }
    if ($rating === null || $rating < 1 || $rating > 5) {
        http_response_code(400);
        exit("Note invalide (1 à 5).");
    }

    if ($feedback === "good") {
        // Insérer un avis dans reviews
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, driver_id, rating, comment, status, created_at)
            VALUES (:user_id, :driver_id, :rating, :comment, 'pending', NOW())
        ");
        $stmt->execute([
            'user_id'   => $userId,
            'driver_id' => (int)$ride['user_id'],
            'rating'    => $rating,
            'comment'   => $comment
        ]);

        // Crédits chauffeur (+5)
        $stmt = $pdo->prepare("UPDATE users SET credits = credits + 5 WHERE id = :id");
        $stmt->execute(['id' => (int)$ride['user_id']]);

        $_SESSION['confirmation_message'] = "Avis soumis avec succès.";
    } else { // "bad"
        // Enregistrer un signalement
        $stmt = $pdo->prepare("
            INSERT INTO troublesome_rides (ride_id, user_id, driver_id, comment, status, created_at)
            VALUES (:ride_id, :user_id, :driver_id, :comment, 'en attente', NOW())
        ");
        $stmt->execute([
            'ride_id'   => $rideId,
            'user_id'   => $userId,
            'driver_id' => (int)$ride['user_id'],
            'comment'   => $comment
        ]);

        $_SESSION['confirmation_message'] = "Votre commentaire a été transmis à un employé.";
    }

    // PRG (Post/Redirect/Get)
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du covoiturage</title>
    <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/modern.css">
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo"><h1>Détail du covoiturage</h1></div>
        <div class="menu-toggle" id="menu-toggle">☰</div>
        <nav id="navbar">
            <ul>
                <li><a href="home.php">Accueil</a></li>
                <li><a href="contact_info.php">Contact</a></li>
                <li><a href="rides.php">Covoiturages</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="covoit">
    <div class="feedbackForm">
        <h2>Comment s'est passé le voyage ?</h2>
        <form class="insideFeedbackForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
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
                <p><?= htmlspecialchars($_SESSION['confirmation_message'], ENT_QUOTES) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['confirmation_message']); ?>
    <?php endif; ?>
</main>

<footer>
        <div class="footer-links">
            <a href="#" id="open-cookie-modal">Gérer mes cookies</a>
            <span>|</span>
            <span>EcoRide@gmail.com</span>
            <span>|</span>
            <a href="legal_notice.php">Mentions légales</a>
        </div>
    </footer>

   <!-- Overlay bloquant -->
  <div id="cookie-blocker" class="cookie-blocker" hidden></div>
    <!-- Bandeau cookies -->
    <div id="cookie-banner" class="cookie-banner" hidden>
    <div class="cookie-content">
        <p>Nous utilisons des cookies pour améliorer votre expérience, mesurer l’audience et proposer des contenus personnalisés.</p>
        <div class="cookie-actions">
        <button data-action="accept-all" type="button">Tout accepter</button>
        <button data-action="reject-all" type="button">Tout refuser</button>
        <button data-action="customize"  type="button">Personnaliser</button>
        </div>
    </div>
    </div>

    <!-- Centre de préférences -->
    <div id="cookie-modal" class="cookie-modal" hidden>
    <div class="cookie-modal-card">
        <h3>Préférences de cookies</h3>
        <label><input type="checkbox" checked disabled> Essentiels (toujours actifs)</label><br>
        <label><input type="checkbox" id="consent-analytics"> Mesure d’audience</label><br>
        <label><input type="checkbox" id="consent-marketing"> Marketing</label>
        <div class="cookie-modal-actions">
        <button data-action="save"  type="button">Enregistrer</button>
        <button data-action="close" type="button">Fermer</button>
        </div>
    </div>
    </div>


<script src="assets/js/cookie_consent.js" defer></script>
<script>
if (document.getElementById('confirmationModal')) {
    var modal = document.getElementById("confirmationModal");
    var closeBtn = document.querySelector(".close-btn");
    modal.style.display = "flex";
    closeBtn.onclick = () => { modal.style.display = "none"; window.location.href = "home.php"; }
    window.onclick = (e) => { if (e.target === modal) { modal.style.display = "none"; window.location.href = "home.php"; }}
}
</script>
</body>
</html>
