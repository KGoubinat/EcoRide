<?php
require __DIR__ . '/../../public/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php'); 
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$resId  = (int)($_POST['reservation_id'] ?? 0);

// CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_POST['csrf_token'] ?? ''))) {
    header('Location: profile.php?err=csrf'); 
    exit;
}

if (!$userId || !$resId) {
    header('Location: profile.php?err=param'); 
    exit;
}

// Vérifier ownership + statut
$st = $pdo->prepare("SELECT statut FROM reservations WHERE id = ? AND user_id = ?");
$st->execute([$resId, $userId]);
$statut = strtolower(trim((string)$st->fetchColumn()));

if (!in_array($statut, ['terminé','annulé'], true)) {
    // On refuse la suppression si statut non terminé/annulé
    header('Location: profile.php?err=statut'); 
    exit;
}

// Suppression (ou soft delete si tu préfères)
$del = $pdo->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ? LIMIT 1");
$del->execute([$resId, $userId]);

header('Location: profile.php?tab=reservations&ok=deleted');
exit;
