<?php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session + BASE_URL + fonctions
$pdo = getPDO();               // OBLIGATOIRE

// Sécurise l’auth si la fonction n’existe pas déjà
if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        return !empty($_SESSION['user_id']);
    }
}

/* ======================================================
   1) RÉCUPÉRATION & VALIDATION DES PARAMÈTRES GET
====================================================== */

$rideId = filter_input(INPUT_GET, 'ride_id', FILTER_VALIDATE_INT);
$token  = $_GET['token'] ?? null;

if (!$rideId) {
    http_response_code(400);
    exit("ID du trajet invalide.");
}

if (!$token) {
    http_response_code(400);
    exit("Token manquant.");
}

/* ======================================================
   2) VALIDATION DU TOKEN AVANT TOUTE DEMANDE DE LOGIN
====================================================== */

try {
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM validation_tokens
        WHERE token = :token
          AND expiration > NOW()
        LIMIT 1
    ");
    $stmt->execute(['token' => $token]);
    $validToken = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log("Token check error: " . $e->getMessage());
    http_response_code(500);
    exit("Erreur interne.");
}

if (!$validToken) {
    http_response_code(400);
    exit("Lien expiré ou invalide.");
}

/* ======================================================
   3) AUTHENTIFICATION AUTOMATIQUE
====================================================== */

$_SESSION['user_id'] = (int)$validToken['user_id'];

if (!isLoggedIn()) {
    // Sécurité supplémentaire
    header("Location: " . BASE_URL . "login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

/* ======================================================
   4) RÉCUPÉRATION DU TRAJET
====================================================== */

try {
    $stmt = $pdo->prepare("SELECT * FROM covoiturages WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $rideId]);
    $ride = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log("Ride fetch error: " . $e->getMessage());
    http_response_code(500);
    exit("Erreur interne.");
}

if (!$ride) {
    http_response_code(404);
    exit("Trajet introuvable.");
}

/* ======================================================
   5) GÉNÉRATION DU CSRF (SI ABSENT)
====================================================== */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

/* ======================================================
   6) TRAITEMENT DU FORMULAIRE POST
====================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        http_response_code(400);
        exit("Requête invalide (CSRF).");
    }

    $feedback = $_POST['feedback'] ?? null;
    $rating   = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $comment  = trim((string)($_POST['comment'] ?? ''));
    $userId   = (int)$_SESSION['user_id'];
    $driverId = (int)$ride['user_id'];

    if (!in_array($feedback, ['good', 'bad'], true)) {
        http_response_code(400);
        exit("Feedback invalide.");
    }

    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        exit("Note invalide.");
    }

    try {
        if ($feedback === 'good') {

            // Ajout d’un avis
            $stmt = $pdo->prepare("
                INSERT INTO reviews (user_id, driver_id, rating, comment, status, created_at)
                VALUES (:u, :d, :r, :c, 'pending', NOW())
            ");
            $stmt->execute([
                'u' => $userId,
                'd' => $driverId,
                'r' => $rating,
                'c' => $comment
            ]);

            // Bonus chauffeur
            $pdo->prepare("UPDATE users SET credits = credits + 5 WHERE id = ?")
                ->execute([$driverId]);

            $_SESSION['confirmation_message'] = "Votre avis a été soumis.";

        } else {

            // Signalement
            $stmt = $pdo->prepare("
                INSERT INTO troublesome_rides (ride_id, user_id, driver_id, comment, status, created_at)
                VALUES (:ride, :u, :d, :c, 'en attente', NOW())
            ");
            $stmt->execute([
                'ride' => $rideId,
                'u'    => $userId,
                'd'    => $driverId,
                'c'    => $comment
            ]);

            $_SESSION['confirmation_message'] = "Votre signalement a été transmis.";
        }

    } catch (Throwable $e) {
        error_log("Feedback insert error: " . $e->getMessage());
        http_response_code(500);
        exit("Erreur interne.");
    }

    // Redirection PRG
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Retour d'expérience</title>
    <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/modern.css">
</head>
<body>

<main class="covoit">
    <div class="feedbackForm">
        <h2>Comment s'est passé le trajet ?</h2>

        <form class="insideFeedbackForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <label><input type="radio" name="feedback" value="good" required> 👍 Bien</label><br>
            <label><input type="radio" name="feedback" value="bad" required> 👎 Mal</label><br>

            <label for="rating">Note :</label>
            <input type="number" id="rating" name="rating" min="1" max="5" required>

            <label for="comment">Commentaire :</label>
            <textarea id="comment" name="comment" rows="4"></textarea>

            <button type="submit">Envoyer</button>
        </form>
    </div>

    <?php if (!empty($_SESSION['confirmation_message'])): ?>
        <div id="confirmationModal" class="modal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <p><?= htmlspecialchars($_SESSION['confirmation_message']) ?></p>
            </div>
        </div>
        <?php unset($_SESSION['confirmation_message']); ?>
    <?php endif; ?>

</main>

<script>
if (document.getElementById('confirmationModal')) {
    const modal = document.getElementById("confirmationModal");
    const close = document.querySelector(".close-btn");
    modal.style.display = "flex";
    close.onclick = () => window.location.href = "home.php";
    window.onclick = e => { if (e.target === modal) window.location.href = "home.php"; };
}
</script>

</body>
</html>
