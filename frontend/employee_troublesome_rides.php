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

  <title>Covoiturages problématiques | Espace Employé EcoRide</title>

  <!-- base dynamique -->
  <base href="<?= htmlspecialchars(rtrim(BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="styles.css">

  <!-- SEO (Lighthouse OK, mais page privée) -->
  <meta name="description" content="Interface employé pour consulter et traiter les covoiturages signalés : filtrer par statut, voir les détails et marquer comme résolus.">
  <meta name="robots" content="noindex, nofollow">

  <!-- Canonical sans paramètres ?status=&page= -->
  <?php $canonical = rtrim(BASE_URL, '/').'/employee_troublesome_rides.php'; ?>
  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">

  <!-- (Facultatif) Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="Covoiturages problématiques | Espace Employé EcoRide">
  <meta property="og:description" content="Consultez et résolvez les signalements de covoiturages.">
  <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
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
        <li><a href="employee_troublesome_rides.php">Covoiturages Problématiques</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
      </ul>
    </nav>
  </div>
  <nav id="mobile-menu">
    <ul>
      <li><a href="employee_dashboard.php">Tableau de bord</a></li>
      <li><a href="employee_reviews.php">Gérer les Avis</a></li>
      <li><a href="employee_troublesome_rides.php">Covoiturages Problématiques</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
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
              <?php
                // on reconstruit un "back" propre pour revenir au bon filtre/page
                $back = 'employee_troublesome_rides.php?status=' . urlencode($status) . '&page=' . (int)$page;
                if (($ride['status'] ?? '') === 'open') {
                  $url = 'update_troublesome_ride.php?id='.(int)$ride['troublesome_id'].'&status=resolved&back='.urlencode($back);
                  echo '<a href="'.$url.'" onclick="return confirm(\'Marquer comme résolu ?\');">Marquer résolu</a>';
                } else {
                  $url = 'update_troublesome_ride.php?id='.(int)$ride['troublesome_id'].'&status=open&back='.urlencode($back);
                  echo '<a href="'.$url.'" onclick="return confirm(\'Réouvrir ce signalement ?\');">Réouvrir</a>';
                }
              ?>
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
            <span>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></span>
        </div>
    </footer>

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
