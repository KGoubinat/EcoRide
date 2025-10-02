<?php
// mentions_legales.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL (+ $pdo dispo si besoin)

$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EcoRide - Mentions légales</title>
  <!-- base dynamique -->
  <base href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>">
  <link rel="stylesheet" href="styles.css" />
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
  <div class="contact">
    <h3>Mentions Légales</h3>

    <h4>Éditeur du site</h4>
    <p>EcoRide, société à responsabilité limitée (SARL) au capital de 100&nbsp;000&nbsp;€</p>
    <p>Siège social : 123 Rue de l'Innovation, 75000 Paris, France</p>
    <p>Email : <a href="mailto:contact@ecoride.com">contact@ecoride.com</a></p>
    <p>Téléphone : 01&nbsp;23&nbsp;45&nbsp;67&nbsp;89</p>
    <p>Numéro SIREN : 123&nbsp;456&nbsp;789</p>

    <h4>Directeur de publication</h4>
    <p>Jean Dupont, Directeur général de EcoRide</p>

    <h4>Hébergement</h4>
    <p>Hébergeur : OVH</p>
    <p>Adresse : 2 Rue Kellermann, 59100 Roubaix, France</p>

    <h4>Conditions Générales d'Utilisation (CGU)</h4>
    <ul>
      <li>En accédant à ce site, vous acceptez les conditions d'utilisation suivantes.</li>
      <li>Le contenu du site est protégé par des droits d'auteur.</li>
      <li>L'éditeur ne peut être tenu responsable des dommages liés à l'utilisation du site.</li>
    </ul>

    <h4>Politique de Confidentialité</h4>
    <p>Les données personnelles sont collectées à des fins de gestion des comptes utilisateurs, etc. Pour plus d'informations, consultez notre politique de confidentialité.</p>

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
            <span>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></span>
        </div>
    </footer>

<!-- chemins relatifs à BASE_URL -->
<script src="js/accueil.js"></script>
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
