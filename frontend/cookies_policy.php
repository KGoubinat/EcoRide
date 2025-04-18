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
        <div class=cookies>
    <h3>Politique de Cookies</h3>

            <p>Ce site utilise des cookies afin d'améliorer votre expérience de navigation et de vous offrir des services personnalisés. En utilisant ce site, vous acceptez notre utilisation des cookies conformément à cette politique.</p>

            <h4>Qu'est-ce qu'un cookie ?</h4>
            <p>Un cookie est un petit fichier texte qui est stocké sur votre appareil lorsque vous accédez à notre site. Les cookies permettent à notre site de se souvenir de vos actions et préférences (comme votre identifiant de connexion, la langue, la taille des caractères, et d'autres préférences d'affichage) pendant une période donnée, afin que vous n'ayez pas à les saisir à chaque fois que vous revenez sur notre site.</p>

            <h4>Les types de cookies utilisés sur ce site</h4>
            <ul>
                <li><strong>Cookies nécessaires :</strong> Ces cookies sont essentiels pour vous permettre de naviguer sur notre site et d'utiliser ses fonctionnalités, telles que l'accès à des zones sécurisées du site. Ils ne peuvent pas être désactivés.</li>
                <li><strong>Cookies de performance :</strong> Ces cookies collectent des informations sur la manière dont vous utilisez notre site, comme les pages que vous consultez le plus souvent. Ces informations nous permettent d'améliorer le fonctionnement de notre site.</li>
                <li><strong>Cookies de fonctionnalité :</strong> Ces cookies permettent de personnaliser votre expérience sur notre site, comme la mémorisation de votre langue préférée ou d'autres paramètres personnalisés.</li>
                <li><strong>Cookies publicitaires :</strong> Ces cookies sont utilisés pour vous proposer des publicités en fonction de vos centres d'intérêt. Ils peuvent aussi être utilisés pour limiter le nombre de fois que vous voyez une publicité.</li>
                <li><strong>Cookies tiers :</strong> Certains cookies sont placés par des services tiers (comme Google Analytics ou des boutons de partage sur les réseaux sociaux). Nous n'avons aucun contrôle sur l'utilisation de ces cookies, et vous devez consulter les politiques de confidentialité des services tiers pour plus d'informations.</li>
            </ul>

            <h4>Comment gérer les cookies</h4>
            <p>Lorsque vous accédez à notre site pour la première fois, vous êtes invité à accepter ou à refuser l'utilisation des cookies. Vous pouvez également gérer vos préférences de cookies à tout moment en modifiant les paramètres de votre navigateur. Voici comment :</p>
            <ul>
                <li><a href="https://www.aboutcookies.org/">Gérer les cookies dans votre navigateur</a></li>
                <li>Les utilisateurs peuvent aussi supprimer les cookies manuellement à tout moment à partir des paramètres de leur navigateur.</li>
            </ul>

            <h4>Durée de conservation des cookies</h4>
            <p>Les cookies que nous utilisons sont stockés sur votre appareil pour une période déterminée, en fonction du type de cookie. Les cookies de session sont supprimés lorsque vous fermez votre navigateur, tandis que les cookies persistants restent sur votre appareil jusqu'à leur expiration ou jusqu'à ce que vous les supprimiez.</p>

            <h4>Cookies de tiers</h4>
            <p>Nous utilisons des services tiers tels que Google Analytics et les boutons de réseaux sociaux qui peuvent également installer leurs propres cookies sur votre appareil. Nous vous conseillons de consulter leurs politiques de confidentialité et de cookies pour plus de détails.</p>

            <h4>Consentement</h4>
            <p>En poursuivant votre navigation sur notre site après avoir vu la bannière de cookies, vous acceptez l'utilisation de cookies conformément à cette politique. Vous pouvez retirer votre consentement à tout moment en modifiant les paramètres de votre navigateur.</p>

            <h4>Contact</h4>
            <p>Si vous avez des questions concernant l'utilisation des cookies sur ce site, vous pouvez nous contacter à l'adresse suivante : <a href="mailto:privacy@ecoride.com">privacy@ecoride.com</a></p>
</div>
    </main>
    
    <footer>
        <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
    </footer>

    <!-- Script JavaScript -->
    <script src="/frontend/js/accueil.js"></script>
</body>
</html>
