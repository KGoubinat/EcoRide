<?php
declare(strict_types=1);
require __DIR__ . '/init.php'; // BASE_URL + session + csrf

header('X-Robots-Tag: noindex, nofollow', true);

// redirection par défaut (peut être surchargée par ?redirect=...)
$redirect = $_GET['redirect'] ?? 'home.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>EcoRide - Connexion</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Base dynamique -->
  <base href="<?= htmlspecialchars((string)BASE_URL, ENT_QUOTES) ?>">

  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">

  <!-- SEO -->
  <meta name="description" content="Connectez-vous à votre compte EcoRide pour gérer vos covoiturages et accéder à vos informations personnelles.">
  <meta name="robots" content="noindex, nofollow">
  <link rel="canonical" href="<?= htmlspecialchars(rtrim(BASE_URL,'/').'/login.php', ENT_QUOTES) ?>">

  <!-- Open Graph minimal (au cas où) -->
  <meta property="og:title" content="Connexion | EcoRide">
  <meta property="og:description" content="Accédez à votre espace EcoRide et retrouvez vos covoiturages en toute sécurité.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars(rtrim(BASE_URL,'/').'/login.php', ENT_QUOTES) ?>">
  <meta property="og:image" content="<?= htmlspecialchars(absolute_from_base('assets/images/cover.jpg'), ENT_QUOTES) ?>">
  <meta name="twitter:card" content="summary_large_image">

  <style>
    .alert{padding:12px;margin:10px 0;border-radius:6px;font-weight:bold}
    .alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
  </style>
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
      </ul>
    </nav>
  </div>

  <nav id="mobile-menu">
    <ul>
      <li><a href="home.php">Accueil</a></li>
      <li><a href="rides.php">Covoiturages</a></li>
      <li><a href="contact_info.php">Contact</a></li>
    </ul>
  </nav>
</header>

<main class="no-columns">
  <div class="login-container">
    <h2>Connexion</h2>

    <!-- Zone d'alerte -->
    <div id="alertBox" class="alert alert-danger" style="display:none;"></div>

    <!-- Formulaire -->
    <form id="loginForm" action="../backend/handlers/login.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
      <input type="hidden" name="redirect" id="redirectInput" value="<?= htmlspecialchars($redirect, ENT_QUOTES) ?>">

      <div class="form-group">
        <label for="email">Adresse email :</label>
        <input type="email" id="email" name="email" placeholder="Entrez votre email" required>
      </div>

      <div class="form-group">
        <label for="password">Mot de passe :</label>
        <input type="password" id="password" name="password" placeholder="Entrez votre mot de passe" required>
      </div>

      <div class="form-group">
        <a href="mot_de_passe_oublie.php" class="forgot-password">Mot de passe oublié ?</a>
      </div>

      <button type="submit">Se connecter</button>
    </form>

    <p>Pas encore inscrit ? <a href="register.php">Créer un compte</a></p>
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
<script src="assets/js/cookie-consent.js" defer></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  // Menu burger
  const menuToggle = document.getElementById("menu-toggle");
  const mobileMenu = document.getElementById("mobile-menu");
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener("click", () => mobileMenu.classList.toggle("active"));
    document.querySelectorAll("#mobile-menu a").forEach(link =>
      link.addEventListener("click", () => mobileMenu.classList.remove("active"))
    );
  }

  // Alerte en fonction de ?error=...
  const params = new URLSearchParams(window.location.search);
  const error = params.get('error');
  const alertBox = document.getElementById('alertBox');
  if (error) {
    const messages = {
      inactive:   "Votre compte est suspendu. Merci de contacter l’administrateur.",
      credentials:"Identifiants incorrects.",
      missing:    "Veuillez remplir tous les champs.",
      internal:   "Erreur interne, réessayez plus tard."
    };
    const msg = messages[error] || "";
    if (msg) {
      alertBox.textContent = msg;
      alertBox.style.display = "block";
    }
  }
});
</script>
</body>
</html>
