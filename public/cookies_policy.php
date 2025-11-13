<?php
// cookies_policy.php
require __DIR__ . '/init.php';

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>EcoRide - Politique de cookies</title>
  <meta name="description" content="Politique de cookies d’EcoRide : types de cookies utilisés, durée de conservation, gestion du consentement et services tiers.">

  <!-- Base dynamique -->
  <base href="<?= htmlspecialchars((string)BASE_URL, ENT_QUOTES) ?>">

  <!-- SEO -->
  <link rel="canonical" href="<?= htmlspecialchars(current_url(false), ENT_QUOTES) ?>">
  <meta property="og:title" content="EcoRide - Politique de cookies">
  <meta property="og:description" content="Découvrez comment EcoRide utilise les cookies et comment gérer vos préférences.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars(current_url(true), ENT_QUOTES) ?>">
  <meta property="og:image" content="<?= htmlspecialchars(absolute_from_base('assets/images/cover.jpg'), ENT_QUOTES) ?>">
  <meta property="og:site_name" content="EcoRide">
  <meta property="og:locale" content="fr_FR">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#0ea5e9">

  <link rel="stylesheet" href="assets/css/styles.css" />
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
  <div class="cookies">
    <h1>Politique de cookies</h1>
    <p>Ce site utilise des cookies afin d'améliorer votre expérience, mesurer l’audience et personnaliser certains contenus selon votre consentement.</p>

    <h2>Qu'est-ce qu'un cookie&nbsp;?</h2>
    <p>Un cookie est un petit fichier texte stocké sur votre appareil (ordinateur, mobile, tablette) par votre navigateur lorsque vous visitez un site web.</p>

    <h2>Types de cookies utilisés</h2>
    <ul>
      <li><strong>Cookies nécessaires :</strong> indispensables au fonctionnement du site (sécurité, session...).</li>
      <li><strong>Cookies de performance :</strong> mesure d’audience et statistiques agrégées.</li>
      <li><strong>Cookies de fonctionnalité :</strong> confort d’utilisation (préférences...).</li>
      <li><strong>Cookies publicitaires :</strong> personnalisation et mesure des campagnes (si consentis).</li>
      <li><strong>Cookies tiers :</strong> services intégrés (par ex. analytics, CDN, outils marketing).</li>
    </ul>

    <h2>Gestion de vos préférences</h2>
    <p>Vous pouvez modifier vos choix à tout moment via le centre de préférences.</p>
    <p><button type="button" id="open-cookie-modal">Ouvrir le centre de préférences</button></p>
    <ul>
      <li><a href="https://www.aboutcookies.org/" target="_blank" rel="noopener noreferrer">Gérer les cookies dans votre navigateur</a></li>
      <li>Vous pouvez également supprimer les cookies via les réglages de votre navigateur.</li>
    </ul>

    <h2>Durée de conservation</h2>
    <p>Les cookies de session sont supprimés à la fermeture du navigateur.<br> Les cookies persistants expirent automatiquement au terme de leur durée (par défaut 6 à 13&nbsp;mois selon la finalité), sauf suppression manuelle.</p>

    <h2>Cookies tiers</h2>
    <p>Nous pouvons utiliser des services tiers (par ex. Google Analytics). Ces cookies ne sont déposés qu’après votre consentement via le bandeau.</p>

    <h2>Contact</h2>
    <p>Pour toute question, écrivez-nous à <a href="mailto:privacy@ecoride.com">privacy@ecoride.com</a>.</p>
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

<!-- JS (defer) -->
<script src="assets/js/home.js" defer></script>
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

  // Ouvre le centre de préférences si le bouton est cliqué
  const openBtn = document.getElementById('open-cookie-modal');
  if (openBtn) {
    openBtn.addEventListener('click', () => {
      // Si tu as un module dédié, déclenche l’ouverture ici
      // Par ex. window.CookieConsentCenter.open();
      const evt = new Event('open-cookie-modal');
      document.dispatchEvent(evt);
    });
  }
});
</script>
</body>
</html>
