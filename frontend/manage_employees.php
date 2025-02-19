<?php
session_start(); // Démarrer la session

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

// Récupérer les employés depuis la base de données
$stmt = $pdo->query("SELECT id, firstName, lastName, email, role, etat FROM users WHERE role = 'employe'");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Employés</title>
    <link rel="stylesheet" href="/frontend/styles.css">
</head>
<body>
    
        <header>
        <div class="header-container">
            <h1>Gestion des Employés</h1>
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
    


    <main class=covoit>
        <section class=listEmployee>
            <h2>Liste des Employés</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>État</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = $stmt->fetch()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['firstName'] . " " . $row['lastName'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['role'] . "</td>";
                        echo "<td>" . ucfirst($row['etat']) . "</td>"; // Affiche 'Active' ou 'Suspended'
                        if ($row['etat'] === 'active') {
                            echo "<td><a href='/frontend/suspend_employee.php?id=" . $row['id'] . "'>Suspendre</a> | <a href='/frontend/edit_employee.php?id=" . $row['id'] . "'>Modifier</a></td>";
                        } else {
                            echo "<td><a href='/frontend/activate_employee.php?id=" . $row['id'] . "'>Activer</a> | <a href='/frontend/edit_employee.php?id=" . $row['id'] . "'>Modifier</a></td>";
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
</body>
</html>
