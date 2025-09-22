<?php
// contact_info.php (ou accueil.php si c'est ta page)
require __DIR__ . '/init.php'; // ← démarre la session, définit BASE_URL et $pdo via getPDO() si besoin

// Vérifie si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Contact</title>
    <meta name="description" content="EcoRide, votre plateforme de covoiturage écologique et solidaire. Contactez-nous facilement par email ou téléphone pour toute question ou assistance. Rejoignez une communauté engagée pour une mobilité plus durable.">
    <!-- base dynamique: marche en local ET sur Heroku -->
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
    <div class="contact">
        <h2>Contactez-nous</h2>
        <p>Vous pouvez nous contacter à l'adresse suivante :</p>
        <p>Email : <a href="mailto:contact@exemple.com">contact@exemple.com</a></p>
        <p>Téléphone : 01 23 45 67 89</p>
    </div>
</main>

<footer>
    <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>

<!-- JS relatif à BASE_URL -->
<script src="js/accueil.js"></script>
</body>
</html>
