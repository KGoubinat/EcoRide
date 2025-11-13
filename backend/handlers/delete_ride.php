<?php
require __DIR__ . '/../../public/init.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: public/profile.php'); exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$rideId = (int)($_POST['covoiturage_id'] ?? 0);

// CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_POST['csrf_token'] ?? ''))) {
  header('Location: public/profile.php?err=csrf'); exit;
}

if (!$userId || !$rideId) {
  header('Location: public/profile.php?err=param'); exit;
}

// Vérifier ownership + statut
$st = $pdo->prepare("SELECT statut FROM covoiturages WHERE id = ? AND user_id = ?");
$st->execute([$rideId, $userId]);
$statut = strtolower(trim((string)$st->fetchColumn()));

if (!in_array($statut, ['terminé','annulé'], true)) {
  header('Location: public/profile.php?err=statut'); exit;
}

// Supprimer (ou soft-delete si tu préfères)
$del = $pdo->prepare("DELETE FROM covoiturages WHERE id = ? AND user_id = ? LIMIT 1");
$del->execute([$rideId, $userId]);

// (débug utile si besoin) : if ($del->rowCount() === 0) { header('Location: profile.php?err=notfound'); exit; }

header('Location: ' . rtrim(BASE_URL, '/') . '/profile.php?tab=offered&ok=deleted');
exit;
