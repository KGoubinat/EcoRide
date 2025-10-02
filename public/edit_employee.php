<?php
// edit_employee.php
declare(strict_types=1);

require __DIR__ . '/init.php';

// Admin uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'accueil.php');
    exit;
}

$pdo = getPDO();

// --- Récupération de l'employé ---
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ' . BASE_URL . 'manage_employees.php?error=invalid_id');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
  <link rel="stylesheet" href="styles.css">
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
    <h2>Modifier l’employé #<?= (int)$employee['id'] ?></h2>

    <?php if (!empty($error)): ?>
      <p class="flash flash-error">✖ <?= htmlspecialchars($error, ENT_QUOTES) ?></p>
    <?php endif; ?>

    <form method="post" class="employee-form">
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
