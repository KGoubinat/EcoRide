<?php
session_start(); // Démarrer la session

// Vérifier si l'utilisateur est connecté et si c'est un administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
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

// Récupérer les employés depuis la base de données
$stmt = $pdo->query("SELECT id, firstName, lastName, email, role, etat FROM users WHERE role = 'employe'");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Employés</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    
        <header>
        <div class="header-container">
            <h1>Gestion des Employés</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Tableau de bord</a></li>
                    <li><a href="add_employee.html">Ajouter un Employé</a></li>
                    <li><a href="manage_employees.php">Gérer les Employés</a></li>
                    <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
                    <li><a href="logout.php">Déconnexion</a></li>
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
                            echo "<td><a href='suspend_employee.php?id=" . $row['id'] . "'>Suspendre</a> | <a href='edit_employee.php?id=" . $row['id'] . "'>Modifier</a></td>";
                        } else {
                            echo "<td><a href='activate_employee.php?id=" . $row['id'] . "'>Activer</a> | <a href='edit_employee.php?id=" . $row['id'] . "'>Modifier</a></td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </main>

    <footer>
        <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
    </footer>
</body>
</html>
