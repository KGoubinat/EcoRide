<?php
session_start();

// Vérifier si l'utilisateur est connecté et s'il est un employé
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employe') {
    header("Location: accueil.php");
    exit;
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
} catch (PDOException $e) {
    die("Impossible de se connecter à la base de données : " . $e->getMessage());
}

// Récupérer les avis en attente de validation
$stmt = $pdo->query("SELECT r.id, r.user_id, r.driver_id, r.rating, r.comment, r.status, u.firstName AS user_firstname, u.lastName AS user_lastname, d.firstName AS driver_firstname, d.lastName AS driver_lastname
                     FROM reviews r
                     LEFT JOIN users u ON r.user_id = u.id
                     LEFT JOIN users d ON r.driver_id = d.id
                     WHERE r.status = 'pending'");

$reviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Avis</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
    
    <div class="header-container">
            <h1>Gérer les Avis</h1>
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

<main class=covoit>
    <section class=validation> 
        <h2>Avis en Attente de Validation</h2>

        <?php if (empty($reviews)) { ?>
            <p>Aucun avis en attente de validation.</p>
        <?php } else { ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Chauffeur</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review) { ?>
                        <tr>
                            <td><?php echo $review['id']; ?></td>
                            <td><?php echo $review['user_firstname'] . ' ' . $review['user_lastname']; ?></td>
                            <td><?php echo $review['driver_firstname'] . ' ' . $review['driver_lastname']; ?></td>
                            <td><?php echo $review['rating']; ?></td>
                            <td><?php echo $review['comment']; ?></td>
                            <td>
                            <a href="approve_review.php?id=<?php echo $review['id']; ?>&status=approved">Valider</a> |
                            <a href="approve_review.php?id=<?php echo $review['id']; ?>&status=rejected">Refuser</a>

                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </section>
</main>
<footer>
    <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>

</body>
</html>
