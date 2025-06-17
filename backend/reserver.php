<?php
header("Content-Type: application/json");
session_start();

// Refuser si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté.']);
    exit();
}

// Vérification du token CSRF
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? '';

if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Échec de vérification CSRF.']);
    exit();
}

// Vérification du type de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit();
}

// Lecture et décodage du JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['ride_id']) || !isset($data['passengers'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit();
}

$ride_id = (int)$data['ride_id'];
$passengers = (int)$data['passengers'];
$user_email = $_SESSION['user_email'];

if ($ride_id <= 0 || $passengers <= 0) {
    echo json_encode(['success' => false, 'message' => 'Paramètres incorrects.']);
    exit();
}

// Connexion base de données
$databaseUrl = getenv('JAWSDB_URL');
$parsedUrl = parse_url($databaseUrl);
$servername = $parsedUrl['host'];
$username = $parsedUrl['user'];
$password = $parsedUrl['pass'];
$dbname = ltrim($parsedUrl['path'], '/');

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer l'utilisateur
    $stmt = $pdo->prepare("SELECT id, credits FROM users WHERE email = ?");
    $stmt->execute([$user_email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
        exit();
    }

    $user_id = $user['id'];
    $credits_dispo = $user['credits'];

    // Récupérer le covoiturage
    $stmt = $pdo->prepare("SELECT prix, places_restantes FROM covoiturages WHERE id = ?");
    $stmt->execute([$ride_id]);
    $covoit = $stmt->fetch();

    if (!$covoit) {
        echo json_encode(['success' => false, 'message' => 'Covoiturage non trouvé.']);
        exit();
    }

    $prix_total = $covoit['prix'] * $passengers;

    // Vérifier crédits et places
    if ($credits_dispo < $prix_total) {
        echo json_encode(['success' => false, 'message' => 'Crédits insuffisants.']);
        exit();
    }

    if ($covoit['places_restantes'] < $passengers) {
        echo json_encode(['success' => false, 'message' => 'Pas assez de places disponibles.']);
        exit();
    }

    // Vérifier si déjà réservé (optionnel selon logique)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE utilisateur_id = ? AND covoiturage_id = ?");
    $stmt->execute([$user_id, $ride_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Vous avez déjà réservé ce covoiturage.']);
        exit();
    }

    // Démarrer une transaction
    $pdo->beginTransaction();

    // Insérer la réservation
    $stmt = $pdo->prepare("INSERT INTO reservations (utilisateur_id, covoiturage_id, passagers) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $ride_id, $passengers]);

    // Décrémenter les crédits
    $stmt = $pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
    $stmt->execute([$prix_total, $user_id]);

    // Décrémenter les places
    $stmt = $pdo->prepare("UPDATE covoiturages SET places_restantes = places_restantes - ? WHERE id = ?");
    $stmt->execute([$passengers, $ride_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Réservation effectuée avec succès.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur réservation : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue.']);
    exit();
}
