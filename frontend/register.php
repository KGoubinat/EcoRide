<?php
header("Content-Type: application/json"); // Renvoie du JSON

// Connexion à la base de données avec PDO
$dsn = 'mysql:host=localhost;dbname=ecoride;charset=utf8';
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
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur de connexion à la base de données"]);
    exit;
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

// Vérifier la longueur du mot de passe
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Le mot de passe doit contenir au moins 6 caractères"]);
    exit;
}

// Hachage du mot de passe
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insérer l'utilisateur dans la base de données
$stmt = $pdo->prepare("INSERT INTO users (firstName, lastName, email, password) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$firstName, $lastName, $email, $hashed_password])) {
    echo json_encode(["success" => true, "message" => "Inscription réussie !"]);
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur lors de l'inscription"]);
}
?>
