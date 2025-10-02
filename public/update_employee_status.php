<?php
// update_employee_status.php
declare(strict_types=1);

require __DIR__ . '/init.php';

function redirect(string $path, array $qs = []): never {
  $base = rtrim(BASE_URL, '/') . '/';
  if ($qs) $path .= (strpos($path,'?')===false?'?':'&') . http_build_query($qs);
  header('Location: ' . $base . ltrim($path, '/'));
  exit;
}

// Admin only
if (($_SESSION['user_role'] ?? null) !== 'administrateur') {
  redirect('accueil.php');
}

$pdo = function_exists('getPDO') ? getPDO() : ($pdo ?? null);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CSRF token global en session (uniquement si absent)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Passerelle GET -> POST (autopost)
 * On génère en plus un token "one-shot" lié à (id|status) pour éviter
 * tout souci de synchro rare entre le token rendu et la session.
 */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['id'], $_GET['status'])) {
  $id   = (int)$_GET['id'];
  $st   = (string)$_GET['status'];
  $back = (string)($_GET['back'] ?? 'manage_employees.php');

  // token global
  $token = $_SESSION['csrf_token'];

  // token one-shot lié à cette action
  if (!isset($_SESSION['csrf_once'])) $_SESSION['csrf_once'] = [];
  $key  = $id . '|' . $st . '|employee_status';
  $once = bin2hex(random_bytes(16));
  $_SESSION['csrf_once'][$key] = $once;

  $self  = htmlspecialchars($_SERVER['PHP_SELF'] ?? '/update_employee_status.php', ENT_QUOTES, 'UTF-8');
  $hId   = (int)$id;
  $hSt   = htmlspecialchars($st, ENT_QUOTES, 'UTF-8');
  $hBack = htmlspecialchars($back, ENT_QUOTES, 'UTF-8');
  $hTok  = htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
  $hOnce = htmlspecialchars($once,  ENT_QUOTES, 'UTF-8');
  ?>
  <!doctype html><meta charset="utf-8">
  <form id="autopost" method="post" action="<?=$self?>">
    <input type="hidden" name="id" value="<?=$hId?>">
    <input type="hidden" name="status" value="<?=$hSt?>">
    <input type="hidden" name="back" value="<?=$hBack?>">
    <input type="hidden" name="csrf_token" value="<?=$hTok?>">
    <input type="hidden" name="csrf_once"  value="<?=$hOnce?>">
  </form>
  <script>document.getElementById('autopost').submit();</script>
  <noscript><button form="autopost" type="submit">Continuer</button></noscript>
  <?php
  exit;
}

// POST + CSRF
$back = (string)($_POST['back'] ?? 'manage_employees.php');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  redirect($back, ['error' => 'method']);
}

// Validation des tokens
$posted = (string)($_POST['csrf_token'] ?? '');
$once   = (string)($_POST['csrf_once']  ?? '');
$sess   = (string)($_SESSION['csrf_token'] ?? '');

$key    = (string)($_POST['id'] ?? '') . '|' . (string)($_POST['status'] ?? '') . '|employee_status';
$onceOk = isset($_SESSION['csrf_once'][$key]) && hash_equals($_SESSION['csrf_once'][$key], $once);

// On accepte soit le token de session valide, soit le one-shot valide
if (!( $posted && hash_equals($sess, $posted) ) && !$onceOk) {
  redirect($back, ['error' => 'csrf']);
}
// Consommer le one-shot (idempotence)
if ($onceOk) {
  unset($_SESSION['csrf_once'][$key]);
}

// Params
$empId   = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status  = (string)($_POST['status'] ?? '');
$allowed = ['active', 'suspended'];

if (!$empId || !in_array($status, $allowed, true)) {
  redirect($back, ['error' => 'params']);
}

try {
  $u = $pdo->prepare("UPDATE users SET etat = ? WHERE id = ? AND role = 'employe'");
  $u->execute([$status, $empId]);

  redirect($back, ['success' => 1]);
} catch (Throwable $e) {
  // error_log($e->getMessage());
  redirect($back, ['error' => 1]);
}
