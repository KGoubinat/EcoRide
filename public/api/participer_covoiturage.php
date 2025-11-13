<?php
// participer_covoiturage.php
declare(strict_types=1);
require __DIR__ . '/../init.php';
header('Content-Type: application/json; charset=UTF-8');

$isLocal = (getenv('APP_ENV') === 'local');

try {
  // 1) Méthode + session
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Méthode non autorisée']); exit;
  }
  if (empty($_SESSION['user_id']) || empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Aucun utilisateur connecté.']); exit;
  }
  $userId = (int)$_SESSION['user_id'];

  // 2) Entrées + CSRF (header OU body)
  $raw  = file_get_contents('php://input');
  $data = $_POST ?: (json_decode($raw, true) ?: []);
  $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($data['csrf_token'] ?? ($data['csrf'] ?? ''));

  if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$csrf)) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Token CSRF invalide.']); exit;
  }

  $rideId = filter_var(
    $data['covoiturage_id'] ?? $data['ride_id'] ?? $data['id'] ?? null,
    FILTER_VALIDATE_INT
);
  $passengers = filter_var(
    $data['passengers'] ?? 1,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'default' => 1]]
);
  if (!$rideId) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Paramètres invalides (id).']); exit;
}

  // 3) Utilitaires
  $pdo = getPDO();
  $hasCol = function(string $table, string $col) use ($pdo): bool {
    $q = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $q->execute([$table, $col]);
    return (int)$q->fetchColumn() > 0;
  };
  $hasPlacesReservees = $hasCol('reservations', 'places_reservees');

  // 4) Transaction + verrous
  $pdo->beginTransaction();

  // Lock covoiturage
  $stRide = $pdo->prepare("
    SELECT id, user_id, prix, places_restantes, statut
    FROM covoiturages
    WHERE id = ?
    FOR UPDATE
  ");
  $stRide->execute([$rideId]);
  $ride = $stRide->fetch();
  if (!$ride) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Covoiturage introuvable.']); exit; }

  if (($ride['statut'] ?? '') === 'terminé') {
    $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Ce covoiturage est terminé.']); exit;
  }

  // Lock user pour crédits
  $stUser = $pdo->prepare("SELECT id, credits FROM users WHERE id = ? FOR UPDATE");
  $stUser->execute([$userId]);
  $user = $stUser->fetch();
  if (!$user) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Utilisateur introuvable.']); exit; }

  // 5) Vérifs métier
  $prix            = (float)$ride['prix'];
  $placesRestantes = (int)$ride['places_restantes'];
  $coutTotal       = $prix * $passengers;

  if ($placesRestantes < $passengers) {
    $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Pas assez de places disponibles.']); exit;
  }
  if ((float)$user['credits'] < $coutTotal) {
    $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Crédits insuffisants.']); exit;
  }

  // Anti-doublon
  $stDup = $pdo->prepare("SELECT id FROM reservations WHERE user_id = ? AND covoiturage_id = ? LIMIT 1");
  $stDup->execute([$userId, $rideId]);
  if ($stDup->fetch()) {
    $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Vous avez déjà une réservation pour ce covoiturage.']); exit;
  }

  // 6) Insérer la réservation (avec places_reservees si la colonne existe)
  if ($hasPlacesReservees) {
    $stRes = $pdo->prepare("
      INSERT INTO reservations (user_id, covoiturage_id, statut, places_reservees)
      VALUES (?, ?, 'en attente', ?)
    ");
    $stRes->execute([$userId, $rideId, $passengers]);
  } else {
    $stRes = $pdo->prepare("
      INSERT INTO reservations (user_id, covoiturage_id, statut)
      VALUES (?, ?, 'en attente')
    ");
    $stRes->execute([$userId, $rideId]);
  }

  // 7) Mettre à jour le covoiturage
  // (on décompte les places; si la colonne 'passagers' existe on l’incrémente aussi)
  $sql = "UPDATE covoiturages SET places_restantes = places_restantes - ?";
  if ($hasCol('covoiturages','passagers')) $sql .= ", passagers = passagers + ?";
  $sql .= " WHERE id = ?";

  $params = [$passengers];
  if ($hasCol('covoiturages','passagers')) $params[] = $passengers;
  $params[] = $rideId;

  $stUpRide = $pdo->prepare($sql);
  $stUpRide->execute($params);

  // 8) Débiter l’utilisateur
  $stUpUser = $pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
  $stUpUser->execute([$coutTotal, $userId]);

  $pdo->commit();

  echo json_encode([
    'success' => true,
    'message' => 'Réservation effectuée avec succès.',
    'reservation' => [
      'ride_id'     => (int)$rideId,
      'passengers'  => (int)$passengers,
      'status'      => 'en attente',
      'price_total' => $coutTotal
    ]
  ]);
  exit;

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode([
    'success' => false,
    'message' => $isLocal ? ('Erreur serveur: '.$e->getMessage()) : 'Erreur serveur.'
  ]);
  exit;
}
