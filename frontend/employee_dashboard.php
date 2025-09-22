<?php

declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL (+ $pdo dispo via getPDO())

// Blocage SEO
header('X-Robots-Tag: noindex, nofollow', true);

// Autorisation : employé uniquement
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employe') {
    header('Location: ' . BASE_URL . 'accueil.php');
    exit;
}

$employee_name = trim(($_SESSION['firstName'] ?? '') . ' ' . ($_SESSION['lastName'] ?? ''));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Espace Employé | Tableau de bord EcoRide</title>

    <base href="<?= htmlspecialchars(rtrim(BASE_URL, '/').'/', ENT_QUOTES) ?>">
    <link rel="stylesheet" href="styles.css" />

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
        <h1>Espace Employé de <?= htmlspecialchars($employee_name ?: '—') ?></h1>
        <div class="menu-toggle" id="menu-toggle">☰</div>
        <nav id="navbar">
            <ul>
                <li><a href="employee_dashboard.php">Tableau de bord</a></li>
                <li><a href="employee_reviews.php">Gérer les Avis</a></li>
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
    <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>

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
