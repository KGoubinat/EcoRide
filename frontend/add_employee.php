<?php
// Connexion à la base de données avec PDO
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
    die("Impossible de se connecter à la base de données : " . $e->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastName = $_POST['lastName'];
    $firstName = $_POST['firstName']; 
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'employe'; // On définit directement le rôle ici

    $sql = "INSERT INTO users (lastName, firstName, email, password, role) VALUES (:lastName, :firstName, :email, :password, :role)";

    try {
        $stmt = $pdo->prepare($sql);
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
