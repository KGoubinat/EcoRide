<?php
declare(strict_types=1);
require __DIR__ . '/../../public/init.php'; // session + getPDO()
header('Content-Type: text/html; charset=UTF-8');

// 1) Auth
if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    exit("Aucun utilisateur connecté.");
}

// 2) Méthode POST uniquement
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

// 3) User + CSRF + id trajet
$pdo = getPDO();

$stmtUser = $pdo->prepare("SELECT id, firstName, lastName, email, credits FROM users WHERE email = ?");
$stmtUser->execute([$_SESSION['user_email']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    http_response_code(404);
    exit("Utilisateur non trouvé.");
}

$rideId = filter_input(INPUT_POST, 'covoiturage_id', FILTER_VALIDATE_INT);
if (!$rideId) {
    http_response_code(400);
    exit("ID de covoiturage manquant ou invalide.");
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit("Token CSRF invalide.");
}

try {
    $pdo->beginTransaction();

    // 4) Charger et verrouiller le trajet du conducteur
    $st = $pdo->prepare("
        SELECT id, user_id, `date`, statut
        FROM covoiturages
        WHERE id = ? AND user_id = ?
        FOR UPDATE
    ");
    $st->execute([$rideId, (int)$user['id']]);
    $ride = $st->fetch(PDO::FETCH_ASSOC);
    if (!$ride) {
        $pdo->rollBack();
        http_response_code(403);
        exit("Aucun covoiturage trouvé avec cet ID ou non autorisé.");
    }

    // 5) Si déjà terminé/en cours, on refuse (à adapter selon ta règle)
    if ($ride['statut'] === 'terminé') {
        $pdo->rollBack();
        http_response_code(409);
        exit("Impossible d’annuler : trajet déjà terminé.");
    }

    // 6) Règle de remboursement (ex. si le trajet n’a pas encore eu lieu)
    $refund = false;
    if (!empty($ride['date'])) {
        $refund = (strtotime($ride['date']) >= strtotime(date('Y-m-d')));
    }

    // 7) Marquer annulé (trajet + réservations)
    $pdo->prepare("UPDATE covoiturages SET statut = 'annulé' WHERE id = ?")->execute([$rideId]);
    $pdo->prepare("UPDATE reservations SET statut = 'annulé' WHERE covoiturage_id = ?")->execute([$rideId]);

    // 8) Remboursement éventuel
    if ($refund) {
        $pdo->prepare("UPDATE users SET credits = credits + 2 WHERE id = ?")->execute([(int)$user['id']]);
    }

    $pdo->commit();

    // 9) Notifications (Mailtrap via notifyRideEvent)
    require_once __DIR__ . '/../lib/ride_notifications.php';
    try {
        notifyRideEvent($pdo, $rideId, 'cancel', (int)$user['id'], true);
    } catch (\Throwable $e) {
        // On ignore une erreur d’envoi pour ne pas bloquer l’UX
    }

    // 10) Redirection propre
    header('Location: ' . BASE_URL . 'profile.php?message=' . urlencode('Annulation réussie'));
    exit;

} catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    exit("Erreur lors de l’annulation du covoiturage.");
}
