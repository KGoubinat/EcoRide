<?php
// employee_troublesome_rides.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()

// Page privée : empêcher l’indexation (HTTP)
header('X-Robots-Tag: noindex, nofollow', true);

// Autorisation : employé uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employe') {
    header('Location: ' . BASE_URL . 'accueil.php');
    exit;
}

// CSRF (réutilise si déjà présent)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$pdo = getPDO();

// (Optionnel) filtre par statut ?status=open|resolved|all
$status = $_GET['status'] ?? 'all';
$allowed = ['all','open','resolved'];
if (!in_array($status, $allowed, true)) { $status = 'all'; }

// (Optionnel) pagination simple
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($status !== 'all') {
    $where = 'WHERE tr.status = ?';
    $params[] = $status;
}

// Compter pour pagination
$countSql = "SELECT COUNT(*) FROM troublesome_rides tr $where";
$cnt = $pdo->prepare($countSql);
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();

// Récupérer les signalements
$sql = "
    SELECT 
        tr.id            AS troublesome_id,
        tr.comment       AS comment,
        tr.status        AS status,
        tr.created_at    AS created_at,
        c.id             AS ride_id, 
        c.date           AS ride_date,
        c.depart         AS departure_location,
        c.destination    AS arrival_location,
        c.heure_depart   AS departure_time,
        c.heure_arrivee  AS arrival_time,
        u1.firstName     AS user_first_name, 
        u1.lastName      AS user_last_name, 
        u1.email         AS user_email,
        u2.firstName     AS driver_first_name, 
        u2.lastName      AS driver_last_name, 
        u2.email         AS driver_email
    FROM troublesome_rides tr
    JOIN covoiturages c ON tr.ride_id = c.id
    JOIN users u1 ON tr.user_id = u1.id
    JOIN users u2 ON tr.driver_id = u2.id
    $where
    ORDER BY tr.created_at DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$troublesomeRides = $stmt->fetchAll() ?: [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>

  <title>EcoRide - Covoiturages Problématiques</title>

  <!-- base dynamique -->
  <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">

  <!-- SEO (page privée) -->
  <meta name="description" content="Interface employé pour consulter et traiter les covoiturages signalés : filtrer par statut, voir les détails et marquer comme résolus.">
  <meta name="robots" content="noindex, nofollow">
  <?php $canonical = rtrim((string)BASE_URL, '/').'/employee_troublesome_rides.php'; ?>
  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
</head>

<body>
<header>
  <div class="header-container">
    <h1>Covoiturages Problématiques</h1>
    <div class="menu-toggle" id="menu-toggle">☰</div>
    <nav id="navbar">
      <ul>
        <li><a href="employee_dashboard.php">Tableau de bord</a></li>
        <li><a href="employee_reviews.php">Gérer les Avis</a></li>
        <li><a href="employee_troublesome_rides.php" aria-current="page">Covoiturages Problématiques</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
      </ul>
    </nav>
  </div>
  <nav id="mobile-menu">
    <ul>
      <li><a href="employee_dashboard.php">Tableau de bord</a></li>
      <li><a href="employee_reviews.php">Gérer les Avis</a></li>
      <li><a href="employee_troublesome_rides.php">Covoiturages Problématiques</a></li>
      <li>
        <form action="../backend/handlers/deconnexion.php" method="POST" style="display:inline">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <button type="submit" class="linklike">Déconnexion</button>
        </form>
      </li>
    </ul>
  </nav>
</header>

<main class="covoit">
  <div class="troublesome-rides-list">
    <h2>Signalements</h2>

    <?php if (isset($_GET['success'])): ?>
      <p class="flash flash-success">✔ Statut mis à jour.</p>
    <?php elseif (isset($_GET['error'])): ?>
      <p class="flash flash-error">✖ Erreur lors de la mise à jour.</p>
    <?php endif; ?>

    <form method="get" style="margin-bottom:1rem;">
      <label for="status">Filtrer par statut :</label>
      <select name="status" id="status" onchange="this.form.submit()">
        <option value="all"     <?= $status==='all'?'selected':'' ?>>Tous</option>
        <option value="open"    <?= $status==='open'?'selected':'' ?>>Ouverts</option>
        <option value="resolved"<?= $status==='resolved'?'selected':'' ?>>Résolus</option>
      </select>
    </form>

    <?php if ($troublesomeRides): ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>ID Covoiturage</th>
            <th>Participant</th>
            <th>Conducteur</th>
            <th>Date</th>
            <th>Départ</th>
            <th>Arrivée</th>
            <th>Heure Dép.</th>
            <th>Heure Arr.</th>
            <th>Commentaire</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($troublesomeRides as $ride): ?>
          <?php
            $back = 'employee_troublesome_rides.php?status=' . urlencode($status) . '&page=' . (int)$page;
            $isOpen = (($ride['status'] ?? '') === 'open');
            $targetStatus = $isOpen ? 'resolved' : 'open';
            $btnLabel = $isOpen ? 'Marquer résolu' : 'Réouvrir';
            $confirmMsg = $isOpen ? 'Marquer comme résolu ?' : 'Réouvrir ce signalement ?';
          ?>
          <tr>
            <td><?= (int)$ride['troublesome_id'] ?></td>
            <td><?= (int)$ride['ride_id'] ?></td>
            <td>
              <?= htmlspecialchars(($ride['user_first_name'] ?? '').' '.($ride['user_last_name'] ?? ''), ENT_QUOTES) ?><br>
              <small><?= htmlspecialchars($ride['user_email'] ?? '', ENT_QUOTES) ?></small>
            </td>
            <td>
              <?= htmlspecialchars(($ride['driver_first_name'] ?? '').' '.($ride['driver_last_name'] ?? ''), ENT_QUOTES) ?><br>
              <small><?= htmlspecialchars($ride['driver_email'] ?? '', ENT_QUOTES) ?></small>
            </td>
            <td>
              <?= htmlspecialchars($ride['ride_date'] ?? '', ENT_QUOTES) ?><br>
              <small><?= htmlspecialchars($ride['created_at'] ?? '', ENT_QUOTES) ?></small>
            </td>
            <td><?= htmlspecialchars($ride['departure_location'] ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($ride['arrival_location'] ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($ride['departure_time'] ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($ride['arrival_time'] ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($ride['comment'] ?? '', ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($ride['status'] ?? '', ENT_QUOTES) ?></td>
            <td>
              <form action="../backend/handlers/update_troublesome_ride.php" method="POST" style="display:inline" onsubmit="return confirm('<?= $confirmMsg ?>');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" value="<?= (int)$ride['troublesome_id'] ?>">
                <input type="hidden" name="status" value="<?= htmlspecialchars($targetStatus, ENT_QUOTES) ?>">
                <input type="hidden" name="back" value="<?= htmlspecialchars($back, ENT_QUOTES) ?>">
                <button type="submit" class="btn"><?= $btnLabel ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <?php
        $pages = max(1, (int)ceil($total / $perPage));
        if ($pages > 1):
      ?>
      <nav style="margin-top:1rem; display:flex; gap:.5rem; flex-wrap:wrap;">
        <?php for ($p = 1; $p <= $pages; $p++):
          $link = 'employee_troublesome_rides.php?status='.urlencode($status).'&page='.$p; ?>
          <a href="<?= $link ?>" <?= $p===$page ? 'style="font-weight:bold;text-decoration:underline;"':'' ?>>
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </nav>
      <?php endif; ?>

    <?php else: ?>
      <p>Aucun covoiturage problématique n'a été signalé pour le moment.</p>
    <?php endif; ?>
  </div>
</main>

<footer>
        <div class="footer-links">
            <a href="#" id="open-cookie-modal">Gérer mes cookies</a>
            <span>|</span>
            <span>EcoRide@gmail.com</span>
            <span>|</span>
            <a href="mentions_legales.php">Mentions légales</a>
        </div>
    </footer>

<style>
.linklike { background:none; border:none; padding:0; margin:0; cursor:pointer; color:inherit; font:inherit; text-decoration:underline; }
</style>

<!-- Overlay bloquant -->
  <div id="cookie-blocker" class="cookie-blocker" hidden></div>
    <!-- Bandeau cookies -->
    <div id="cookie-banner" class="cookie-banner" hidden>
    <div class="cookie-content">
        <p>Nous utilisons des cookies pour améliorer votre expérience, mesurer l’audience et proposer des contenus personnalisés.</p>
        <div class="cookie-actions">
        <button data-action="accept-all" type="button">Tout accepter</button>
        <button data-action="reject-all" type="button">Tout refuser</button>
        <button data-action="customize"  type="button">Personnaliser</button>
        </div>
    </div>
    </div>
<!-- Centre de préférences -->
    <div id="cookie-modal" class="cookie-modal" hidden>
    <div class="cookie-modal-card">
        <h3>Préférences de cookies</h3>
        <label><input type="checkbox" checked disabled> Essentiels (toujours actifs)</label><br>
        <label><input type="checkbox" id="consent-analytics"> Mesure d’audience</label><br>
        <label><input type="checkbox" id="consent-marketing"> Marketing</label>
        <div class="cookie-modal-actions">
        <button data-action="save"  type="button">Enregistrer</button>
        <button data-action="close" type="button">Fermer</button>
        </div>
    </div>
    </div>
 
<script src="assets/js/cookie-consent.js" defer></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const menuToggle = document.getElementById("menu-toggle");
  const mobileMenu = document.getElementById("mobile-menu");
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener("click", () => mobileMenu.classList.toggle("active"));
    document.querySelectorAll("#mobile-menu a").forEach(link =>
      link.addEventListener("click", () => mobileMenu.classList.remove("active"))
    );
  }
});
</script>
</body>
</html>
