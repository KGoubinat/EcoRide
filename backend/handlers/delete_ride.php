<?php
require __DIR__ . '/../../public/init.php'; 

// On active les erreurs temporairement pour debug (tu peux retirer après)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . BASE_URL . 'profile.php'); 
  exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$rideId = (int)($_POST['covoiturage_id'] ?? 0);

// CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', (string)($_POST['csrf_token'] ?? ''))) {
  header('Location: ' . BASE_URL . 'profile.php?err=csrf'); 
  exit;
}

if (!$userId || !$rideId) {
  header('Location: ' . BASE_URL . 'profile.php?err=param'); 
  exit;
}

// Vérifier ownership + statut
$st = $pdo->prepare("SELECT statut FROM covoiturages WHERE id = ? AND user_id = ?");
$st->execute([$rideId, $userId]);

$raw = $st->fetchColumn();

if ($raw === false) {
  // Le trajet n'appartient pas à l'utilisateur ou n'existe plus
  header('Location: ' . BASE_URL . 'profile.php?err=notfound'); 
  exit;
}

$statut = strtolower(trim($raw));

// Seuls terminé ou annulé peuvent être supprimés
if (!in_array($statut, ['terminé','annulé'], true)) {
  header('Location: ' . BASE_URL . 'profile.php?err=statut'); 
  exit;
}

// Supprimer les incidents liés au trajet
$pdo->prepare("DELETE FROM troublesome_rides WHERE ride_id = ?")->execute([$rideId]);

// Puis supprimer le covoiturage
$del = $pdo->prepare("DELETE FROM covoiturages WHERE id = ? AND user_id = ? LIMIT 1");
$del->execute([$rideId, $userId]);


header('Location: ' . BASE_URL . 'profile.php?tab=offered&ok=deleted');
exit;
