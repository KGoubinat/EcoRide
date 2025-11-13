<?php
// add_review.php
require __DIR__ . '/init.php'; // ← lance session + BASE_URL + $pdo = getPDO()

// 1) Doit être connecté
if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo "Veuillez vous connecter pour laisser un avis.";
    exit;
}

// 2) Méthode POST uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Méthode non autorisée.";
    exit;
}

// 3) Récupération + validations
$driver_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT); // id du conducteur évalué
$note      = filter_input(INPUT_POST, 'note', FILTER_VALIDATE_INT);
$comment   = trim($_POST['commentaire'] ?? '');

$errors = [];
if (!$driver_id)                { $errors['user_id'] = 'Conducteur invalide.'; }
if ($note === false || $note < 1 || $note > 5) { $errors['note'] = 'Note entre 1 et 5.'; }
if ($comment === '')            { $errors['commentaire'] = 'Commentaire requis.'; }

if ($errors) {
    http_response_code(422);
    echo "Erreur de validation : " . implode(' ', $errors);
    exit;
}

$email_utilisateur = $_SESSION['user_email'];

try {
    // 4) Récupérer l'ID de l'utilisateur connecté
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email_utilisateur]);
    $me = $stmt->fetch();

    if (!$me) {
        http_response_code(404);
        echo "Utilisateur non trouvé.";
        exit;
    }

    $user_id = (int)$me['id']; // auteur de l'avis

    // (optionnel) empêcher l’auto-avis
    if ($user_id === $driver_id) {
        http_response_code(400);
        echo "Vous ne pouvez pas laisser un avis sur vous-même.";
        exit;
    }

    // (optionnel) éviter les doublons (un avis par paire user/driver)
    $check = $pdo->prepare("SELECT 1 FROM reviews WHERE user_id = ? AND driver_id = ? LIMIT 1");
     $check->execute([$user_id, $driver_id]);
     if ($check->fetch()) {
         http_response_code(409);
         echo "Vous avez déjà laissé un avis pour ce conducteur.";
         exit;
    // }

    // 5) Insertion (ordre des colonnes corrigé : user_id = auteur, driver_id = conducteur évalué)
    $ins = $pdo->prepare("
        INSERT INTO reviews (user_id, driver_id, rating, comment, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $ins->execute([$user_id, $driver_id, $note, $comment]);

    // 6) Redirection
    header('Location: ' . BASE_URL . 'details.php?id=' . $driver_id);
    exit;

} }
catch (Throwable $e) {
    // En dev, loggue $e->getMessage()
    http_response_code(500);
    echo "Erreur lors de l'ajout de l'avis.";
    exit;
}
