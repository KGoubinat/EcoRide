<?php
// edit_employee.php
declare(strict_types=1);

require __DIR__ . '/init.php';

// Admin uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'home.php');
    exit;
}

$pdo = getPDO();

// --- Récupération de l'employé ---
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ' . BASE_URL . 'manage_employees.php?error=invalid_id');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    // CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!$csrf || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        header('Location: ' . BASE_URL . 'manage_employees.php?error=forbidden');
        exit;
    }

    $lastName  = trim($_POST['lastName'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $etat      = trim($_POST['etat'] ?? 'active');

    if ($lastName && $firstName && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET lastName = :lastName,
                firstName = :firstName,
                email = :email,
                etat = :etat
            WHERE id = :id AND role = 'employe'
        ");
        $stmt->execute([
            ':lastName'  => $lastName,
            ':firstName' => $firstName,
            ':email'     => $email,
            ':etat'      => $etat,
            ':id'        => $id,
        ]);

        header('Location: ' . BASE_URL . 'manage_employees.php?success=1');
        exit;
    } else {
        $error = "Veuillez remplir correctement tous les champs.";
    }
}

$stmt = $pdo->prepare("SELECT id, lastName, firstName, email, etat FROM users WHERE id = ? AND role = 'employe'");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: ' . BASE_URL . 'manage_employees.php?error=notfound');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Modifier un Employé</title>
  <base href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">
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
  <section class="listEmployee">
    <h2>Modifier l’employé #<?= (int)$employee['id'] ?></h2>

    <?php if (!empty($error)): ?>
      <p class="flash flash-error">✖ <?= htmlspecialchars($error, ENT_QUOTES) ?></p>
    <?php endif; ?>

    <form method="post" class="employee-form" action="edit_employee.php?id=<?= (int)$employee['id'] ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <label for="lastName">Nom :</label>
      <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($employee['lastName'], ENT_QUOTES) ?>" required><br><br>

      <label for="firstName">Prénom :</label>
      <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($employee['firstName'], ENT_QUOTES) ?>" required><br><br>

      <label for="email">Email :</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($employee['email'], ENT_QUOTES) ?>" required><br><br>

      <label for="etat">État :</label>
      <select id="etat" name="etat">
        <option value="active"   <?= $employee['etat']==='active'?'selected':'' ?>>Actif</option>
        <option value="suspended" <?= $employee['etat']==='suspended'?'selected':'' ?>>Suspendu</option>
      </select><br><br>

      <div style="margin-top:1rem;">
        <button type="submit">Enregistrer</button>
        <a href="manage_employees.php" class="btn-secondary">↩ Retour</a>
      </div>
    </form>
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
