<?php
session_start();
header("Content-Type: application/json");

// Récupérer l'URL de la base de données
$databaseUrl = getenv('JAWSDB_URL');
if (!$databaseUrl) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur : Variable d'environnement JAWSDB_URL non définie"]);
    exit;
}

$parsedUrl = parse_url($databaseUrl);
$servername = $parsedUrl['host'];
$username = $parsedUrl['user'];
$password = $parsedUrl['pass'];
$dbname = ltrim($parsedUrl['path'], '/');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur de connexion à la base de données"]);
    exit;
}

// Lire et décoder le JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Format JSON invalide"]);
    exit;
}

// Vérification des champs requis
if (!isset($data['firstName'], $data['lastName'], $data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Tous les champs sont obligatoires"]);
    exit;
}

// Nettoyage et validation des données
$firstName = trim($data['firstName']);
$lastName = trim($data['lastName']);
$email = trim($data['email']);
$password = trim($data['password']);

if (!preg_match("/^[\p{L}\s'-]+$/u", $firstName) || strlen($firstName) < 2 || strlen($firstName) > 50) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Prénom invalide"]);
    exit;
}
if (!preg_match("/^[\p{L}\s'-]+$/u", $lastName) || strlen($lastName) < 2 || strlen($lastName) > 50) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Nom invalide"]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email invalide"]);
    exit;
}
if (strlen($password) < 8 || strlen($password) > 32 ||
    !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) || !preg_match('/[\W_]/', $password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Mot de passe trop faible"]);
    exit;
}

// Vérifier si l'email existe déjà
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$userExists = $stmt->fetch();

if ($userExists) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email déjà utilisé"]);
    exit;
}

// Hachage du mot de passe et insertion
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, password) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$firstName, $lastName, $email, $hashed_password])) {
    $userId = $conn->lastInsertId();
    echo json_encode(["success" => true, "message" => "Inscription réussie", "user_id" => $userId]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur lors de l'inscription"]);
}
?>
