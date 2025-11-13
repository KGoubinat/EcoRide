<?php
// activer_utilisateur.php (exemple)
require __DIR__ . '/../../public/init.php'; // ← bootstrap + db.php + session_start + $pdo

// 1) Autorisation : réservé aux admins
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'home.php');
    exit;
}

// 2) Récupération et validation de l'ID
$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$userId) {
    http_response_code(400);
    exit('ID utilisateur invalide.');
}

//  3) anti-CSRF si l'action vient d’un formulaire ou d’un lien signé
 if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['token'] ?? '')) {
    http_response_code(403);
    exit('Token CSRF invalide.');
 }

// 4) Exécution de la mise à jour
try {
    

    $stmt = $pdo->prepare("UPDATE users SET etat = 'active' WHERE id = ?");
    $stmt->execute([$userId]);


    // 5) Redirection
    header('Location: ' . BASE_URL . 'manage_users.php');
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    exit('Erreur lors de l’activation de l’utilisateur.');
}
