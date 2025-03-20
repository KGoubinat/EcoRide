<?php
session_start();

// Vérifier si l'utilisateur est connecté et s'il est un employé
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'employe') {
    header("Location: accueil.php");
    exit;
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

// Récupérer les avis en attente de validation
$stmt = $conn->query("SELECT r.id, r.user_id, r.driver_id, r.rating, r.comment, r.status, u.firstName AS user_firstname, u.lastName AS user_lastname, d.firstName AS driver_firstname, d.lastName AS driver_lastname
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
    <link rel="stylesheet" href="/frontend/styles.css">
</head>
<body>

<header>
    
    <div class="header-container">
            <h1>Gérer les Avis</h1>
            <div class="menu-toggle" id="menu-toggle">☰</div>
            <nav id="navbar">
                <ul>
                    <li><a href="/frontend/employee_dashboard.php">Tableau de bord</a></li>
                    <li><a href="/frontend/employee_reviews.php">Gérer les Avis</a></li>
                    <li><a href="/frontend/employee_troublesome_rides.php">Covoiturages Problématiques</a></li>
                    <li><a href="/frontend/logout.php">Déconnexion</a></li>
                </ul>
            </nav>
        </div>
        <!-- Menu mobile (caché par défaut) -->
        <nav id="mobile-menu">
            <ul>
                <li><a href="/frontend/employee_dashboard.php">Tableau de bord</a></li>
                <li><a href="/frontend/employee_reviews.php">Gérer les Avis</a></li>
                <li><a href="/frontend/employee_troublesome_rides.php">Covoiturages Problématiques</a></li>
                <li><a href="/frontend/logout.php">Déconnexion</a></li>
            </ul>
        </nav>
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
                            <a href="/frontend/approve_review.php?id=<?php echo $review['id']; ?>&status=approved">Valider</a> |
                            <a href="/frontend/approve_review.php?id=<?php echo $review['id']; ?>&status=rejected">Refuser</a>

                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php } ?>
    </section>
</main>
<footer>
    <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
</footer>
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
