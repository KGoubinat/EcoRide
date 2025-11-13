<?php
// legal_notice.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL (+ $pdo si besoin)

$isLoggedIn = isset($_SESSION['user_id']);
$canonical  = rtrim((string)BASE_URL, '/').'/legal_notice.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EcoRide - Mentions légales</title>

  <!-- base dynamique -->
  <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
  <link rel="stylesheet" href="assets/css/styles.css" />
  <link rel="stylesheet" href="assets/css/modern.css">

  <!-- SEO -->
  <meta name="description" content="Mentions légales d’EcoRide : éditeur du site, hébergeur, contact, conditions d’utilisation, confidentialité et cookies.">
  <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">

  <!-- Open Graph (partage propre) -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="EcoRide - Mentions légales">
  <meta property="og:description" content="Informations légales : éditeur, hébergeur, CGU, confidentialité et cookies.">
  <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES) ?>">
  <meta property="og:image" content="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/assets/images/cover.jpg', ENT_QUOTES) ?>">
  <meta name="twitter:card" content="summary_large_image">
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
        <li><a href="rides.php">Covoiturages</a></li>
        <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
        <li id="authButton"   data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
      </ul>
    </nav>
  </div>
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
    <h3>Mentions Légales</h3>

    <h4>Éditeur du site</h4>
    <p>EcoRide – Projet pédagogique réalisé dans le cadre du Titre Professionnel DWWM.</p>
    <p>Email : <a href="mailto:EcoRide@gmail.com">EcoRide@gmail.com</a></p>
    <p>Téléphone : <a href="tel:+33123456789">01&nbsp;23&nbsp;45&nbsp;67&nbsp;89</a></p>

    <h4>Directeur de publication</h4>
    <p>Kévin Goubinat</p>

    <h4>Hébergement</h4>
    <p>Hébergeur : Heroku</p>
  

    <h4>Conditions Générales d'Utilisation (CGU)</h4>
    <ul>
      <li>En accédant à ce site, vous acceptez les conditions d'utilisation.</li>
      <li>Le contenu du site est protégé par des droits d'auteur.</li>
      <li>L'éditeur ne peut être tenu responsable des dommages liés à l'utilisation du site.</li>
    </ul>

    <h4>Politique de Confidentialité</h4>
    <p>Les données personnelles sont collectées à des fins de gestion des comptes utilisateurs, etc.
       Pour plus d'informations, consultez notre politique de confidentialité.</p>

    <h4>Cookies</h4>
    <p>Ce site utilise des cookies pour améliorer votre expérience. Consultez notre
      <a href="cookies_policy.php">politique de cookies</a>.
    </p>

    <h4>Litiges</h4>
    <p>Les litiges seront résolus par les tribunaux de Paris, France, sous la législation française.</p>
  </div>
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
<script src="assets/js/home.js"></script>
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
