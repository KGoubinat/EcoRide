<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un employé
if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] != 'employe') {
    echo "Accès interdit. Vous devez être un employé pour accéder à cette page.";
    exit();
}

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

// Récupérer les covoiturages problématiques
$query = "
    SELECT 
        tr.id AS troublesome_id,
        tr.comment, 
        tr.status,
        c.id AS ride_id, 
        c.date AS ride_date,
        c.depart AS departure_location,
        c.destination AS arrival_location,
        c.heure_depart AS departure_time,
        c.heure_arrivee AS arrival_time,
        u1.firstName AS user_first_name, 
        u1.lastName AS user_last_name, 
        u1.email AS user_email,
        u2.firstName AS driver_first_name, 
        u2.lastName AS driver_last_name, 
        u2.email AS driver_email
    FROM troublesome_rides tr
    JOIN covoiturages c ON tr.ride_id = c.id
    JOIN users u1 ON tr.user_id = u1.id
    JOIN users u2 ON tr.driver_id = u2.id
    ORDER BY tr.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->execute();
$troublesomeRides = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Covoiturages Problématiques</title>
    <link rel="stylesheet" href="/frontend/styles.css">
</head>
<body>

<header>            
    <div class="header-container">
        <h1>Covoiturages Problématiques</h1>
        <nav>
            <ul>
                <li><a href="/frontend/employee_dashboard.php">Tableau de bord</a></li>
                <li><a href="/frontend/employee_reviews.php">Gérer les Avis</a></li>
                <li><a href="/frontend/employee_troublesome_rides.php">Covoiturages Problématiques</a></li>
                <li><a href="/frontend/logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="covoit">
    <div class="troublesome-rides-list">
        <?php if (count($troublesomeRides) > 0): ?>
            <h2>Voici les covoiturages qui se sont mal passés :</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID du Covoiturage</th>
                        <th>Participant</th>
                        <th>Conducteur</th>
                        <th>Date de Départ</th>
                        <th>Heure de Départ</th>
                        <th>Heure d'Arrivée</th>
                        <th>Lieu de Départ</th>
                        <th>Lieu d'Arrivée</th>
                        <th>Commentaire</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($troublesomeRides as $ride): ?>
                        <tr>
                            <td><?= htmlspecialchars($ride['ride_id']) ?></td>
                            <td>
                                <?= htmlspecialchars($ride['user_first_name']) . ' ' . htmlspecialchars($ride['user_last_name']) ?><br>
                                Email : <?= htmlspecialchars($ride['user_email']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($ride['driver_first_name']) . ' ' . htmlspecialchars($ride['driver_last_name']) ?><br>
                                Email : <?= htmlspecialchars($ride['driver_email']) ?>
                            </td>
                            <td><?= htmlspecialchars($ride['ride_date']) ?></td>
                            <td><?= htmlspecialchars($ride['departure_time']) ?></td>
                            <td><?= htmlspecialchars($ride['arrival_time']) ?></td>
                            <td><?= htmlspecialchars($ride['departure_location']) ?></td>
                            <td><?= htmlspecialchars($ride['arrival_location']) ?></td>
                            <td><?= htmlspecialchars($ride['comment']) ?></td>
                            <td><?= htmlspecialchars($ride['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Aucun covoiturage problématique n'a été signalé pour le moment.</p>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
</footer>

</body>
</html>
