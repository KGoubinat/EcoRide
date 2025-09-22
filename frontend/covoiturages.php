<?php
// accueil.php (page recherche)
require __DIR__ . '/init.php'; // ← session_start + BASE_URL + $pdo=getPDO()

$isLoggedIn  = isset($_SESSION['user_id']);
$user_email  = $_SESSION['user_email'] ?? '';

// Charger la liste des villes pour le datalist
try {
    $stmt = $pdo->query("SELECT nom FROM villes ORDER BY nom ASC");
    $villes = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
} catch (Throwable $e) {
    $villes = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <!-- base dynamique: OK local & Heroku -->
  <base href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>">
  <link rel="stylesheet" href="styles.css">
  <title>Covoiturages près de chez vous | EcoRide</title>
  <meta name="description" content="Trouvez ou proposez facilement un covoiturage avec EcoRide. Partagez vos trajets, économisez et réduisez votre empreinte carbone.">
  <link rel="canonical" href="https://localhost/MesGrossesCouilles/frontend/covoiturages.php">
  <meta property="og:title" content="Covoiturages près de chez vous | EcoRide">
  <meta property="og:description" content="Trouvez ou proposez facilement un covoiturage avec EcoRide. Partagez vos trajets, économisez et réduisez votre empreinte carbone.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://localhost/MesGrossesCouilles/frontend/covoiturages.php">
  <meta property="og:image" content="https://localhost/MesGrossesCouilles/frontend/images/cover.jpg">
  <meta name="twitter:card" content="summary_large_image">
</head>
<body>
<header>
  <div class="header-container">
    <div class="logo"><h1>EcoRide</h1></div>
    <div class="menu-toggle" id="menu-toggle">☰</div>
    <nav id="navbar">
      <ul>
        <li><a href="accueil.php">Accueil</a></li>
        <li><a href="contact_info.php">Contact</a></li>
        <li><a href="covoiturages.php">Covoiturages</a></li>
        <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
        <li id="authButton"
            data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"
            data-user-email="<?= htmlspecialchars($user_email, ENT_QUOTES) ?>"></li>
      </ul>
    </nav>
  </div>

  <!-- Menu mobile -->
  <nav id="mobile-menu">
    <ul>
      <li><a href="accueil.php">Accueil</a></li>
      <li><a href="covoiturages.php">Covoiturages</a></li>
      <li><a href="contact_info.php">Contact</a></li>
      <li id="profilButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
      <li id="authButtonMobile"   data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
    </ul>
  </nav>
</header>

<main class="covoit">
  <section class="form">
    <div class="formulaire">
      <h2 class="ecoride-title">EcoRide</h2>
      <p>Voyagez ensemble, économisez ensemble.</p>

      <form id="rechercheForm" action="resultatsCovoiturages.php" method="GET">
        <input list="cities" id="start" name="start" placeholder="Départ" required><br>
        <input list="cities" id="end"   name="end"   placeholder="Destination" required><br>
        <input type="number" id="passengers" name="passengers" placeholder="Passager(s)" min="1" required><br>
        <label for="date" class="sr-only">Date du covoiturage</label>
        <input type="date" id="date" name="date" required><br>
        <div class="button">
          <button type="submit">Rechercher</button>
        </div>
      </form>

      <datalist id="cities">
        <?php foreach ($villes as $ville): ?>
          <option value="<?= htmlspecialchars($ville, ENT_QUOTES) ?>">
        <?php endforeach; ?>
      </datalist>
    </div>

    <div id="results"></div>
  </section>
</main>

<footer>
  <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>


<script src="js/accueil.js"></script>
</body>
</html>
