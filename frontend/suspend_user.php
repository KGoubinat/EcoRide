<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/../backend/bdd/db.php';   
require __DIR__ . '/bootstrap.php';          

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'accueil.php', true, 302);
    exit;
}

// ici on accepte encore GET mais on valide strictement
$userIdParam = $_POST['id'] ?? $_GET['id'] ?? null;
if ($userIdParam === null || !ctype_digit((string)$userIdParam)) {
    // id invalide
    header('Location: ' . BASE_URL . 'manage_users.php?error=id', true, 302);
    exit;
}
$userId = (int)$userIdParam;

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare('UPDATE users SET etat = :etat WHERE id = :id');
    $stmt->execute([
        ':etat' => 'suspended',
        ':id'   => $userId,
    ]);

    // petit message flash en session si tu veux l'afficher dans manage_users.php
    $_SESSION['flash'] = ['type' => 'success', 'msg' => "L'utilisateur #$userId a été suspendu."];

    header('Location: ' . BASE_URL . 'manage_users.php', true, 302);
    exit;

} catch (Throwable $e) {
    // log interne si besoin
    // error_log($e->getMessage());
    header('Location: ' . BASE_URL . 'manage_users.php?error=db', true, 302);
    exit;
}
