<?php
// report_problem.php

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()

// Bloquer l’indexation (page privée)
header('X-Robots-Tag: noindex, nofollow', true);
header('Content-Type: text/html; charset=utf-8');

// Redirige vers la page de connexion si non connecté (et revient ensuite)
if (empty($_SESSION['user_id'])) {
    // on réutilise la même convention que partout ailleurs: connexion.php?redirect=...
    $redirect = 'report_problem.php?ride_id=' . urlencode((string)($_GET['ride_id'] ?? ''));
    header('Location: ' . BASE_URL . 'connexion.php?redirect=' . urlencode($redirect));
    exit;
}

$pdo = getPDO();

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES);

// Paramètre requis
$ride_id = filter_input(INPUT_GET, 'ride_id', FILTER_VALIDATE_INT);
if (!$ride_id) {
    header('Location: ' . BASE_URL . 'employee_troublesome_rides.php?error=params');
    exit;
}

// Récup détails du covoiturage (pour affichage)
$stmt = $pdo->prepare('
    SELECT id, depart, destination, date, heure_depart, heure_arrivee
    FROM covoiturages
    WHERE id = ?
');
$stmt->execute([$ride_id]);
$ride = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ride) {
    header('Location: ' . BASE_URL . 'employee_troublesome_rides.php?error=ride');
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Signaler un problème</title>
  <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">
  <meta name="robots" content="noindex, nofollow">
</head>
<body>
  <main class="covoit" style="max-width:700px;margin:24px auto;">
    <h1>Signaler un problème</h1>
    <p>
      Trajet <strong><?= htmlspecialchars($ride['depart']) ?> → <?= htmlspecialchars($ride['destination']) ?></strong>
      le <strong><?= htmlspecialchars($ride['date']) ?></strong>
      à <strong><?= htmlspecialchars($ride['heure_depart']) ?></strong>.
    </p>

    <form method="post" action="../backend/handlers/create_troublesome_ride.php">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="ride_id" value="<?= (int)$ride_id ?>">

      <label for="comment">Décrivez le problème :</label><br>
      <textarea id="comment" name="comment" rows="5" required
        placeholder="Ex. : conducteur absent, comportement dangereux, retard important, …"
        style="width:100%;max-width:100%;margin:8px 0;"></textarea><br>

      <button type="submit">Envoyer le signalement</button>
    </form>
  </main>
</body>
</html>
