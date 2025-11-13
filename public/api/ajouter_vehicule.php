<?php
declare(strict_types=1);

require __DIR__ . '/../init.php'; // ← session_start + BASE_URL + $pdo = getPDO()
header('Content-Type: application/json; charset=utf-8');

// 1) Auth
if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté.']);
    exit;
}

// 2) Méthode
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit;
}

try {
    // 3) Utilisateur courant
    $stmtUser = $pdo->prepare("SELECT id, firstName, lastName FROM users WHERE email = ?");
    $stmtUser->execute([$_SESSION['user_email']]);
    $user = $stmtUser->fetch();
    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Utilisateur non trouvé.']);
        exit;
    }

    // 4) Récup & validations
    $plaque       = trim($_POST['plaque_immatriculation'] ?? '');
    $date_immat   = trim($_POST['date_1ere_immat'] ?? '');
    $modele       = trim($_POST['modele'] ?? '');
    $marque       = trim($_POST['marque'] ?? '');
    $energie      = trim($_POST['energie'] ?? '');
    $nb_places    = (int)($_POST['nb_places_disponibles'] ?? 0);
    $preferences  = trim($_POST['preferences'] ?? '');
    $fumeur       = isset($_POST['fumeur']) ? 1 : 0;
    $animal       = isset($_POST['animal']) ? 1 : 0;

    // plaque FR format récent : AB 123 CD (tolérance espaces)
    $plateOk = (bool)preg_match('/^[A-Z]{2}\s?\d{3}\s?[A-Z]{2}$/i', $plaque);
    if (!$plateOk) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => "Plaque invalide (format attendu : AB 123 CD)."]);
        exit;
    }

    // date au format YYYY-MM-DD
    $dt = DateTime::createFromFormat('Y-m-d', $date_immat);
    $dateOk = $dt && $dt->format('Y-m-d') === $date_immat;
    if (!$dateOk) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => "Date d'immatriculation invalide (YYYY-MM-DD)."]);
        exit;
    }

    if ($nb_places <= 0) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => "Le nombre de places doit être un entier positif."]);
        exit;
    }

    // Energie : borne les valeurs usuelles
    $energiesValides = ['essence','diesel','electrique','hybride','gpl','ethanol'];
    if ($energie !== '' && !in_array(strtolower($energie), $energiesValides, true)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => "Énergie invalide. Valeurs permises : ".implode(', ',$energiesValides)]);
        exit;
    }

    if ($modele === '' || $marque === '') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => "Marque et modèle sont requis."]);
        exit;
    }

    // 5) Insertion
    $sql = "
        INSERT INTO chauffeur_info
        (user_id, firstName, lastName, plaque_immatriculation, date_1ere_immat, modele, marque,
         nb_places_disponibles, preferences, smoker_preference, pet_preference, energie)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        (int)$user['id'],
        $user['firstName'],
        $user['lastName'],
        strtoupper($plaque),       // normalise la plaque
        $date_immat,
        $modele,
        $marque,
        $nb_places,
        $preferences,
        $fumeur,
        $animal,
        $energie,
    ]);

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Véhicule ajouté avec succès !', 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    // Contrainte d’unicité sur la plaque (si tu l’as mise)
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => "Cette plaque est déjà enregistrée."]);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "Erreur lors de l'ajout du véhicule."]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "Erreur serveur."]);
}
