<?php
// get_ride_status.php
declare(strict_types=1);

require __DIR__ . '/../../public/init.php'; // ← session_start + $pdo = getPDO()
header('Content-Type: application/json; charset=utf-8');

// 1) Auth
if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Aucun utilisateur connecté.']);
    exit;
}

// 2) Lecture du body JSON
$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$covoiturageId = filter_var($payload['covoiturageId'] ?? null, FILTER_VALIDATE_INT);

if (!$covoiturageId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de covoiturage manquant ou invalide.']);
    exit;
}

try {
    // 3) Récupérer le statut du covoiturage
    $stmt = $pdo->prepare("SELECT statut FROM covoiturages WHERE id = ?");
    $stmt->execute([$covoiturageId]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Covoiturage introuvable.']);
        exit;
    }

    // 4) Retourne le statut
    echo json_encode([
        'success' => true,
        'id'      => $covoiturageId,
        'statut'  => $row['statut'], // ex: "terminé", "annulé", "en_cours", etc.
    ]);
} catch (Throwable $e) {
    // Log côté serveur si besoin
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
