<?php
session_start();

// Vérifier si l'utilisateur est un employé
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



// Afficher l'ID et le nom de l'employé connecté
$employee_name = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];

$sql = "SELECT MONTH(date) AS month, YEAR(date) AS year, COUNT(*) AS count
        FROM covoiturages
        GROUP BY year, month
        ORDER BY year, month";
$stmt = $pdo->query($sql);
$ride_data = $stmt->fetchAll();
$months = [];
$counts = [];
foreach ($ride_data as $data) {
    $months[] = $data['month'] . '/' . $data['year'];
    $counts[] = $data['count'];
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Employé - Tableau de bord</title>
    <link rel="stylesheet" href="styles.css"> <!-- Inclure votre fichier CSS -->
    

</head>
<body>

    <!-- Entête -->
    <header>
        <div class="header-container">
            <h1>Bienvenue dans votre Espace Employé, <?php echo $employee_name; ?>!</h1>
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

    <!-- Contenu Principal -->
    <main class=adaptation>
        <section class=boardEmployee>
            <h2>Tableau de bord</h2>
            <p>Bienvenue dans votre espace. Vous pouvez gérer les avis des chauffeurs et consulter les covoiturages qui ont eu des problèmes.</p>
            <div>
                <h3>Actions disponibles :</h3>
                <ul>
                    <li><a href="employee_reviews.php">Valider ou Refuser les Avis</a></li>
                    <li><a href="employee_troublesome_rides.php">Consulter les Covoiturages Problématiques</a></li>
                </ul>
            </div>
        </section>


    </main>
    <footer>
    <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>
</body>

</html>
