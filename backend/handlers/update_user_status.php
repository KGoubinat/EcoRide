<?php
// update_user_status.php
declare(strict_types=1);

require __DIR__ . '/../../public/init.php';

function redirect(string $path, array $qs = []): never {
  $base = rtrim(BASE_URL, '/') . '/';
  if ($qs) $path .= (strpos($path,'?')===false?'?':'&') . http_build_query($qs);
  header('Location: ' . $base . ltrim($path, '/'));
  exit;
}

// Admin uniquement
if (($_SESSION['user_role'] ?? null) !== 'administrateur') {
  redirect('accueil.php');
}

$pdo = getPDO();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CSRF (token de session créé une fois)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Passerelle GET -> POST (autopost) avec token one-shot
 */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['id'], $_GET['status'])) {
  $id   = (int)$_GET['id'];
  $st   = (string)$_GET['status'];
  $back = (string)($_GET['back'] ?? 'manage_users.php');

  // Token global + one-shot
  $token = $_SESSION['csrf_token'];
  if (!isset($_SESSION['csrf_once'])) $_SESSION['csrf_once'] = [];
  $key  = $id . '|' . $st . '|user_status';
  $once = bin2hex(random_bytes(16));
  $_SESSION['csrf_once'][$key] = $once;

  $self  = htmlspecialchars($_SERVER['PHP_SELF'] ?? '/update_user_status.php', ENT_QUOTES, 'UTF-8');
  $hId   = $id;
  $hSt   = htmlspecialchars($st,   ENT_QUOTES, 'UTF-8');
  $hBack = htmlspecialchars($back, ENT_QUOTES, 'UTF-8');
  $hTok  = htmlspecialchars($token,ENT_QUOTES, 'UTF-8');
  $hOnce = htmlspecialchars($once, ENT_QUOTES, 'UTF-8');
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

// ---------- Phase POST ----------
$back = (string)($_POST['back'] ?? 'manage_users.php');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  redirect($back, ['error'=>'method']);
}

// CSRF : accepter soit le token global, soit le one-shot
$posted = (string)($_POST['csrf_token'] ?? '');
$once   = (string)($_POST['csrf_once']  ?? '');
$sess   = (string)($_SESSION['csrf_token'] ?? '');

$key    = (string)($_POST['id'] ?? '') . '|' . (string)($_POST['status'] ?? '') . '|user_status';
$onceOk = isset($_SESSION['csrf_once'][$key]) && hash_equals($_SESSION['csrf_once'][$key], $once);

if (!( $posted && hash_equals($sess, $posted) ) && !$onceOk) {
  redirect($back, ['error'=>'csrf']);
}
if ($onceOk) unset($_SESSION['csrf_once'][$key]); // consommer le one-shot

// Paramètres
// Params robustes
$uidRaw  = $_POST['id'] ?? null;
$status  = strtolower(trim((string)($_POST['status'] ?? '')));
$allowed = ['active', 'suspended'];

$uid = is_numeric($uidRaw) ? (int)$uidRaw : 0;

if ($uid <= 0) {
  redirect($back, ['error' => 'params_id']);
}
if (!in_array($status, $allowed, true)) {
  redirect($back, ['error' => 'params_status']);
}


// Option sécurité : empêcher de changer son propre compte
if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === (int)$uid) {
  redirect($back, ['error'=>'self']);
}

try {
  // Vérifier l'existence de l’utilisateur
  $s = $pdo->prepare("SELECT id FROM users WHERE id = ?");
  $s->execute([$uid]);
  if (!$s->fetchColumn()) {
    redirect($back, ['error'=>'notfound']);
  }

  // Mise à jour du statut (sans restriction de rôle)
  $u = $pdo->prepare("UPDATE users SET etat = ? WHERE id = ?");
  $u->execute([$status, $uid]);

  redirect($back, ['success'=>1]);

} catch (Throwable $e) {
  // error_log($e->getMessage());
  redirect($back, ['error'=>1]);
}
