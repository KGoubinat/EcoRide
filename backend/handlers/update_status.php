<?php

require __DIR__ . '/../../public/init.php';
header('Content-Type: application/json; charset=UTF-8');

// Doit être connecté
if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté.']);
    exit;
}

// Uniquement POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit;
}

// CSRF cohérent avec ton formulaire (name="csrf_token")
$posted = (string)($_POST['csrf_token'] ?? '');
$token  = (string)($_SESSION['csrf_token'] ?? '');
if ($posted === '' || $token === '' || !hash_equals($token, $posted)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF invalide.']);
    exit;
}

// Statut
$status = trim((string)($_POST['status'] ?? ''));
$allowed = ['passager','chauffeur','passager_chauffeur'];
if (!in_array($status, $allowed, true)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Statut invalide.']);
    exit;
}

try {
    $pdo = getPDO();

    // Maj par email (tu as déjà l’email en session)
    $st = $pdo->prepare('UPDATE users SET status = ? WHERE email = ?');
    $st->execute([$status, $_SESSION['user_email']]);

    echo json_encode(['status' => 'success', 'newStatus' => $status]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur.']);
}
