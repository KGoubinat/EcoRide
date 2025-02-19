<?php
session_start();

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
    echo "Connexion réussie à la base de données MySQL.";
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
            <nav>
                <ul>
                    <li><a href="/frontend/accueil.php">Accueil</a></li>
                    <li><a href="/frontend/contact_info.php">Contact</a></li>
                    <li><a href="/frontend/covoiturages.php">Covoiturages</a></li>
                    <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                    <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>" data-user-email="<?= $user_email ?? ''; ?>"></li>
                </ul>
            </nav>

        </div>
    </header>
    
    <main class=covoit>
            <section class="form">
                <div class="formulaire">
                    <h2 class="ecoride-title">EcoRide</h2>
                    <p>Voyagez ensemble, économisez ensemble.</p>
                    <form id="rechercheForm" action="/frontend/resultatsCovoiturages.php" method="GET">
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

        </section>
    </main>
    
    <footer>
        <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
    </footer>

    <!-- Script JavaScript -->
    <script src="/frontend/js/accueil.js"></script>
</body>
</html>
