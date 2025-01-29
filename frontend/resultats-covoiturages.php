<?php
// Connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=ecoride';
$username = 'root';
$password = 'nouveau_mot_de_passe';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Impossible de se connecter à la base de données : " . $e->getMessage());
}

// Récupérer les paramètres de recherche depuis l'URL
$start = isset($_GET['start']) ? $_GET['start'] : '';
$end = isset($_GET['end']) ? $_GET['end'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$passengers = isset($_GET['passengers']) ? $_GET['passengers'] : '';

// Construire la requête SQL avec des conditions dynamiques
$sql = "SELECT * FROM covoiturages WHERE 1=1";

$params = [];

if ($start) {
    $sql .= " AND depart LIKE ?";
    $params[] = "%$start%"; // Recherche insensible à la casse
}

if ($end) {
    $sql .= " AND destination LIKE ?";
    $params[] = "%$end%";
}

if ($date) {
    $sql .= " AND date = ?";
    $params[] = $date;
}

if ($passengers) {
    $sql .= " AND passagers >= ?";
    $params[] = $passengers;
}

// Préparer et exécuter la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Récupérer les résultats
$covoiturages = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Résultats</title>
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
                    <li><a href="accueil.html">Accueil</a></li>
                    <li><a href="#">Contact</a></li>
                    <li id="authButton"></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="four-columns">
        <div class="first-column">
            <div class="card">
                <div class="card-body-filters">
                    <h2>FILTRES</h2>
                    <div class="filters">
                        <label>Voiture écologique :</label>
                        <input type="radio" id="ecolo-oui" name="ecolo" value="oui">
                        <label for="ecolo-oui">Oui</label>
                        <input type="radio" id="ecolo-non" name="ecolo" value="non">
                        <label for="ecolo-non">Non</label>
                    </div>
                    <div class="filters">
                        <label for="prix">Prix maximum :</label>
                        <select id="prix" name="prix">
                            <option value="">-- Sélectionnez une tranche --</option>
                            <option value="10">10€ ou moins</option>
                            <option value="20">20€ ou moins</option>
                            <option value="30">30€ ou moins</option>
                            <option value="50">50€ ou moins</option>
                            <option value="100">100€ ou moins</option>
                        </select>
                    </div>
                    <div class="filters">
                        <label for="duree">Durée du voyage :</label>
                        <select id="duree" name="duree">
                            <option value="">-- Sélectionnez une durée --</option>
                            <option value="30min">30 minutes ou moins</option>
                            <option value="1h">1 heure ou moins</option>
                            <option value="2h">2 heures ou moins</option>
                            <option value="3h">3 heures ou moins</option>
                            <option value="4h">4 heures ou moins</option>
                        </select>
                    </div>
                    <div class="filters">
                        <label for="note">Note minimale :</label>
                        <select id="note" name="note">
                            <option value="">-- Sélectionnez une note --</option>
                            <option value="1">1 étoile</option>
                            <option value="2">2 étoiles</option>
                            <option value="3">3 étoiles</option>
                            <option value="4">4 étoiles</option>
                            <option value="5">5 étoiles</option>
                        </select>    
                    </div>
                    <button id="applyFilters" type="button">VALIDER</button>
                </div>
            </div>
        </div>

        <!-- Affichage des résultats des covoiturages -->
        <div class="second-column">
            <h2>Résultats de la recherche</h2>
            <?php if (count($covoiturages) > 0): ?>
                <?php foreach ($covoiturages as $covoiturage): ?>
                    <div class="card">
                        <div class="card-header">
                            <img src="../Images/yasmina.jpg" alt="Photo de Yasmina">
                        </div>
                        <div class="card-body">
                            <h2><?= htmlspecialchars($covoiturage['conducteur']) ?> <span><?= htmlspecialchars($covoiturage['note']) ?>/5</span></h2>
                            <p>Places restantes : <?= htmlspecialchars($covoiturage['places_restantes']) ?><br>
                               Tarif : <?= htmlspecialchars($covoiturage['prix']) ?>€<br>
                               Le : <?= htmlspecialchars($covoiturage['date']) ?><br>
                               Voyage écologique : <?= $covoiturage['ecologique'] ? 'Oui' : 'Non' ?></p>
                            <button>+ d'informations</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun covoiturage trouvé avec ces critères.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <footer>
        <p>EcoRide@gmail.com / <a href="#">Mentions légales</a></p>
    </footer>

    <!-- Script JavaScript -->
    <script src="validation.js"></script>
    <script>
        // Gestion de l'authentification
        document.addEventListener("DOMContentLoaded", function () {
            const authButton = document.getElementById("authButton");

            // Vérifie si l'utilisateur est connecté (exemple simplifié)
            const isLoggedIn = localStorage.getItem("isLoggedIn") === "true";

            // Met à jour le bouton en fonction de l'état de connexion
            function updateAuthButton() {
                if (isLoggedIn) {
                    authButton.innerHTML = `<a href="#" id="logoutBtn">Déconnexion</a>`;
                } else {
                    authButton.innerHTML = `<a href="connexion.html" id="loginBtn">Connexion</a>`;
                }
            }

            // Gère la déconnexion
            authButton.addEventListener("click", function (event) {
                if (event.target.id === "logoutBtn") {
                    event.preventDefault();
                    localStorage.setItem("isLoggedIn", "false");
                    window.location.reload(); // Recharge la page pour mettre à jour l'interface
                }
            });

            updateAuthButton();
        });
    </script>
</body>
</html>
