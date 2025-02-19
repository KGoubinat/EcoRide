<?php
session_start();

header("Content-Type: application/json"); // Renvoie du JSON

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

// Lire les données envoyées en JSON
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Format JSON invalide"]);
    exit;
}

// Vérifier si toutes les valeurs sont présentes
if (!isset($data['firstName'], $data['lastName'], $data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Tous les champs doivent être remplis"]);
    exit;
}

// Récupérer et nettoyer les données
$firstName = trim($data['firstName']);
$lastName = trim($data['lastName']);
$email = trim($data['email']);
$password = trim($data['password']);

// Vérification des champs vides
if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Tous les champs doivent être remplis"]);
    exit;
}

// Validation de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "L'adresse email n'est pas valide"]);
    exit;
}

// Vérifier si l'email existe déjà
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Cet email est déjà utilisé"]);
    exit;
}

// Vérification de la sécurité du mot de passe
if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial"]);
    exit;
}

// Hachage du mot de passe
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insérer l'utilisateur dans la base de données
$stmt = $pdo->prepare("INSERT INTO users (firstName, lastName, email, password) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$firstName, $lastName, $email, $hashed_password])) {
    // Récupérer l'ID de l'utilisateur inséré
    $userId = $pdo->lastInsertId();

    // Insérer les 20 crédits dans la table des crédits (par exemple, credit_users)
    $stmtCredits = $pdo->prepare("INSERT INTO users_credit (user_id, credits) VALUES (?, ?)");
    $stmtCredits->execute([$userId, 20]); // Attribuer 20 crédits à l'utilisateur

    echo json_encode(["success" => true, "message" => "Inscription réussie !"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur lors de l'inscription"]);
}
?>
