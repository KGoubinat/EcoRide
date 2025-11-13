<?php
// add_employee.php
declare(strict_types=1);

require __DIR__ . '/init.php';
header('X-Robots-Tag: noindex, nofollow', true);

// Admin uniquement
if (($_SESSION['user_role'] ?? null) !== 'administrateur') {
    header('Location: ' . BASE_URL . 'accueil.php');
    exit;
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$pdo = getPDO();
$error = '';
$success = isset($_GET['success']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!hash_equals($csrf, $token)) {
    $error = "Action interdite (CSRF).";
  } else {
    $lastName  = trim($_POST['lastName'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $etat      = ($_POST['etat'] ?? 'active') === 'suspended' ? 'suspended' : 'active';

    if ($lastName !== '' && $firstName !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $st = $pdo->prepare("
        INSERT INTO users (lastName, firstName, email, role, etat)
        VALUES (:ln, :fn, :em, 'employe', :et)
      ");
      $st->execute([
        ':ln' => $lastName,
        ':fn' => $firstName,
        ':em' => $email,
        ':et' => $etat,
      ]);
      header('Location: ' . BASE_URL . 'add_employee.php?success=1');
      exit;
    } else {
      $error = "Veuillez remplir correctement tous les champs.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Ajouter un Employé</title>
  <base href="<?= htmlspecialchars((string)BASE_URL, ENT_QUOTES) ?>">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">
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
        <li><a href="add_employee.php" aria-current="page">Ajouter un Employé</a></li>
        <li><a href="manage_employees.php">Gérer les Employés</a></li>
        <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
        <li><a href="logout.php">Déconnexion</a></li>
      </ul>
    </nav>
  </div>
  <nav id="mobile-menu">
    <ul>
      <li><a href="admin_dashboard.php">Tableau de bord</a></li>
      <li><a href="add_employee.php" aria-current="page">Ajouter un Employé</a></li>
      <li><a href="manage_employees.php">Gérer les Employés</a></li>
      <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
      <li><a href="logout.php">Déconnexion</a></li>
    </ul>
  </nav>
</header>

<main class="covoit">
  <section class="addEmployee" >
    <h2>Ajouter un employé</h2>

    <?php if ($success): ?>
      <p class="flash flash-success">✔ Employé ajouté.</p>
    <?php elseif ($error): ?>
      <p class="flash flash-error">✖ <?= htmlspecialchars($error, ENT_QUOTES) ?></p>
    <?php endif; ?>

    <form class="compact-fields" method="POST" action="add_employee.php">
  <div class="form-group">
    <label for="lastName">Nom :</label>
    <input id="lastName" name="lastName" type="text" required>
  </div>

  <div class="form-group">
    <label for="firstName">Prénom :</label>
    <input id="firstName" name="firstName" type="text" required>
  </div>

  <div class="form-group">
    <label for="email">Email :</label>
    <input id="email" name="email" type="email" required>
  </div>

  <div class="form-group">
    <label for="etat">État :</label>
    <select id="etat" name="etat">
      <option value="active">Actif</option>
      <option value="suspended">Suspendu</option>
    </select>
  </div>

  <button type="submit">Enregistrer</button>
</form>

  </section>
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

<script src="assets/js/cookie-consent.js" defer></script>
<script src="assets/js/accueil.js" defer></script>
</body>
</html>
