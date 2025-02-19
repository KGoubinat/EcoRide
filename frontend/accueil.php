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
                    <li><a href="covoiturages.php">Covoiturages</a></li>
                    <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                    <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                </ul>
            </nav>

        </div>
    </header>
    
    <main>
        <div class="first-column">
            <section class="presentation">
                <div class="intro">
                    <h2>Bienvenue sur EcoRide !</h2>
                    <p>Rejoignez la plateforme de covoiturage dédiée à la réduction de l'impact environnemental et à l'économie des déplacements. Chez EcoRide, nous facilitons les trajets en voiture partagée pour ceux qui souhaitent voyager de manière plus durable et économique. Que vous soyez conducteur ou passager, notre application vous aide à réduire votre empreinte carbone tout en économisant sur vos déplacements. Ensemble, faisons de chaque trajet un geste pour la planète !</p>
                </div>
            </section>
        </div>

        <div class="second-column">
            <section class="form">
                <div class="formulaire">
                    <h2 class="ecoride-title">EcoRide</h2>
                    <p>Voyagez ensemble, économisez ensemble.</p>
                    <form id="rechercheForm" action="resultatsCovoiturages.php" method="GET">
                        <input list="cities" id="start" placeholder="Départ" name="start" required><br>
                        <input list="cities" id="end" placeholder="Destination" name="end" required><br>
                        <input type="number" id="passengers" placeholder="Passager(s)" name="passengers" min="1" required><br>
                        <input type="date" id="date" name="date" required><br>
                        <div class=button>
                            <button type="submit">Rechercher</button>
                        </div>
                    </form>
                    
                    <datalist id="cities">
                        <?php foreach ($villes as $ville) : ?>
                            <option value="<?= htmlspecialchars($ville) ?>">
                        <?php endforeach; ?>
                    </datalist>


                </div>
                <div id="results"></div>
            </section>
        </div>

        <section class="third-column">
            <section class="details">
                <div class="highlight">
                    <h3>Écologique</h3>
                    <p>Nous croyons en un avenir plus vert grâce à une mobilité durable.</p>
                    <h3>Économique</h3>
                    <p>Partagez les frais de déplacement et économisez sur vos trajets quotidiens.</p>
                    <h3>Convivial</h3>
                    <p>Voyagez en bonne compagnie et faites de nouvelles rencontres.</p>
                </div>
            </section>

            <div class="rejoindre">
                <p>Plateforme intuitive et sécurisée.</p>
                <p>Large choix de trajets adaptés à vos besoins.</p>
                <p>Des conducteurs et passagers vérifiés.</p>
                <a href="register.html"><button type="button" id="rejoindreBtn">Rejoignez nous!</button></a>
            </div>
        </section>
    </main>
    
    <footer>
        <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
    </footer>

    <!-- Script JavaScript -->
    <script src="js/accueil.js"></script>
</body>
</html>
