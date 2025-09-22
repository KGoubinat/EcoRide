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
  <title>Inscription - EcoRide</title>
  <base href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>">
  <link rel="stylesheet" href="styles.css">
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

<main class="no-columns">
  <section class="signup-form-container">
    <h2>Inscription</h2>
    <p>Créez votre compte pour rejoindre EcoRide !</p>

    <form id="signupForm" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES) ?>">
      <input type="text"     id="firstName"       name="firstName"       placeholder="Prénom" required><br>
      <input type="text"     id="lastName"        name="lastName"        placeholder="Nom" required><br>
      <input type="email"    id="email"           name="email"           placeholder="Adresse e-mail" required><br>
      <input type="password" id="password"        name="password"        placeholder="Mot de passe" required minlength="8"><br>
      <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirmer le mot de passe" required><br>
      <div id="passwordError" style="color:red;font-size:.9rem;display:none;"></div>

      <div class="button">
        <button type="submit">S'inscrire</button>
      </div>
    </form>

    <p>Déjà inscrit ? <a href="connexion.html">Connectez-vous ici !</a></p>
  </section>
</main>

<footer>
        <div class="footer-links">
            <a href="#" id="open-cookie-modal">Gérer mes cookies</a>
            <span>|</span>
            <span>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></span>
        </div>
    </footer>

<!-- Réutilise ton JS de nav (remplit Connexion/Profil) -->
<script src="js/accueil.js" defer></script>
<!-- Validation + appel API d’inscription -->
<script src="js/register.js" defer></script>
</body>
</html>
