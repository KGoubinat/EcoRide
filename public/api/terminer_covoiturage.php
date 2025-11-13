<?php
declare(strict_types=1);
require __DIR__ . '/../init.php';
header('Content-Type: application/json; charset=UTF-8');

if (empty($_SESSION['user_email']) && empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['status'=>'error','message'=>'Non connecté']);
  exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  header('Allow: POST');
  echo json_encode(['status'=>'error','message'=>'Méthode non autorisée']);
  exit;
}

// CSRF
$csrf = $_POST['csrf_token'] ?? $_POST['csrf'] ?? '';
if (!$csrf || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$csrf)) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'CSRF invalide']);
  exit;
}

// ID
$rideId = filter_var($_POST['covoiturage_id'] ?? $_POST['ride_id'] ?? null, FILTER_VALIDATE_INT);
if (!$rideId) {
  http_response_code(400);
  echo json_encode(['status'=>'error','message'=>'Paramètres invalides (id)']);
  exit;
}

$pdo = getPDO();

// User id
$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId && !empty($_SESSION['user_email'])) {
  $stU = $pdo->prepare('SELECT id FROM users WHERE email = ?');
  $stU->execute([$_SESSION['user_email']]);
  $userId = (int)$stU->fetchColumn();
}
if (!$userId) {
  http_response_code(401);
  echo json_encode(['status'=>'error','message'=>'Utilisateur introuvable']);
  exit;
}

// Contrôles
$st = $pdo->prepare('SELECT id, user_id, statut FROM covoiturages WHERE id = ?');
$st->execute([$rideId]);
$ride = $st->fetch(PDO::FETCH_ASSOC);
if (!$ride) {
  http_response_code(404);
  echo json_encode(['status'=>'error','message'=>'Covoiturage introuvable']);
  exit;
}
if ((int)$ride['user_id'] !== $userId) {
  http_response_code(403);
  echo json_encode(['status'=>'error','message'=>'Accès refusé']);
  exit;
}
if ($ride['statut'] !== 'en cours') {
  http_response_code(409);
  echo json_encode(['status'=>'error','message'=>"Statut actuel: {$ride['statut']}"]);
  exit;
}

// MAJ + notif
try {
  $pdo->beginTransaction();
  $pdo->prepare("UPDATE covoiturages SET statut = 'terminé' WHERE id = ?")->execute([$rideId]);
  $pdo->prepare("UPDATE reservations SET statut = 'terminé' WHERE covoiturage_id = ?")->execute([$rideId]);
  $pdo->commit();

  require_once __DIR__ . '/../../backend/lib/ride_notifications.php';
  try { notifyRideEvent($pdo, $rideId, 'end', $userId, false); } catch (\Throwable $e) {}

  echo json_encode(['status'=>'success','message'=>'Covoiturage terminé','newStatus'=>'terminé','id'=>$rideId]);
  exit;

} catch (\Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'Erreur serveur']);
  exit;
}
