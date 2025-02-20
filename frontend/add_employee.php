<?php
// Connexion à la base de données avec PDO
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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastName = $_POST['lastName'];
    $firstName = $_POST['firstName']; 
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'employe'; // On définit directement le rôle ici

    $sql = "INSERT INTO users (lastName, firstName, email, password, role) VALUES (:lastName, :firstName, :email, :password, :role)";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':lastName' => $lastName,
            ':firstName' => $firstName,
            ':email' => $email,
            ':password' => $password,
            ':role' => $role // Le rôle est bien défini ici
        ]);
        echo json_encode(["message" => "Employé ajouté avec succès avec le statut d'employé."]);
    } catch (PDOException $e) {
        echo json_encode(["error" => "Erreur : " . $e->getMessage()]);
    }
}
?>
