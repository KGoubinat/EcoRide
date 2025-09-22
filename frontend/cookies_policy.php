<?php
// cookies.php (par ex.)
require __DIR__ . '/init.php'; // session_start + BASE_URL (+ $pdo dispo si besoin)

// Es-tu connecté ?
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EcoRide - Politique de cookies</title>
  <!-- base dynamique: OK local & Heroku -->
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
  <div class="cookies">
    <h3>Politique de Cookies</h3>
    <p>Ce site utilise des cookies afin d'améliorer votre expérience...</p>

    <h4>Qu'est-ce qu'un cookie ?</h4>
    <p>Un cookie est un petit fichier texte stocké sur votre appareil...</p>

    <h4>Les types de cookies utilisés sur ce site</h4>
    <ul>
      <li><strong>Cookies nécessaires :</strong> ...</li>
      <li><strong>Cookies de performance :</strong> ...</li>
      <li><strong>Cookies de fonctionnalité :</strong> ...</li>
      <li><strong>Cookies publicitaires :</strong> ...</li>
      <li><strong>Cookies tiers :</strong> ...</li>
    </ul>

    <h4>Comment gérer les cookies</h4>
    <ul>
      <li><a href="https://www.aboutcookies.org/">Gérer les cookies dans votre navigateur</a></li>
      <li>Vous pouvez supprimer les cookies à tout moment via les paramètres du navigateur.</li>
    </ul>

    <h4>Durée de conservation des cookies</h4>
    <p>Les cookies de session sont supprimés à la fermeture du navigateur...</p>

    <h4>Cookies de tiers</h4>
    <p>Nous utilisons des services tiers (ex. Google Analytics)...</p>

    <h4>Consentement</h4>
    <p>En poursuivant votre navigation après la bannière, vous acceptez l'utilisation de cookies...</p>

    <h4>Contact</h4>
    <p>Questions : <a href="mailto:privacy@ecoride.com">privacy@ecoride.com</a></p>
  </div>
</main>

<footer>
  <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>

<!-- chemins relatifs à BASE_URL -->
<script src="js/accueil.js"></script>
</body>
</html>
