<?php
// manage_users.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()

// Bloquer l’indexation par les moteurs
header('X-Robots-Tag: noindex, nofollow', true);

// Admin uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'home.php');
    exit;
}

// CSRF (une fois par session)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$pdo = getPDO();

// Filtres simples
$search = trim((string)($_GET['q'] ?? ''));
$role   = (string)($_GET['role'] ?? 'utilisateur'); // ''|utilisateur|employe|administrateur
$etat   = (string)($_GET['etat'] ?? '');            // ''|active|suspendu

$params = [];
$where  = 'WHERE 1=1';

if ($search !== '') {
    $where .= " AND (CONCAT(firstName, ' ', lastName) LIKE ? OR email LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like; $params[] = $like;
}
if ($role === '') { $role = 'utilisateur'; }
if (in_array($role, ['utilisateur','employe','administrateur'], true)) {
    $where .= " AND role = ?";
    $params[] = $role;
}
if ($etat !== '' && in_array($etat, ['active','suspendu'], true)) {
    $where .= " AND etat = ?";
    $params[] = $etat;
}

$sql = "SELECT id, firstName, lastName, email, role, etat
        FROM users
        $where
        ORDER BY lastName ASC, firstName ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll() ?: [];

// back pour revenir avec les mêmes filtres
$qs = [];
if ($search !== '') $qs['q'] = $search;
if ($role   !== '') $qs['role'] = $role;
if ($etat   !== '') $qs['etat'] = $etat;
$back = 'manage_users.php' . ($qs ? ('?' . http_build_query($qs)) : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EcoRide - Gérer les Utilisateurs</title>

  <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">

  <!-- SEO : page privée -->
  <meta name="description" content="Interface d’administration EcoRide pour rechercher, filtrer, activer ou suspendre les comptes utilisateurs.">
  <meta name="robots" content="noindex, nofollow">
  <?php $canonical = rtrim((string)BASE_URL, '/').'/manage_users.php'; ?>
  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
</head>
<body>
<header>
  <div class="header-container">
    <h1>Gestion des utilisateurs</h1>
    <div class="menu-toggle" id="menu-toggle">☰</div>
    <nav id="navbar">
      <ul>
        <li><a href="admin_dashboard.php">Tableau de bord</a></li>
        <li><a href="add_employee.php">Ajouter un Employé</a></li>
        <li><a href="manage_employees.php">Gérer les Employés</a></li>
        <li><a href="manage_users.php" aria-current="page">Gérer les Utilisateurs</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
      </ul>
    </nav>
  </div>
  <nav id="mobile-menu">
    <ul>
      <li><a href="admin_dashboard.php">Tableau de bord</a></li>
      <li><a href="add_employee.html">Ajouter un Employé</a></li>
      <li><a href="manage_employees.php">Gérer les Employés</a></li>
      <li><a href="manage_users.php" aria-current="page">Gérer les Utilisateurs</a></li>
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
  <section class="manageUsers">
    <h2>Gérer les Utilisateurs</h2>

    <form method="get" class="filter-form" id="filterForm">
      <div>
        <label for="q">Recherche</label><br>
        <input type="text" id="q" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Nom ou email">
      </div>
      <div>
        <label for="etat">État</label><br>
        <select id="etat" name="etat">
          <option value="" <?= $etat===''?'selected':''; ?>>Tous</option>
          <option value="active"   <?= $etat==='active'?'selected':''; ?>>Actif</option>
          <option value="suspendu" <?= $etat==='suspendu'?'selected':''; ?>>Suspendu</option>
        </select>
      </div>
      <!-- on garde le rôle en GET (défaut utilisateur) pour construire $back -->
      <input type="hidden" name="role" value="<?= htmlspecialchars($role, ENT_QUOTES) ?>">
    </form>

    <h3>Liste des Utilisateurs</h3>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$users): ?>
          <tr><td colspan="6">Aucun utilisateur trouvé.</td></tr>
        <?php else: ?>
          <?php foreach ($users as $row): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><?= htmlspecialchars($row['firstName'] . ' ' . $row['lastName'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($row['email'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($row['role'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars(ucfirst((string)$row['etat']), ENT_QUOTES) ?></td>
              <td>
                <?php if (($row['etat'] ?? '') === 'active'): ?>
                  <form action="../backend/handlers/update_user_status.php" method="POST" style="display:inline" onsubmit="return confirm('Suspendre cet utilisateur ?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <input type="hidden" name="status" value="suspended">
                    <input type="hidden" name="back" value="<?= htmlspecialchars($back, ENT_QUOTES) ?>">
                    <button type="submit" class="btn btn-warning">Suspendre</button>
                  </form>
                <?php else: ?>
                  <form action="../backend/handlers/update_user_status.php" method="POST" style="display:inline" onsubmit="return confirm('Activer cet utilisateur ?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <input type="hidden" name="status" value="active">
                    <input type="hidden" name="back" value="<?= htmlspecialchars($back, ENT_QUOTES) ?>">
                    <button type="submit" class="btn btn-success">Activer</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
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


<style>
.linklike { background:none; border:none; padding:0; margin:0; cursor:pointer; color:inherit; font:inherit; text-decoration:underline; }
</style>

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

    const form = document.getElementById("filterForm");
    document.getElementById("q").addEventListener("input", () => form.submit());
    document.getElementById("etat").addEventListener("change", () => form.submit());
  }
});
</script>
</body>
</html>
