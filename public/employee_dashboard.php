<?php

declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL (+ $pdo dispo via getPDO())

// Blocage SEO
header('X-Robots-Tag: noindex, nofollow', true);

// Autorisation : employé uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employe') {
    header('Location: ' . BASE_URL . 'home.php');
    exit;
}

$employee_name = trim(($_SESSION['firstName'] ?? '') . ' ' . ($_SESSION['lastName'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Ecoride - Tableau de bord</title>

    <base href="<?= htmlspecialchars(rtrim(BASE_URL, '/').'/', ENT_QUOTES) ?>">
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="assets/css/modern.css">

    <!-- SEO (Lighthouse OK mais page privée) -->
    <meta name="description" content="Espace Employé EcoRide : tableau de bord permettant de gérer les avis des chauffeurs et de consulter les covoiturages problématiques.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= htmlspecialchars(rtrim(BASE_URL,'/').'/employee_dashboard.php', ENT_QUOTES) ?>">

    <!-- Open Graph facultatif -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Espace Employé | Tableau de bord EcoRide">
    <meta property="og:description" content="Tableau de bord de l’espace Employé : gestion des avis et suivi des covoiturages problématiques.">
    <meta property="og:url" content="<?= htmlspecialchars(rtrim(BASE_URL,'/').'/employee_dashboard.php', ENT_QUOTES) ?>">
</head>
<body>
<header>
    <div class="header-container">
        <h1>Espace de <?= htmlspecialchars($employee_name ?: '—') ?></h1>
        <div class="menu-toggle" id="menu-toggle">☰</div>
        <nav id="navbar">
            <ul>
                <li><a href="employee_dashboard.php" aria-current="page">Tableau de bord</a></li>
                <li><a href="employee_reviews.php" >Gérer les Avis</a></li>
                <li><a href="employee_troublesome_rides.php">Covoiturages Problématiques</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </div>

    <!-- Menu mobile -->
    <nav id="mobile-menu">
        <ul>
            <li><a href="employee_dashboard.php">Tableau de bord</a></li>
            <li><a href="employee_reviews.php">Gérer les Avis</a></li>
            <li><a href="employee_troublesome_rides.php">Covoiturages Problématiques</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>

<main class="adaptation">
    <section class="boardEmployee">
        <h2>Tableau de bord</h2>
        <p>Bienvenue dans votre espace. Vous pouvez gérer les avis des chauffeurs et consulter les covoiturages qui ont eu des problèmes.</p>
        <div>
            <h2>Actions disponibles :</h2>
            <ul>
                <li><a href="employee_reviews.php">Valider ou refuser les avis</a></li>
                <li><a href="employee_troublesome_rides.php">Consulter les covoiturages problématiques</a></li>
            </ul>
        </div>
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

<script src ="assets/js/accueil.js" defer></script>
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
