<?php
// Démarrer la session PHP
session_start(); 


// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']); // Vérifie si l'identifiant utilisateur est présent dans la session


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

// Liste des villes autorisées
$villesFrance = [
    "Paris", "Marseille", "Lyon", "Toulouse", "Nice", "Nantes", "Strasbourg",
    "Montpellier", "Bordeaux", "Lille", "Rennes", "Reims", "Le Havre",
    "Saint-Étienne", "Toulon", "Grenoble", "Dijon", "Angers", "Nîmes", "Villeurbanne"
];

// Récupération et nettoyage des entrées
$start = isset($_GET['start']) ? trim($_GET['start']) : '';
$end = isset($_GET['end']) ? trim($_GET['end']) : '';
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
$date = isset($_GET['date']) ? $_GET['date'] : '';  // Nouveau champ "date"
$ecolo = isset($_GET['ecolo']) ? $_GET['ecolo'] : '';
$prix = isset($_GET['prix']) ? $_GET['prix'] : '';
$duree = isset($_GET['duree']) ? $_GET['duree'] : '';
$note = isset($_GET['note']) ? $_GET['note'] : '';

// Construire la requête SQL avec des conditions dynamiques
$sql = "SELECT * FROM covoiturages WHERE 1=1";
$params = [];

// Filtre départ
if ($start) {
    $sql .= " AND depart LIKE ?";
    $params[] = "%$start%";
}

// Filtre destination
if ($end) {
    $sql .= " AND destination LIKE ?";
    $params[] = "%$end%";
}

// Filtre date (nouveau filtre)
if ($date) {
    $sql .= " AND date = ?";
    $params[] = $date;
}

// Filtre passagers
if ($passengers) {
    $sql .= " AND places_restantes >= ?";
    $params[] = $passengers;
}

// Filtre écologique
if ($ecolo) {
    $sql .= " AND ecologique = ?";
    $params[] = ($ecolo === 'oui') ? 1 : 0;
}

// Filtre prix
if ($prix) {
    $sql .= " AND prix <= ?";
    $params[] = $prix;
}

// Filtre durée
if ($duree) {
    $sql .= " AND duree <= ?";
    $params[] = $duree;
}

// Filtre note
if ($note) {
    $sql .= " AND note >= ?";
    $params[] = $note;
}

// Préparer et exécuter la requête
$stmt = $conn->prepare($sql);
$stmt->execute($params);

// Récupérer les résultats
$covoiturages = $stmt->fetchAll();

// Trouver le premier trajet disponible si aucun covoiturage ne correspond
$suggestedRide = null;
if (empty($covoiturages)) {
    $sql = "SELECT * FROM covoiturages 
            WHERE depart LIKE ? AND destination LIKE ? 
            AND places_restantes >= ? 
            ORDER BY date ASC LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(["%$start%", "%$end%", $passengers]);
    $suggestedRide = $stmt->fetch();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Résultats</title>
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
                    <li><a href="/frontend/contact-info">Contact</a></li>
                    <li><a href="/frontend/Covoiturages.php">Covoiturages</a></li>
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

    <main class="adaptation">
        <div class="card">
            <div class="card-body-filters">
                <h2>FILTRES</h2>

                <div class="filters">
                    <label>Voiture écologique :</label>
                    <input type="radio" id="ecolo-oui" name="ecolo" value="oui" onchange="applyFilters()" 
                        <?php if ($ecolo === 'oui') echo 'checked'; ?>>
                    <label for="ecolo-oui">Oui</label>

                    <input type="radio" id="ecolo-non" name="ecolo" value="non" onchange="applyFilters()" 
                        <?php if ($ecolo === 'non') echo 'checked'; ?>>
                    <label for="ecolo-non">Non</label>
                </div>

                <div class="filters">
                    <label for="prix">Prix maximum :</label>
                    <select id="prix" name="prix" onchange="applyFilters()">
                        <option value="">-- Sélectionnez une tranche --</option>
                        <option value="10" <?php if ($prix === '10') echo 'selected'; ?>>10€ ou moins</option>
                        <option value="20" <?php if ($prix === '20') echo 'selected'; ?>>20€ ou moins</option>
                        <option value="30" <?php if ($prix === '30') echo 'selected'; ?>>30€ ou moins</option>
                        <option value="50" <?php if ($prix === '50') echo 'selected'; ?>>50€ ou moins</option>
                        <option value="100" <?php if ($prix === '100') echo 'selected'; ?>>100€ ou moins</option>
                    </select>
                </div>

                <div class="filters">
                    <label for="duree">Durée du voyage :</label>
                    <select id="duree" name="duree" onchange="applyFilters()">
                        <option value="">-- Sélectionnez une durée --</option>
                        <option value="30" <?php if ($duree === '30') echo 'selected'; ?>>30 minutes ou moins</option>
                        <option value="60" <?php if ($duree === '60') echo 'selected'; ?>>1 heure ou moins</option>
                        <option value="120" <?php if ($duree === '120') echo 'selected'; ?>>2 heures ou moins</option>
                        <option value="180" <?php if ($duree === '180') echo 'selected'; ?>>3 heures ou moins</option>
                        <option value="240" <?php if ($duree === '240') echo 'selected'; ?>>4 heures ou moins</option>
                    </select>
                </div>

                <div class="filters">
                    <label for="note">Note minimale :</label>
                    <select id="note" name="note" onchange="applyFilters()">
                        <option value="">-- Sélectionnez une note --</option>
                        <option value="1" <?php if ($note === '1') echo 'selected'; ?>>1 étoile</option>
                        <option value="2" <?php if ($note === '2') echo 'selected'; ?>>2 étoiles</option>
                        <option value="3" <?php if ($note === '3') echo 'selected'; ?>>3 étoiles</option>
                        <option value="4" <?php if ($note === '4') echo 'selected'; ?>>4 étoiles</option>
                        <option value="5" <?php if ($note === '5') echo 'selected'; ?>>5 étoiles</option>
                    </select>    
                </div>

                <div class="filters">
                    <label for="depart">Départ :</label>
                    <select id="depart" name="start" onchange="applyFilters()">
                        <option value="">-- Sélectionnez une ville de départ --</option>
                        <?php foreach ($villesFrance as $ville): ?>
                            <option value="<?= htmlspecialchars($ville) ?>" <?php if ($start === $ville) echo 'selected'; ?>>
                                <?= htmlspecialchars($ville) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filters">
                    <label for="arrivee">Arrivée :</label>
                    <select id="arrivee" name="end" onchange="applyFilters()">
                        <option value="">-- Sélectionnez une ville d'arrivée --</option>
                        <?php foreach ($villesFrance as $ville): ?>
                            <option value="<?= htmlspecialchars($ville) ?>" <?php if ($end === $ville) echo 'selected'; ?>>
                                <?= htmlspecialchars($ville) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filters">
                    <label for="passengers">Passagers :</label>
                    <select id="passengers" name="passengers" onchange="applyFilters()">
                        <option value="">-- Sélectionnez le nombre de passagers --</option>
                        <option value="1" <?php if ($passengers == 1) echo 'selected'; ?>>1</option>
                        <option value="2" <?php if ($passengers == 2) echo 'selected'; ?>>2</option>
                        <option value="3" <?php if ($passengers == 3) echo 'selected'; ?>>3</option>
                        <option value="4" <?php if ($passengers == 4) echo 'selected'; ?>>4</option>
                        <option value="5" <?php if ($passengers == 5) echo 'selected'; ?>>5</option>

                    </select>
                </div>
                
                <div class="filters">
                    <label for="date">Date du voyage :</label>
                    <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="applyFilters()">
                </div>
            </div>
        </div>

        <!-- Affichage des résultats des covoiturages -->
        <?php if (count($covoiturages) > 0): ?>
            <?php foreach ($covoiturages as $covoiturage): ?>
                <div class="card">
                    <div class="card-header">
                        <?php
                        // Récupérer la photo du conducteur
                        $stmtPhoto = $conn->prepare("SELECT photo FROM users WHERE id = ?");
                        $stmtPhoto->execute([$covoiturage['conducteur_id']]); // Assure-toi que 'conducteur_id' existe dans $covoiturage
                        $photo = $stmtPhoto->fetchColumn();

                        // Afficher la photo du conducteur
                        if (!empty($photo) && file_exists($photo)) {
                            echo '<img src="' . htmlspecialchars($photo) . '" alt="Photo de ' . htmlspecialchars($covoiturage['conducteur']) . '">';
                        } else {
                            // Afficher l'image par défaut si pas de photo
                            echo '<img src="/frontend/images/default-avatar.png" alt="Photo de ' . htmlspecialchars($covoiturage['conducteur']) . '">';
                        }
                        ?>
                    </div>
                    <div class="card-body">
                        <h2><?= htmlspecialchars($covoiturage['conducteur']) ?> <span><?= htmlspecialchars($covoiturage['note']) ?>/5</span></h2>
                        <p>Départ : <?= htmlspecialchars($covoiturage['depart']) ?><br>
                        Arrivée : <?= htmlspecialchars($covoiturage['destination']) ?><br>
                        Places restantes : <?= htmlspecialchars($covoiturage['places_restantes']) ?><br>
                        Tarif : <?= htmlspecialchars($covoiturage['prix']) ?>€<br>
                        Le : <?= date('d/m/Y', strtotime($covoiturage['date'])) ?> à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?><br> <!-- Affichage de l'heure de départ -->
                        Durée du trajet : <?= date('H\h i\m', strtotime($covoiturage['duree'])) ?><br>
                        Voyage écologique : <?= $covoiturage['ecologique'] ? 'Oui' : 'Non' ?></p>

                        <a href="http://localhost/ecoride/frontend/details.php?id=<?= $covoiturage['id'] ?>&start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&date=<?= urlencode($date) ?>&passengers=<?= urlencode($passengers) ?>&ecolo=<?= urlencode($ecolo) ?>&prix=<?= urlencode($prix) ?>&duree=<?= urlencode($duree) ?>&note=<?= urlencode($note) ?>" class="btn-detail">+ d'informations</a>

                    </div>
                </div>
            <?php endforeach; ?>
            <?php elseif ($suggestedRide): ?>
                <div class="suggestion">
                    <p>Aucun covoiturage trouvé pour cette date.</p>
                    <p>Premier itinéraire le plus proche le <strong><?= htmlspecialchars((new DateTime($suggestedRide['date']))->format('d/m/Y')) ?></strong> à <?= date('H:i', strtotime($suggestedRide['heure_depart'])) ?></p> <!-- Affichage de l'heure de départ pour la suggestion -->
                    <a href="?start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&date=<?= urlencode($suggestedRide['date'])?>" class="btn">Voir ce trajet</a>
                </div>
            <?php else: ?>
                <div>
                    <p class="no-results">Aucun covoiturage trouvé avec ces critères.</p>
                </div>
            <?php endif; ?>

    </main>
    
    <footer>
        <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
    </footer>

    <!-- Script JavaScript -->
    <script src="/frontend/js/filtres.js"></script>
    <script>
            document.addEventListener("DOMContentLoaded", function () {
            // Gestion du menu burger
            const menuToggle = document.getElementById("menu-toggle");
            const mobileMenu = document.getElementById("mobile-menu");

            if (menuToggle && mobileMenu) {
                menuToggle.addEventListener("click", function () {
                    mobileMenu.classList.toggle("active");
                });

                // Fermer le menu après un clic sur un lien
                document.querySelectorAll("#mobile-menu a").forEach(link => {
                    link.addEventListener("click", function () {
                        mobileMenu.classList.remove("active");
                    });
                });
            }
        });
    </script>
</body>
</html>
