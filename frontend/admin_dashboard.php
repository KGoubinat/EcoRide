<?php
session_start();


// Vérifier si l'utilisateur est connecté et si c'est un administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
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
    echo "Connexion réussie à la base de données MySQL.";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}
// Fonction pour récupérer le nombre de covoiturages par jour
function getCarpoolData($pdo) {
    $stmt = $pdo->query("
        SELECT DATE(date) AS jour, COUNT(*) AS total_covoiturages
        FROM covoiturages
        GROUP BY jour
        ORDER BY jour ASC
    ");

    $data = [];
    while ($row = $stmt->fetch()) {
        $data[] = [
            'jour' => $row['jour'],
            'total_covoiturages' => $row['total_covoiturages']
        ];
    }
    return $data;
}

// Récupérer les données pour le graphique du nombre de covoiturages
$carpoolData = getCarpoolData($pdo);

// Formatage des données pour Chart.js
$carpoolLabels = [];
$carpoolValues = [];
foreach ($carpoolData as $data) {
    $carpoolLabels[] = $data['jour'];  // Liste des dates
    $carpoolValues[] = $data['total_covoiturages'];  // Liste des nombres de covoiturages
}

// Fonction pour récupérer les crédits gagnés par jour
function getCreditsData($pdo) {
    $stmt = $pdo->query("
        SELECT DATE(date) AS jour, COUNT(*) * 2 AS total_credits
        FROM covoiturages
        WHERE statut = 'terminé'
        GROUP BY jour
        ORDER BY jour ASC
    ");

    $data = [];
    while ($row = $stmt->fetch()) {
        $data[] = [
            'jour' => $row['jour'],
            'total_credits' => $row['total_credits']
        ];
    }
    return $data;
}

// Récupérer les données pour le graphique des crédits
$creditsData = getCreditsData($pdo);

// Formatage des données pour Chart.js
$creditsLabels = [];
$creditsValues = [];
foreach ($creditsData as $data) {
    $creditsLabels[] = $data['jour'];  // Liste des dates
    $creditsValues[] = $data['total_credits'];  // Liste des crédits gagnés
}

// Requête pour calculer le total des crédits
$stmt = $pdo->query("SELECT COUNT(*) as total_covoiturages FROM covoiturages WHERE statut = 'terminé'");
$row = $stmt->fetch();
$totalCovoiturages = $row['total_covoiturages'];

// Chaque covoiturage terminé rapporte 2 crédits
$totalCredits = $totalCovoiturages * 2;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Tableau de bord Administrateur</title>

    <!-- Bibliothèque pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
    <div class="header-container">
        <h1>Bienvenue, Administrateur</h1>
            <nav>
                <ul>
                    <li><a href="/frontend/admin_dashboard.php">Tableau de bord</a></li>
                    <li><a href="/frontend/add_employee.html">Ajouter un Employé</a></li>
                    <li><a href="/frontend/manage_employees.php">Gérer les Employés</a></li>
                    <li><a href="/frontend/manage_users.php">Gérer les Utilisateurs</a></li>
                    <li><a href="/frontend/logout.php">Déconnexion</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class=adaptation>
        <section class=stats>
            <h2>Statistiques de la plateforme</h2>
            <!-- Graphiques -->
            <div>
                <h3>Nombre de Covoiturages par Jour</h3>
                <canvas id="carpoolChart" width="800" height="400"></canvas>
            </div>
            <div>
                <h3>Crédit Gagné par Jour</h3>
                <canvas id="creditChart" width="800" height="400"></canvas>
            </div>
            <div>
                <h3>Total de Crédit Gagné par la Plateforme</h3>
                <p id="totalCredits"></p>
            </div>
        </section>

        <section class=comptes>
            <h2>Gérer les Comptes</h2>
            <h3>Liste des Employés et Utilisateurs</h3>
            <!-- Tableau des utilisateurs avec options pour suspendre leurs comptes -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Liste des utilisateurs avec option de suspension -->
                    <?php
                    // Connexion à la base de données pour récupérer les utilisateurs
                    // Exemple de requête pour afficher les utilisateurs
                    $stmt = $pdo->query("SELECT id, firstName, lastName, etat, email, role FROM users");
                    while ($row = $stmt->fetch()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>"; 
                        echo "<td>" . $row['firstName'] . " " . $row['lastName'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['role'] . "</td>";
                        echo "<td>" . ucfirst($row['etat']) . "</td>"; // Affiche 'Active' ou 'Suspended'
                        if ($row['etat'] === 'active') {
                            echo "<td><a href='/frontend/suspend_user.php?id=" . $row['id'] . "'>Suspendre</a></td>";
                        } else {
                            echo "<td><a href='/frontend/active_user.php?id=" . $row['id'] . "'>Activer</a></td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </main>
    <footer>
        <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
    </footer>

    <script>
        // Exemple de code JavaScript pour afficher les graphiques avec Chart.js
        const carpoolChartData = {
            labels: <?php echo json_encode($carpoolLabels); ?>,
            datasets: [{
                label: 'Nombre de Covoiturages',
                data: <?php echo json_encode($carpoolValues); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        };

        const creditChartData = {
            labels: <?php echo json_encode($creditsLabels); ?>,
            datasets: [{
                label: 'Crédit Gagné',
                data: <?php echo json_encode($creditsValues); ?>,
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        };

        const ctx1 = document.getElementById('carpoolChart').getContext('2d');
        const carpoolChart = new Chart(ctx1, {
            type: 'bar',
            data: carpoolChartData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const ctx2 = document.getElementById('creditChart').getContext('2d');
        const creditChart = new Chart(ctx2, {
            type: 'line',
            data: creditChartData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Afficher le total de crédit gagné
        const totalCredits = <?php echo $totalCredits; ?>;
        document.getElementById("totalCredits").textContent = "Total des crédits gagnés: " + totalCredits + " crédits";
    </script>
</body>
</html>
