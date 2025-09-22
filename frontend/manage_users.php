<?php
// manage_users.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()

// Bloquer l’indexation par les moteurs
header('X-Robots-Tag: noindex, nofollow', true);
// Admin uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'accueil.php');
    exit;
}

$pdo = getPDO();

// Filtres simples
$search = trim((string)($_GET['q'] ?? ''));
$role   = (string)($_GET['role'] ?? 'utilisateur');        // ''|utilisateur|employe|administrateur
$etat   = (string)($_GET['etat'] ?? '');        // ''|active|suspendu

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gérer les Utilisateurs | Admin EcoRide</title>

  <base href="<?= htmlspecialchars(rtrim(BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="styles.css">

  <!-- SEO : description obligatoire pour Lighthouse -->
  <meta name="description" content="Interface d’administration EcoRide pour rechercher, filtrer, activer ou suspendre les comptes utilisateurs.">
  <meta name="robots" content="noindex, nofollow">

  <!-- Canonical sans les paramètres (q, etat, role) pour éviter le duplicate -->
  <?php $canonical = rtrim(BASE_URL, '/').'/manage_users.php'; ?>
  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">

  <!-- Open Graph facultatif -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="Gérer les Utilisateurs | Admin EcoRide">
  <meta property="og:description" content="Page d’administration EcoRide pour gérer les comptes utilisateurs.">
  <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
</head>
<body>
<header>
  <div class="header-container">
    <h1>Bienvenue, Administrateur</h1>
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
      <li><a href="add_employee.php">Ajouter un Employé</a></li>
      <li><a href="manage_employees.php">Gérer les Employés</a></li>
      <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
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
    </form>


    <h3>Liste des Utilisateurs</h3>
    <div class="table-container">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Email</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$users): ?>
          <tr><td colspan="6">Aucun utilisateur trouvé.</td></tr>
        <?php else: ?>
          <?php
            $qs = [];
            if ($search !== '') $qs['q'] = $search;
            if ($role   !== '') $qs['role'] = $role;     // même si non présent dans le form, on garde le défaut
            if ($etat   !== '') $qs['etat'] = $etat;
            $back = 'manage_users.php' . ($qs ? ('?' . http_build_query($qs)) : '');
          ?>

          <?php foreach ($users as $row): ?>
            <tr>
              <td><?= (int)$row['id'] ?></td>
              <td><?= htmlspecialchars($row['firstName'] . ' ' . $row['lastName'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars($row['email'], ENT_QUOTES) ?></td>
              <td><?= htmlspecialchars(ucfirst((string)$row['etat']), ENT_QUOTES) ?></td>
              <td>
                <?php if (($row['etat'] ?? '') === 'active'): ?>
                  <a href="update_user_status.php?id=<?= (int)$row['id'] ?>&status=suspended&back=<?= urlencode($back) ?>"
                    onclick="return confirm('Suspendre cet utilisateur ?');">Suspendre</a>
                <?php else: ?>
                  <a href="update_user_status.php?id=<?= (int)$row['id'] ?>&status=active&back=<?= urlencode($back) ?>"
                    onclick="return confirm('Activer cet utilisateur ?');">Activer</a>
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
  <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
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

<script>
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("filterForm");

  // Déclencher filtre quand on tape dans recherche
  document.getElementById("q").addEventListener("input", function () {
    form.submit();
  });
  document.getElementById("etat").addEventListener("change", function () {
    form.submit();
  });
});
</script>
</body>
</html>
