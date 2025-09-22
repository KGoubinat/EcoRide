<?php
// create_troublesome_ride.php
declare(strict_types=1);

require __DIR__ . '/init.php';

function redirect(string $path, array $qs = []): never {
  $base = rtrim(BASE_URL, '/') . '/';
  if ($qs) $path .= (strpos($path,'?')===false?'?':'&') . http_build_query($qs);
  header('Location: ' . $base . ltrim($path,'/')); exit;
}

if (empty($_SESSION['user_id'])) {
  redirect('login.php');
}

$pdo = function_exists('getPDO') ? getPDO() : ($pdo ?? null);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CSRF
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
  redirect('employee_troublesome_rides.php', ['error'=>'csrf']);
}

// Params
$ride_id = filter_input(INPUT_POST, 'ride_id', FILTER_VALIDATE_INT);
$comment = trim((string)($_POST['comment'] ?? ''));
if (!$ride_id || $comment === '') {
  redirect('employee_troublesome_rides.php', ['error'=>'params']);
}

try {
  $pdo->beginTransaction();

  // Trouver le conducteur du covoiturage (covoiturages.user_id)
  $q = $pdo->prepare('SELECT id, user_id AS driver_id FROM covoiturages WHERE id = ? FOR UPDATE');
  $q->execute([$ride_id]);
  $ride = $q->fetch(PDO::FETCH_ASSOC);
  if (!$ride) throw new RuntimeException('Ride not found');

  $user_id   = (int)$_SESSION['user_id'];   // signaleur
  $driver_id = (int)$ride['driver_id'];     // conducteur

  // Insérer
  $ins = $pdo->prepare('
  INSERT IGNORE INTO troublesome_rides (ride_id, user_id, driver_id, comment, status, created_at)
  VALUES (?, ?, ?, ?, "open", NOW())
');
  $ins->execute([$ride_id, $user_id, $driver_id, $comment]);

  $pdo->commit();
  redirect('employee_troublesome_rides.php', ['success'=>1]);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  // error_log($e->getMessage());
  redirect('employee_troublesome_rides.php', ['error'=>1]);
}
