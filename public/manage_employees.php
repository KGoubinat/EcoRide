<?php
// manage_employees.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()
header('X-Robots-Tag: noindex, nofollow', true);

// Admin uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'home.php');
    exit;
}

// CSRF (généré une fois par session)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$pdo = getPDO();

// Recherche par nom/email
$search = trim((string)($_GET['q'] ?? ''));
$params = [];
$where  = "WHERE role = 'employe'";
if ($search !== '') {
    $where .= " AND (CONCAT(firstName, ' ', lastName) LIKE ? OR email LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$sql = "SELECT id, firstName, lastName, email, role, etat
        FROM users
        $where
        ORDER BY lastName ASC, firstName ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll() ?: [];

// Back param pour revenir sur le même écran après action
$back = 'manage_employees.php' . ($search !== '' ? ('?q='.urlencode($search)) : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EcoRide - Gestion des Employés</title>

  <!-- base dynamique (local & prod) -->
  <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">

  <!-- Page privée -->
  <meta name="description" content="Administration des comptes employés : recherche, activation/suspension et modification.">
  <meta name="robots" content="noindex, nofollow">
</head>
<body>
<header>
  <div class="header-container">
    <h1>Gestion des Employés</h1>
    <div class="menu-toggle" id="menu-toggle">☰</div>
    <nav id="navbar">
      <ul>
        <li><a href="admin_dashboard.php">Tableau de bord</a></li>
        <li><a href="add_employee.php">Ajouter un Employé</a></li>
        <li><a href="manage_employees.php" aria-current="page">Gérer les Employés</a></li>
        <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
      </ul>
    </nav>
  </div>
  <nav id="mobile-menu">
    <ul>
      <li><a href="admin_dashboard.php">Tableau de bord</a></li>
      <li><a href="add_employee.php">Ajouter un Employé</a></li>
      <li><a href="manage_employees.php" aria-current="page">Gérer les Employés</a></li>
      <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
      <li>
        <form action="../backend/handlers/delogin.php" method="POST" style="display:inline">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <button type="submit" class="linklike">Déconnexion</button>
        </form>
      </li>
    </ul>
  </nav>
</header>

<main class="covoit">
  <section class="listEmployee">
    <h2>Liste des Employés</h2>


    <form id="searchForm" method="get" style="margin:.5rem 0 1rem; display:flex; gap:.5rem; align-items:center;">
      <input type="text" id="searchInput" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Rechercher (nom ou email)" />
      <?php if ($search !== ''): ?>
        <a href="manage_employees.php" style="margin-left:.5rem;">Réinitialiser</a>
      <?php endif; ?>
    </form>

    <div id="resultsContainer" class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>État</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$employees): ?>
          <tr><td colspan="6">Aucun employé trouvé.</td></tr>
        <?php else: ?>
          <?php foreach ($employees as $row): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><?= htmlspecialchars($row['firstName'] . ' ' . $row['lastName'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($row['email'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($row['role'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars(ucfirst((string)$row['etat']), ENT_QUOTES) ?></td>
              <td>
                <?php if (($row['etat'] ?? '') === 'active'): ?>
                  <form action="../backend/handlers/update_employee_status.php" method="POST" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <input type="hidden" name="status" value="suspended">
                    <input type="hidden" name="back" value="<?= htmlspecialchars($back, ENT_QUOTES) ?>">
                    <button type="submit" class="btn btn-warning">Suspendre</button>
                  </form>
                <?php else: ?>
                  <form action="../backend/handlers/update_employee_status.php" method="POST" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <input type="hidden" name="status" value="active">
                    <input type="hidden" name="back" value="<?= htmlspecialchars($back, ENT_QUOTES) ?>">
                    <button type="submit" class="btn btn-success">Activer</button>
                  </form>
                <?php endif; ?>
                | <button onclick="location.href='edit_employee.php?id=<?= (int)$row['id'] ?>'">Modifier</button>
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

<style>
.linklike { background:none; border:none; padding:0; margin:0; cursor:pointer; color:inherit; font:inherit; text-decoration:underline; }
</style>

<!-- Modale de confirmation EcoRide -->
<div id="confirm-modal" class="eco-modal" hidden>
  <div class="eco-modal-content">
    <h3 id="confirm-title">Confirmer l’action</h3>
    <p id="confirm-message">Voulez-vous vraiment effectuer cette action ?</p>
    <div class="eco-modal-actions">
      <button id="confirm-yes" class="btn">Confirmer</button>
      <button id="confirm-no" class="btn button--ghost">Annuler</button>
    </div>
  </div>
</div>
<div id="confirm-overlay" class="eco-modal-overlay" hidden></div>

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
document.addEventListener("DOMContentLoaded", () => {
  const results = document.getElementById("resultsContainer");
  const modal = document.getElementById("confirm-modal");
  const overlay = document.getElementById("confirm-overlay");
  const yesBtn = document.getElementById("confirm-yes");
  const noBtn = document.getElementById("confirm-no");
  const msg = document.getElementById("confirm-message");

  let pendingForm = null;

  // afficher modale
  function openModal(message, form) {
    msg.textContent = message;
    modal.hidden = false;
    overlay.hidden = false;
    pendingForm = form;
  }

  function closeModal() {
    modal.hidden = true;
    overlay.hidden = true;
    pendingForm = null;
  }

  yesBtn.addEventListener("click", () => {
    if (pendingForm) {
      pendingForm.submit();
      closeModal();
    }
  });
  noBtn.addEventListener("click", closeModal);
  overlay.addEventListener("click", closeModal);

  // interception des boutons Suspendre/Activer
  results.addEventListener("submit", e => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (!/update_employee_status\.php/.test(form.action)) return;

    e.preventDefault();
    const status = form.querySelector('[name="status"]')?.value;
    const message = status === "suspended"
      ? "Voulez-vous vraiment suspendre cet employé ?"
      : "Voulez-vous réactiver cet employé ?";
    openModal(message, form);
  });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const input = document.getElementById("searchInput");
  const form = document.getElementById("searchForm");
  const results = document.getElementById("resultsContainer");

  if (!input || !results) return;

  // Empêche le formulaire de recharger la page
  form.addEventListener("submit", e => e.preventDefault());

  let timer;
  input.addEventListener("input", () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      const q = input.value.trim();
      const url = "manage_employees.php?q=" + encodeURIComponent(q);
      try {
        const resp = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });
        const text = await resp.text();

        // Extraire seulement le tableau des résultats (pas toute la page)
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, "text/html");
        const newTable = doc.querySelector(".table-container");
        results.innerHTML = newTable ? newTable.innerHTML : "<p>Aucun résultat</p>";

      } catch (err) {
        console.error(err);
      }
    }, 300); // délai anti-spam 300 ms
  });
});
</script>

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
