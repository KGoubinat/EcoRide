<?php
declare(strict_types=1);

require __DIR__ . '/../frontend/init.php'; // session + BASE_URL + getPDO()

header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = getPDO(); // config unifiée: local (.env) OU prod (JAWSDB_URL)
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base.']);
    exit;
}


// --- Récupération données : JSON ou Form ---
$raw  = file_get_contents("php://input");
$data = json_decode($raw, true);
if ($raw !== '' && $data === null && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "JSON mal formé."]);
    exit;
}
if (!is_array($data)) {
    $data = [
        'firstName' => $_POST['firstName'] ?? null,
        'lastName'  => $_POST['lastName']  ?? null,
        'email'     => $_POST['email']     ?? null,
        'password'  => $_POST['password']  ?? null,
    ];
}

// --- Vérifications champs obligatoires ---
if (!isset($data['firstName'], $data['lastName'], $data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Tous les champs sont obligatoires."]);
    exit;
}

// --- Nettoyage/validation ---
$firstName = trim((string)$data['firstName']);
$lastName  = trim((string)$data['lastName']);
$email     = strtolower(trim((string)$data['email']));
$password  = (string)$data['password'];

if (!preg_match("/^[\p{L}\s'’-]+$/u", $firstName) || mb_strlen($firstName) < 2 || mb_strlen($firstName) > 50) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Prénom invalide."]);
    exit;
}
if (!preg_match("/^[\p{L}\s'’-]+$/u", $lastName) || mb_strlen($lastName) < 2 || mb_strlen($lastName) > 50) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Nom invalide."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email invalide."]);
    exit;
}

// Mot de passe fort (8–72, 1 maj, 1 min, 1 chiffre, 1 spécial)
if (mb_strlen($password) < 8 || mb_strlen($password) > 72 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/\d/',   $password) ||
    !preg_match('/[\W_]/', $password)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Mot de passe trop faible."]);
    exit;
}

// --- Unicité email ---
try {
    $st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $st->execute([$email]);
    if ($st->fetch()) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Email déjà utilisé."]);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erreur interne (vérif email)."]);
    exit;
}

// --- Création utilisateur ---
$hash    = password_hash($password, PASSWORD_DEFAULT);
$credits = 20;
$status  = 'passager';
$role    = 'utilisateur';
$etat    = 'active';

try {
  $sql = 'INSERT INTO users
          (firstName, lastName, email, password, credits, status, role, etat)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
  $pdo->prepare($sql)->execute([
    $firstName, $lastName, $email, $hash, $credits, $status, $role, $etat
  ]);

    // Auto-login
    $userId = (int)$pdo->lastInsertId();
    $_SESSION['user_id']    = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role']  = $role;
    $_SESSION['firstName']  = $firstName;
    $_SESSION['lastName']   = $lastName;

    echo json_encode(["success" => true, "message" => "Inscription réussie."]);
    
    exit;
} catch (PDOException $e) {
    if ((int)$e->errorInfo[1] === 1062) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Email déjà utilisé."]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erreur lors de l'inscription."]);
    }
    exit;
}
