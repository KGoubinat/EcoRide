<?php
// Démarrer la session PHP
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);

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

// Récupération des paramètres
$start = isset($_GET['start']) ? trim($_GET['start']) : '';
$end = isset($_GET['end']) ? trim($_GET['end']) : '';
$passengers = isset($_GET['passengers']) ? intval($_GET['passengers']) : 1;
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Requête SQL
$sql = "SELECT * FROM covoiturages WHERE depart LIKE ? AND destination LIKE ? AND date >= ? AND places_restantes >= ? ORDER BY date ASC LIMIT 1";
$params = ["%$start%", "%$end%", $date, $passengers];
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$covoiturages = $stmt->fetchAll();

// Trouver la date la plus proche si aucun covoiturage n'est disponible
if (empty($covoiturages)) {
    $sql = "SELECT * FROM covoiturages WHERE depart LIKE ? AND destination LIKE ? AND places_restantes >= ? ORDER BY date ASC LIMIT 1";
    $stmt = $pdo->prepare($sql);
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
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>EcoRide</h1>
        <nav>
            <a href="accueil.php">Accueil</a>
            <a href="#">Contact</a>
        </nav>
    </header>
    <main>
        <?php if (!empty($covoiturages)): ?>
            <?php foreach ($covoiturages as $covoiturage): ?>
                <div class="card">
                    <h2><?= htmlspecialchars($covoiturage['conducteur']) ?> (<?= htmlspecialchars($covoiturage['note']) ?>/5)</h2>
                    <p>Départ : <?= htmlspecialchars($covoiturage['depart']) ?></p>
                    <p>Arrivée : <?= htmlspecialchars($covoiturage['destination']) ?></p>
                    <p>Prix : <?= htmlspecialchars($covoiturage['prix']) ?>€</p>
                    <p>Places restantes : <?= htmlspecialchars($covoiturage['places_restantes']) ?></p>
                    <p>Départ : <?= htmlspecialchars($covoiturage['date']) ?> à <?= htmlspecialchars($covoiturage['heure_depart']) ?></p>
                    <p>Voyage écologique : <?= $covoiturage['ecologique'] ? 'Oui' : 'Non' ?></p>
                    <a href="details-covoiturage.php?id=<?= htmlspecialchars($covoiturage['id']) ?>" class="btn">Détails</a>
                </div>
            <?php endforeach; ?>
        <?php elseif (isset($suggestedRide)): ?>
            <p>Aucun covoiturage trouvé pour cette date. Voulez-vous partir le <?= htmlspecialchars($suggestedRide['date']) ?> ?</p>
            <a href="?start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>&date=<?= urlencode($suggestedRide['date']) ?>">Voir ce trajet</a>
        <?php else: ?>
            <p>Aucun covoiturage disponible.</p>
        <?php endif; ?>
    </main>
    <footer>
        <p>EcoRide@gmail.com</p>
    </footer>
</body>
</html>
