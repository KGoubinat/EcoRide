<?php
// employee_reviews.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()

// Page privée : empêcher l’indexation
header('X-Robots-Tag: noindex, nofollow', true);

// Autorisation : employé uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employe') {
    header('Location: ' . BASE_URL . 'accueil.php');
    exit;
}

$pdo = getPDO();

// ---- Filtre par statut (pending par défaut) ----
$validStatuses = ['pending', 'approved', 'rejected', 'all'];
$filter = $_GET['status'] ?? 'pending';
if (!in_array($filter, $validStatuses, true)) {
    $filter = 'pending';
}

// ---- Construire la requête en fonction du filtre ----
$where  = '';
$params = [];
if ($filter !== 'all') {
    $where   = 'WHERE r.status = ?';
    $params[] = $filter;
}

$sql = "
    SELECT r.id, r.user_id, r.driver_id, r.rating, r.comment, r.status,
           u.firstName AS user_firstname, u.lastName AS user_lastname,
           d.firstName AS driver_firstname, d.lastName AS driver_lastname
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN users d ON r.driver_id = d.id
    $where
    ORDER BY r.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll() ?: [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gérer les Avis | Espace Employé EcoRide</title>

  <!-- base dynamique -->
  <base href="<?= htmlspecialchars(rtrim(BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="styles.css">

  <!-- SEO (Lighthouse OK, mais page privée) -->
  <meta name="description" content="Interface employé EcoRide pour modérer les avis : filtrer par statut, consulter les notes et approuver ou rejeter les commentaires.">
  <meta name="robots" content="noindex, nofollow">

  <!-- Canonical sans paramètres ?status= -->
  <?php $canonical = rtrim(BASE_URL, '/').'/employee_reviews.php'; ?>
  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">

  <!-- (Facultatif) Open Graph -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="Gérer les Avis | Espace Employé EcoRide">
  <meta property="og:description" content="Modération des avis : filtrer, approuver, rejeter.">
  <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
</head>
<body>
<header>
  <div class="header-container">
    <h1>Gérer les Avis</h1>
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
  <section class="validation">
    <h2>Avis</h2>

    <!-- Flash messages (conservés) -->
    <?php if (isset($_GET['success'])): ?>
      <p class="flash flash-success">✔ Avis traité avec succès.</p>
    <?php elseif (isset($_GET['error'])): ?>
      <p class="flash flash-error">✖ Une erreur est survenue lors du traitement.</p>
    <?php elseif (isset($_GET['info']) && $_GET['info']==='already_moderated'): ?>
      <p class="flash flash-info">ℹ Cet avis avait déjà été modéré.</p>
    <?php endif; ?>

    <!-- Menu déroulant filtre statut -->
    <form method="get" action="employee_reviews.php" class="filters">
      <label for="status">Filtrer par statut :</label>
      <select name="status" id="status" onchange="this.form.submit()">
        <option value="pending"  <?= $filter==='pending' ? 'selected' : '' ?>>En attente</option>
        <option value="approved" <?= $filter==='approved' ? 'selected' : '' ?>>Approuvés</option>
        <option value="rejected" <?= $filter==='rejected' ? 'selected' : '' ?>>Rejetés</option>
        <option value="all"      <?= $filter==='all' ? 'selected' : '' ?>>Tous</option>
      </select>
      <noscript><button type="submit">Appliquer</button></noscript>
    </form>

    <?php if (empty($reviews)): ?>
      <p>Aucun avis trouvé.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Utilisateur</th>
            <th>Chauffeur</th>
            <th>Note</th>
            <th>Commentaire</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($reviews as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars(($r['user_firstname'] ?? '') . ' ' . ($r['user_lastname'] ?? ''), ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars(($r['driver_firstname'] ?? '') . ' ' . ($r['driver_lastname'] ?? ''), ENT_QUOTES) ?></td>
            <td><?= (int)$r['rating'] ?>/5</td>
            <td><?= htmlspecialchars((string)$r['comment'], ENT_QUOTES) ?></td>
            <td>
              <?php
                $st = (string)$r['status'];
                $pillClass = 'status-pill status-' . htmlspecialchars($st, ENT_QUOTES);
                echo '<span class="'.$pillClass.'">'.htmlspecialchars($st, ENT_QUOTES).'</span>';
              ?>
            </td>
            <td>
              <?php if ($r['status'] === 'pending'): ?>
                <a href="approve_review.php?id=<?= (int)$r['id'] ?>&status=approved"
                   onclick="return confirm('Approuver cet avis ?');">Valider</a>
                |
                <a href="approve_review.php?id=<?= (int)$r['id'] ?>&status=rejected"
                   onclick="return confirm('Refuser cet avis ?');">Refuser</a>
              <?php else: ?>
                <em>—</em>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
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
