<?php
// update_troublesome_ride.php
declare(strict_types=1);
require __DIR__ . '/../../public/init.php';

function redirect(string $path, array $qs = []): never {
  $base = rtrim(BASE_URL, '/') . '/';
  if ($qs) $path .= (strpos($path,'?')===false?'?':'&') . http_build_query($qs);
  header('Location: ' . $base . ltrim($path,'/')); exit;
}

// employé ou admin
if (!in_array($_SESSION['user_role'] ?? null, ['employe','administrateur'], true)) {
  redirect('accueil.php');
}

$pdo = function_exists('getPDO') ? getPDO() : ($pdo ?? null);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CSRF pour l’auto-post
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Passerelle GET -> POST (compat des liens)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['id'], $_GET['status'])) {
  $self  = htmlspecialchars($_SERVER['PHP_SELF'] ?? '/update_troublesome_ride.php', ENT_QUOTES, 'UTF-8');
  $id    = (int)$_GET['id'];
  $st    = htmlspecialchars((string)$_GET['status'], ENT_QUOTES, 'UTF-8');
  $back  = htmlspecialchars((string)($_GET['back'] ?? 'employee_troublesome_rides.php'), ENT_QUOTES, 'UTF-8');
  $token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
  ?>
  <!doctype html><meta charset="utf-8">
  <form id="autopost" method="post" action="<?=$self?>">
    <input type="hidden" name="id" value="<?=$id?>">
    <input type="hidden" name="status" value="<?=$st?>">
    <input type="hidden" name="back" value="<?=$back?>">
    <input type="hidden" name="csrf_token" value="<?=$token?>">
  </form>
  <script>document.getElementById('autopost').submit();</script>
  <?php exit;
}

// POST + CSRF
$back = $_POST['back'] ?? 'employee_troublesome_rides.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST')  redirect($back, ['error'=>'method']);
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
  redirect($back, ['error'=>'csrf']);
}

$trouble_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$newStatus  = (string)($_POST['status'] ?? '');
if (!$trouble_id || !in_array($newStatus, ['open','resolved'], true)) {
  redirect($back, ['error'=>'params']);
}

try {
  $pdo->beginTransaction();

  // lock
  $s = $pdo->prepare('SELECT id FROM troublesome_rides WHERE id = ? FOR UPDATE');
  $s->execute([$trouble_id]);
  if (!$s->fetch()) throw new RuntimeException('not found');

  // UPDATE aligné (pas de handled_by / updated_at dans ton schéma)
  $u = $pdo->prepare('UPDATE troublesome_rides SET status = ? WHERE id = ?');
  $u->execute([$newStatus, $trouble_id]);

  $pdo->commit();
  redirect($back, ['success'=>1]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  // error_log($e->getMessage());
  redirect($back, ['error'=>1]);
}
