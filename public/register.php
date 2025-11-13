<?php
declare(strict_types=1);
require __DIR__ . '/init.php'; // session + BASE_URL + getPDO()


$isLoggedIn = !empty($_SESSION['user_email'] ?? null);

// CSRF (optionnel pour l’API inscription ; utile si tu veux aussi poster en x-www-form-urlencoded)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EcoRide - Inscription</title>
  <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
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
        <li><a href="accueil.php">Accueil</a></li>
        <li><a href="contact_info.php">Contact</a></li>
        <li><a href="covoiturages.php">Covoiturages</a></li>
        <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
        <li id="authButton"   data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
      </ul>
    </nav>
  </div>

  <!-- Menu mobile (caché par défaut) -->
        <nav id="mobile-menu">
            <ul>
                <li><a href="accueil.php">Accueil</a></li>
                <li><a href="covoiturages.php">Covoiturages</a></li>
                <li><a href="contact_info.php">Contact</a></li>
                <li id="profilButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                <li id="authButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
            </ul>
        </nav>
</header>

<main class="no-columns">
  <section class="signup-form-container ">
    <h2>Inscription</h2>
    <p>Créez votre compte pour rejoindre EcoRide !</p>

    <form id="signupForm" class="compact-fields" method="POST" action="../backend/handlers/register.php" novalidate>
      <input type="text"  id="firstName"  name="firstName"  placeholder="Prénom" autocomplete="given-name" required>
      <input type="text"  id="lastName"   name="lastName"   placeholder="Nom" autocomplete="family-name" required>
      <input type="email" id="email"      name="email"      placeholder="Adresse e-mail" autocomplete="email" required>
      <input type="password" id="password" name="password" placeholder="Mot de passe" minlength="8" autocomplete="new-password" required>
      <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirmer le mot de passe" autocomplete="new-password" required>
      <div id="passwordError" role="alert" style="color:red;font-size:.9rem;display:none;"></div>
      <button type="submit" class="button">S’inscrire</button>
    </form>

    <p>Déjà inscrit ? <a href="connexion.php">Connectez-vous ici !</a></p>
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

<!-- Réutilise ton JS de nav (remplit Connexion/Profil) -->
<script src="assets/js/accueil.js" defer></script>
<!-- Validation + appel API d’inscription -->
<script src="assets/js/register.js" defer></script>
<script src="assets/js/cookie-consent.js" defer></script>

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
