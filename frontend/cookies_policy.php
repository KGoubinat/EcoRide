<?php
session_start(); // Commencer la session

// Connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=ecoride';
$username = 'root';
$password = 'nouveau_mot_de_passe'; // Mets ton mot de passe ici si nécessaire
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];
try {
    $pdo = new PDO($dsn, $username, $password, $options);  
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer la liste des villes
    $stmt = $pdo->query("SELECT nom FROM villes ORDER BY nom ASC");
    $villes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>EcoRide</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="accueil.php">Accueil</a></li>
                    <li><a href="contact_info.php">Contact</a></li>
                    <li><a href="Covoiturages.php">Covoiturages</a></li>
                    <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                    <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                </ul>
            </nav>

        </div>
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
        <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
    </footer>

    <!-- Script JavaScript -->
    <script src="js/accueil.js"></script>
</body>
</html>
