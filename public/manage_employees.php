<?php
// manage_employees.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()

header('X-Robots-Tag: noindex, nofollow', true);

// Admin uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'accueil.php');
    exit;
}

$pdo = getPDO();

// petite recherche par nom/email
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gérer les Employés | Admin EcoRide</title>

  <!-- base dynamique (local & prod) -->
  <base href="<?= htmlspecialchars(rtrim(BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="styles.css">

  <!-- SEO Lighthouse OK mais page privée -->
  <meta name="description" content="Interface d’administration EcoRide pour rechercher, activer, suspendre et modifier les comptes employés.">
  <meta name="robots" content="noindex, nofollow">

  <!-- canonical SANS le paramètre ?q= pour éviter le duplicate -->
  <?php
    $canonical = rtrim(BASE_URL, '/').'/manage_employees.php';
  ?>
  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Gérer les Employés | Admin EcoRide">
  <meta property="og:description" content="Recherche, activation/suspension et modification des comptes employés.">
  <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
  </head>
<body>
<header>
  <div class="header-container">
    <h1>Gestion des Employés</h1>
    <div class="menu-toggle" id="menu-toggle">☰</div>
    <nav id="navbar">
      <ul>
        <li><a href="admin_dashboard.php">Tableau de bord</a></li>
        <li><a href="add_employee.html">Ajouter un Employé</a></li>
        <li><a href="manage_employees.php">Gérer les Employés</a></li>
        <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
      </ul>
    </nav>
  </div>
  <nav id="mobile-menu">
    <ul>
      <li><a href="admin_dashboard.php">Tableau de bord</a></li>
      <li><a href="add_employee.html">Ajouter un Employé</a></li>
      <li><a href="manage_employees.php">Gérer les Employés</a></li>
      <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>

<main class="covoit">
  <section class="listEmployee">
    <h2>Liste des Employés</h2>
    <?php if (isset($_GET['success'])): ?>
      <p class="flash flash-success">✔ Action effectuée.</p>
    <?php elseif (isset($_GET['error'])): ?>
      <p class="flash flash-error">✖ Erreur lors de l’action.</p>
    <?php endif; ?>

    <form method="get" style="margin: .5rem 0 1rem; display:flex; gap:.5rem; align-items:center;">
      <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES) ?>" placeholder="Rechercher (nom ou email)" />
      <button type="submit">Rechercher</button>
      <?php if ($search !== ''): ?>
        <a href="manage_employees.php" style="margin-left:.5rem;">Réinitialiser</a>
      <?php endif; ?>
    </form>

    <div class="table-container">
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
        <?php $back = 'manage_employees.php' . ($search !== '' ? ('?q='.urlencode($search)) : ''); ?>

          <?php foreach ($employees as $row): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><?= htmlspecialchars($row['firstName'] . ' ' . $row['lastName'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($row['email'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($row['role'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars(ucfirst((string)$row['etat']), ENT_QUOTES) ?></td>
              <td>
                <?php if (($row['etat'] ?? '') === 'active'): ?>
                  <a href="update_employee_status.php?id=<?= (int)$row['id'] ?>&status=suspended&back=<?= urlencode($back) ?>"
                    onclick="return confirm('Suspendre cet employé ?');">Suspendre</a>
                <?php else: ?>
                  <a href="update_employee_status.php?id=<?= (int)$row['id'] ?>&status=active&back=<?= urlencode($back) ?>"
                    onclick="return confirm('Activer cet employé ?');">Activer</a>
                <?php endif; ?>
                | <a href="edit_employee.php?id=<?= (int)$row['id'] ?>">Modifier</a>

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
