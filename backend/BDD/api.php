<?php
// Inclusion de la connexion à la base de données
include('db.php');

// Fonction pour envoyer une réponse JSON
function sendResponse($status_code, $data) {
    http_response_code($status_code);
    echo json_encode($data);
}

// Logique pour gérer les requêtes API
$method = $_SERVER['REQUEST_METHOD'];  // Méthode HTTP
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);  // URL demandée
$uri = explode('/', $uri);  // Décompose l'URL pour récupérer les parties

$id = isset($uri[2]) ? $uri[2] : null;  // Si un ID est présent dans l'URL

// Gestion des requêtes selon la méthode HTTP
switch ($method) {
    case 'GET':
        if ($id) {
            // Cas où un ID est présent, on récupère un utilisateur spécifique
            $user = getUserById($id);
            if ($user) {
                sendResponse(200, $user);
            } else {
                sendResponse(404, ['message' => 'Utilisateur non trouvé']);
            }
        } else {
            // Récupérer tous les utilisateurs
            $users = getAllUsers();
            sendResponse(200, $users);
        }
        break;

    case 'POST':
        // Créer un nouvel utilisateur
        $inputData = json_decode(file_get_contents("php://input"), true);
        if (isset($inputData['firstName'], $inputData['lastName'], $inputData['email'], $inputData['password'])) {
            $userId = createUser($inputData['firstName'], $inputData['lastName'], $inputData['email'], $inputData['password']);
            sendResponse(201, ['message' => 'Utilisateur créé', 'userId' => $userId]);
        } else {
            sendResponse(400, ['message' => 'Paramètres manquants']);
        }
        break;

    case 'PUT':
        if ($id) {
            // Mettre à jour un utilisateur existant par son ID
            $inputData = json_decode(file_get_contents("php://input"), true);
            if (isset($inputData['firstName'], $inputData['lastName'], $inputData['email'], $inputData['password'])) {
                updateUser($id, $inputData['firstName'], $inputData['lastName'], $inputData['email'], $inputData['password']);
                sendResponse(200, ['message' => 'Utilisateur mis à jour']);
            } else {
                sendResponse(400, ['message' => 'Paramètres manquants']);
            }
        } else {
            sendResponse(400, ['message' => 'ID manquant']);
        }
        break;

    case 'DELETE':
        if ($id) {
            // Supprimer un utilisateur par son ID
            deleteUser($id);
            sendResponse(200, ['message' => 'Utilisateur supprimé']);
        } else {
            sendResponse(400, ['message' => 'ID manquant']);
        }
        break;

    default:
        sendResponse(405, ['message' => 'Méthode HTTP non autorisée']);
        break;
}

// Fonctions pour interagir avec la base de données
function getUserById($id) {
    global $conn;
    $sql = "SELECT * FROM utilisateurs WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllUsers() {
    global $conn;
    $sql = "SELECT * FROM utilisateurs";
    $stmt = $conn->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createUser($firstName, $lastName, $email, $password) {
    global $conn;
    $sql = "INSERT INTO utilisateurs (firstName, lastName, email, password) VALUES (:firstName, :lastName, :email, :password)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':firstName' => $firstName, ':lastName' => $lastName, ':email' => $email, ':password' => password_hash($password, PASSWORD_DEFAULT)]);
    return $conn->lastInsertId();
}

function updateUser($id, $firstName, $lastName, $email, $password) {
    global $conn;
    $sql = "UPDATE utilisateurs SET firstName = :firstName, lastName = :lastName, email = :email, password = :password WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':firstName' => $firstName, ':lastName' => $lastName, ':email' => $email, ':password' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);
}

function deleteUser($id) {
    global $conn;
    $sql = "DELETE FROM utilisateurs WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $id]);
}
?>
