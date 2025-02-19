<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un employé
if (!isset($_SESSION['user_email']) || $_SESSION['user_role'] != 'employe') {
    echo "Accès interdit. Vous devez être un employé pour accéder à cette page.";
    exit();
}

// Connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=ecoride';
$username = 'root';
$password = 'nouveau_mot_de_passe'; // Remplacer par ton mot de passe

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
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

$stmt = $pdo->prepare($query);
$stmt->execute();
$troublesomeRides = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Covoiturages Problématiques</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>            
    <div class="header-container">
        <h1>Covoiturages Problématiques</h1>
        <nav>
            <ul>
                <li><a href="employee_dashboard.php">Tableau de bord</a></li>
                <li><a href="employee_reviews.php">Gérer les Avis</a></li>
                <li><a href="employee_troublesome_rides.php">Covoiturages Problématiques</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
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
    <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>

</body>
</html>
