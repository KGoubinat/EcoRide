<?php
// report_problem.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()

// Redirige vers login si non connecté (on revient ensuite)
if (empty($_SESSION['user_id'])) {
  $next = 'report_problem.php?ride_id=' . urlencode((string)($_GET['ride_id'] ?? ''));
  header('Location: ' . BASE_URL . 'login.php?next=' . urlencode($next)); exit;
}

$pdo = function_exists('getPDO') ? getPDO() : ($pdo ?? null);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$ride_id = filter_input(INPUT_GET, 'ride_id', FILTER_VALIDATE_INT);
if (!$ride_id) {
  header('Location: ' . BASE_URL . 'employee_troublesome_rides.php?error=params'); exit;
}

// Récup détails du covoiturage (pour affichage)
$stmt = $pdo->prepare('SELECT id, depart, destination, date, heure_depart, heure_arrivee FROM covoiturages WHERE id = ?');
$stmt->execute([$ride_id]);
$ride = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ride) {
  header('Location: ' . BASE_URL . 'employee_troublesome_rides.php?error=ride'); exit;
}

$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Signaler un problème</title>
  <base href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <main class="covoit" style="max-width:700px;margin:24px auto;">
    <h1>Signaler un problème</h1>
    <p>
      Trajet <strong><?= htmlspecialchars($ride['depart']) ?> → <?= htmlspecialchars($ride['destination']) ?></strong>
      le <strong><?= htmlspecialchars($ride['date']) ?></strong>
      à <strong><?= htmlspecialchars($ride['heure_depart']) ?></strong>.
    </p>
    <form method="post" action="create_troublesome_ride.php">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="ride_id" value="<?= (int)$ride_id ?>">
      <label for="comment">Décrivez le problème :</label><br>
      <textarea id="comment" name="comment" rows="5" required
        placeholder="Ex.: conducteur absent, comportement dangereux, retard important, ... "
        style="width:100%;max-width:100%;margin:8px 0;"></textarea><br>
      <button type="submit">Envoyer le signalement</button>
    </form>
  </main>
</body>
</html>
