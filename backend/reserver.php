<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

session_start();

require __DIR__ . '/../frontend/init.php'; // session + BASE_URL + getPDO()
$pdo = getPDO();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Auth (on garde la convention basée sur l'email ici pour éviter les gros diffs)
if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit;
}
$userEmail = $_SESSION['user_email'];

// Méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Payload JSON robuste
$raw  = file_get_contents('php://input');
$json = json_decode($raw, true);
if ($raw !== '' && $json === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON mal formé.']);
    exit;
}

$rideId     = is_array($json) ? (int)($json['ride_id'] ?? $json['id'] ?? 0) : (int)($_POST['ride_id'] ?? $_POST['id'] ?? 0);
$passengers = is_array($json) ? (int)($json['passengers'] ?? 0)             : (int)($_POST['passengers'] ?? 0);
$csrfBody   = is_array($json) ? ($json['csrf_token'] ?? null)               : ($_POST['csrf_token'] ?? null);

// CSRF
$headers = function_exists('getallheaders') ? getallheaders() : [];
$csrfHeader   = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
$csrfProvided = $csrfHeader ?: $csrfBody;

if (empty($_SESSION['csrf_token']) || empty($csrfProvided) || !hash_equals($_SESSION['csrf_token'], $csrfProvided)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Échec de vérification CSRF.']);
    exit;
}

// Validation rapide
if ($rideId <= 0 || $passengers <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres incorrects.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // User (FOR UPDATE => anti double-spend)
    $st = $pdo->prepare("SELECT id, credits FROM users WHERE email = ? FOR UPDATE");
    $st->execute([$userEmail]);
    $user = $st->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
        exit;
    }
    $userId      = (int)$user['id'];
    $creditsUser = (float)$user['credits'];

    // Trajet (FOR UPDATE)
    $stmt = $pdo->prepare("SELECT id, user_id, prix, places_restantes, statut, `date`, `heure_depart`
                           FROM covoiturages WHERE id = ? FOR UPDATE");
    $stmt->execute([$rideId]);
    $ride = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ride) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Covoiturage non trouvé.']);
        exit;
    }

    // Règles métiers
    if (in_array($ride['statut'], ['terminé', 'annulé'], true)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ce covoiturage n’est plus réservable.']);
        exit;
    }
    if ((int)$ride['user_id'] === $userId) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas réserver votre propre covoiturage.']);
        exit;
    }
    if (strtotime($ride['date'].' '.$ride['heure_depart']) <= time()) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ce covoiturage est déjà parti.']);
        exit;
    }

    $prix      = (float)$ride['prix'];
    $placesOK  = (int)$ride['places_restantes'];
    $totalCost = $prix * $passengers;

    if ($placesOK < $passengers) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Pas assez de places disponibles.']);
        exit;
    }
    if ($creditsUser < $totalCost) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Crédits insuffisants.']);
        exit;
    }

    // Empêcher double réservation active
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations
                           WHERE user_id = ? AND covoiturage_id = ? AND statut IN ('en attente','en cours')");
    $stmt->execute([$userId, $rideId]);
    if ((int)$stmt->fetchColumn() > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Vous avez déjà une réservation pour ce covoiturage.']);
        exit;
    }

    // INSERT réservation
    $stmt = $pdo->prepare("INSERT INTO reservations (user_id, covoiturage_id, statut, places_reservees, created_at)
                           VALUES (?, ?, 'en attente', ?, NOW())");
    $stmt->execute([$userId, $rideId, $passengers]);

    // UPDATE crédits & places
    $pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?")
        ->execute([$totalCost, $userId]);
    $pdo->prepare("UPDATE covoiturages
                   SET places_restantes = places_restantes - ?, passagers = passagers + ?
                   WHERE id = ?")
        ->execute([$passengers, $passengers, $rideId]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Réservation effectuée avec succès.',
        'remaining_places'  => $placesOK - $passengers,
        'remaining_credits' => $creditsUser - $totalCost
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Erreur réservation: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue.']);
    exit;
}
