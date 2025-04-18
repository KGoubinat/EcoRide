<?php
session_start(); // Commencer la session

// Récupérer l'URL de la base de données depuis la variable d'environnement JAWSDB_URL
$databaseUrl = getenv('JAWSDB_URL');

// Utiliser une expression régulière pour extraire les éléments nécessaires de l'URL
$parsedUrl = parse_url($databaseUrl);

// Définir les variables pour la connexion à la base de données
$servername = $parsedUrl['host'];  // Hôte MySQL
$username = $parsedUrl['user'];  // Nom d'utilisateur MySQL
$password = $parsedUrl['pass'];  // Mot de passe MySQL
$dbname = ltrim($parsedUrl['path'], '/');  // Nom de la base de données (en enlevant le premier "/")

// Connexion à la base de données avec PDO
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

// Vérifie si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']); // Renvoi 'true' ou 'false' en fonction de la connexion
?>


<!DOCTYPE html> 
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Accueil</title>
    <link rel="stylesheet" href="/frontend/styles.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>EcoRide</h1>
            </div>

            <div class="menu-toggle" id="menu-toggle">☰</div>

            <nav id="navbar">
                <ul>
                    <li><a href="/frontend/accueil.php">Accueil</a></li>
                    <li><a href="/frontend/contact_info.php">Contact</a></li>
                    <li><a href="/frontend/covoiturages.php">Covoiturages</a></li>
                    <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                    <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                </ul>
            </nav>
        </div>
        <!-- Menu mobile (caché par défaut) -->
        <nav id="mobile-menu">
            <ul>
                <li><a href="/frontend/accueil.php">Accueil</a></li>
                <li><a href="/frontend/covoiturages.php">Covoiturages</a></li>
                <li><a href="/frontend/contact_info.php">Contact</a></li>
                <li id="profilButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                <li id="authButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
            </ul>
        </nav>
    </header>
    
    <main class=covoit>
        <div class=contact>
            <h2>Contactez-nous</h2>
            <p>Vous pouvez nous contacter à l'adresse suivante :</p>
            <p>Email : <a href="mailto:contact@exemple.com">contact@exemple.com</a></p>
            <p>Téléphone : 01 23 45 67 89</p>
        </div>
    </main>
    
    <footer>
        <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
    </footer>

    <!-- Script JavaScript -->
    <script src="/frontend/js/accueil.js"></script>
</body>
</html>
