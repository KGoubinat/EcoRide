<?php
// Démarrer la session PHP
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']); // Vérifie si l'identifiant utilisateur est présent dans la session

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
    die("Erreur de connexion : " . $e->getMessage());
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
                    <li><a href="accueil.php">Accueil</a></li>
                    <li><a href="#">Contact</a></li>
                    <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                    <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="four-columns">
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
                        <img src="../Images/yasmina.jpg" alt="Photo de Yasmina">
                    </div>
                    <div class="card-body">
                        <h2><?= htmlspecialchars($covoiturage['conducteur']) ?> <span><?= htmlspecialchars($covoiturage['note']) ?>/5</span></h2>
                        <p>Départ : <?= htmlspecialchars($covoiturage['depart']) ?><br>
                        Arrivée : <?= htmlspecialchars($covoiturage['destination']) ?><br>
                        Places restantes : <?= htmlspecialchars($covoiturage['places_restantes']) ?><br>
                        Tarif : <?= htmlspecialchars($covoiturage['prix']) ?>€<br>
                        Le : <?= htmlspecialchars($covoiturage['date']) ?><br>
                        Voyage écologique : <?= $covoiturage['ecologique'] ? 'Oui' : 'Non' ?></p>
                        <a href="details-covoiturage.php?id=<?= $covoiturage['id'] ?>" class="btn-detail">+ d'informations</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucun covoiturage trouvé avec ces critères.</p>
        <?php endif; ?>
    </main>
    
    <footer>
        <p>EcoRide@gmail.com / <a href="#">Mentions légales</a></p>
    </footer>

    <!-- Script JavaScript -->
    <script src="js/filtres.js"></script>
</body>
</html>
