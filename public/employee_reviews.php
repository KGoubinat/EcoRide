<?php
// employee_reviews.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()

// Page privée : empêcher l’indexation
header('X-Robots-Tag: noindex, nofollow', true);

// Autorisation : employé uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employe') {
    header('Location: ' . BASE_URL . 'home.php');
    exit;
}

// CSRF token (réutilise si déjà présent)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

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
  <title>EcoRide - Gérer les Avis</title>

  <!-- base dynamique -->
  <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">

  <!-- SEO (Lighthouse OK, mais page privée) -->
  <meta name="description" content="Interface employé EcoRide pour modérer les avis : filtrer par statut, consulter les notes et approuver ou rejeter les commentaires.">
  <meta name="robots" content="noindex, nofollow">

  <!-- Canonical sans paramètres ?status= -->
  <?php $canonical = rtrim((string)BASE_URL, '/').'/employee_reviews.php'; ?>
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
        <li><a href="employee_reviews.php" aria-current="page">Gérer les Avis</a></li>
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
      <li>
        <form action="../backend/handlers/logout.php" method="POST" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <button type="submit" class="linklike">Déconnexion</button>
        </form>

      </li>
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
                <form action="../backend/handlers/approve_review.php" method="POST" style="display:inline" onsubmit="return confirm('Approuver cet avis ?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="status" value="approved">
                  <button type="submit" class="btn btn-success">Valider</button>
                </form>
                <span> | </span>
                <form action="../backend/handlers/approve_review.php" method="POST" style="display:inline" onsubmit="return confirm('Refuser cet avis ?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <input type="hidden" name="status" value="rejected">
                  <button type="submit" class="btn btn-warning">Refuser</button>
                </form>
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
            <span>EcoRide@gmail.com</span>
            <span>|</span>
            <a href="legal_notice.php">Mentions légales</a>
        </div>
    </footer>

<style>
/* bouton qui ressemble à un lien pour logout */
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

<script src="assets/js/cookie_consent.js" defer></script>
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
