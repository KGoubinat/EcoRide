<?php
// rides.php
require __DIR__ . '/init.php'; // session_start + BASE_URL + $pdo=getPDO()

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
  <title>Ecoride - Covoiturages</title>

  <!-- Base dynamique -->
  <base href="<?= htmlspecialchars((string)BASE_URL, ENT_QUOTES) ?>">

  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">

  <!-- SEO dynamiques -->
  <meta name="description" content="Trouvez ou proposez facilement un covoiturage avec EcoRide. Partagez vos trajets, économisez et réduisez votre empreinte carbone.">
  <link rel="canonical" href="<?= htmlspecialchars(current_url(false), ENT_QUOTES) ?>">
  <meta property="og:title" content="Covoiturages près de chez vous | EcoRide">
  <meta property="og:description" content="Trouvez ou proposez facilement un covoiturage avec EcoRide. Partagez vos trajets, économisez et réduisez votre empreinte carbone.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars(current_url(true), ENT_QUOTES) ?>">
  <meta property="og:image" content="<?= htmlspecialchars(absolute_from_base('assets/images/cover.jpg'), ENT_QUOTES) ?>">
  <meta property="og:site_name" content="EcoRide">
  <meta property="og:locale" content="fr_FR">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#0ea5e9">
</head>
<body>
<header>
  <div class="header-container">
    <div class="logo"><h1>EcoRide</h1></div>
    <div class="menu-toggle" id="menu-toggle">☰</div>
    <nav id="navbar">
      <ul>
        <li><a href="home.php">Accueil</a></li>
        <li><a href="contact_info.php">Contact</a></li>
        <li><a href="rides.php" aria-current="page">Covoiturages</a></li>
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
      <li><a href="home.php">Accueil</a></li>
      <li><a href="rides.php">Covoiturages</a></li>
      <li><a href="contact_info.php">Contact</a></li>
      <li id="profilButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
      <li id="authButtonMobile"   data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
    </ul>
  </nav>
</header>

<main class="covoit">
  <section class="form">
    <div class="formulaire">
      <h2 class="ecoride-title">Chercher un covoiturage</h2>
      <p>Indiquez votre trajet et la date souhaitée ci dessous : </p>

      <form id="rechercheForm" action="ride_results.php" method="GET">
        <input list="cities" id="start" name="start" placeholder="Départ" required><br>
        <input list="cities" id="end"   name="end"   placeholder="Destination" required><br>
        <input type="number" id="passengers" name="passengers" placeholder="Passager(s)" min="1" required><br>
        <label for="date" class="sr-only">Date du covoiturage</label>
        <input type="date" id="date" name="date" required><br>
        <button class="button" type="submit">Rechercher</button>
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

<!-- JS -->
<script src="assets/js/home.js" defer></script>
<script src="assets/js/cookie_consent.js" defer></script>

</body>
</html>
