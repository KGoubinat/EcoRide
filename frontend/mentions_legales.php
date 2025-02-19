
<?php
session_start(); // Commencer la session

if (isset($_SESSION['user_email'])) {
    echo "Utilisateur connecté : " . $_SESSION['user_email'];
} else {
    echo "Aucun utilisateur connecté.";
}


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
        <div class=contact>
        <h3>Mentions Légales :</h3>

        <h4>Éditeur du site</h4>
        <p>EcoRide, société à responsabilité limitée (SARL) au capital de 100 000 €</p>
        <p>Siège social : 123 Rue de l'Innovation, 75000 Paris, France</p>
        <p>Email : contact@ecoride.com</p>
        <p>Téléphone : 01 23 45 67 89</p>
        <p>Numéro SIREN : 123 456 789</p>

        <h4>Directeur de publication</h4>
        <p>Jean Dupont, Directeur général de EcoRide</p>

        <h4>Hébergement</h4>
        <p>Hébergeur : OVH</p>
        <p>Adresse : 2 Rue Kellermann, 59100 Roubaix, France</p>

        <h4>Conditions Générales d'Utilisation (CGU)</h4>
        <ul>
            <li>En accédant à ce site, vous acceptez les conditions d'utilisation suivantes :</li>
            <li>Le contenu du site est protégé par des droits d'auteur.</li>
            <li>L'éditeur ne peut être tenu responsable des dommages liés à l'utilisation du site.</li>
        </ul>

        <h4>Politique de Confidentialité</h4>
        <p>Les données personnelles sont collectées à des fins de gestion des comptes utilisateurs, etc. Pour plus d'informations, consultez notre politique de confidentialité.</p>

        <h4>Cookies</h4>
        <p>Ce site utilise des cookies pour améliorer votre expérience. Consultez notre <a href="cookies_policy.php">politique de cookies</a>.</p>

        <h4>Litiges</h4>
        <p>Les litiges seront résolus par les tribunaux de Paris, France, sous la législation française.</p>
    </main>
    
    <footer>
        <p>EcoRide@gmail.com / <a href="#">Mentions légales</a></p>
    </footer>

    <!-- Script JavaScript -->
    <script src="js/accueil.js"></script>
</body>
</html>
