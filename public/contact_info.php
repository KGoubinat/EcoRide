<?php
// contact_info.php
require __DIR__ . '/init.php';

// Vérifie si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>EcoRide - Contact</title>
  <meta name="description" content="EcoRide, votre plateforme de covoiturage écologique et solidaire. Contactez-nous facilement par email ou téléphone pour toute question ou assistance.">

  <!-- Base dynamique -->
  <base href="<?= htmlspecialchars((string)BASE_URL, ENT_QUOTES) ?>">

  <!-- SEO -->
  <link rel="canonical" href="<?= htmlspecialchars(current_url(false), ENT_QUOTES) ?>">
  <meta property="og:title" content="EcoRide - Contact">
  <meta property="og:description" content="Besoin d’aide ? Contactez l’équipe EcoRide.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars(current_url(true), ENT_QUOTES) ?>">
  <meta property="og:image" content="<?= htmlspecialchars(absolute_from_base('assets/images/cover.jpg'), ENT_QUOTES) ?>">
  <meta property="og:site_name" content="EcoRide">
  <meta property="og:locale" content="fr_FR">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#0ea5e9">

  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">
</head>
<body>
<header>
  <div class="header-container">
    <div class="logo"><h1>EcoRide</h1></div>

    <div class="menu-toggle" id="menu-toggle">☰</div>

    <nav id="navbar">
      <ul>
        <li><a href="home.php">Accueil</a></li>
        <li><a href="contact_info.php" aria-current="page">Contact</a></li>
        <li><a href="rides.php">Covoiturages</a></li>
        <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
        <li id="authButton"   data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
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
  <div class="contact">
    <h2>Contactez-nous</h2>
    <p>Vous pouvez nous contacter à l'adresse suivante :</p>
    <p>Email : <a href="mailto:contact@exemple.com">contact@exemple.com</a></p>
    <p>Téléphone : <a href="tel:+33123456789">01 23 45 67 89</a></p>
  </div>
</main>

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

<footer>
        <div class="footer-links">
            <a href="#" id="open-cookie-modal">Gérer mes cookies</a>
            <span>|</span>
            <span>EcoRide@gmail.com</span>
            <span>|</span>
            <a href="legal_notice.php">Mentions légales</a>
        </div>
    </footer>

<!-- JS -->
<script src="assets/js/accueil.js" defer></script>
<script src="assets/js/cookie-consent.js" defer></script>
</body>
</html>
