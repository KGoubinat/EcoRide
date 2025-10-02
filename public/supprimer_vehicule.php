<?php
declare(strict_types=1);
require __DIR__ . '/init.php';

if (empty($_SESSION['user_email']) || empty($_SESSION['csrf_token'])) {
  http_response_code(401); exit('Non connecté.');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405); exit('Méthode non autorisée.');
}
if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
  http_response_code(403); exit('CSRF invalide.');
}

$vehId = filter_input(INPUT_POST, 'vehicule_id', FILTER_VALIDATE_INT);
if (!$vehId) { http_response_code(400); exit('ID véhicule invalide.'); }

$pdo = getPDO();

// Ownership
$st = $pdo->prepare("
  SELECT c.id FROM chauffeur_info c
  JOIN users u ON u.id = c.user_id
  WHERE c.id = ? AND u.email = ?
");
$st->execute([$vehId, $_SESSION['user_email']]);
if (!$st->fetch()) { http_response_code(403); exit('Accès refusé.'); }

// Empêcher suppression si véhicule utilisé par des covoiturages actifs
$chk = $pdo->prepare("SELECT COUNT(*) FROM covoiturages WHERE vehicule_id = ? AND statut IN ('en attente','en cours')");
$chk->execute([$vehId]);
if ($chk->fetchColumn() > 0) { exit('Véhicule utilisé par un covoiturage actif.'); }

$del = $pdo->prepare("DELETE FROM chauffeur_info WHERE id = ?");
$del->execute([$vehId]);

header('Location: ' . BASE_URL . 'profil.php?message=' . urlencode('Véhicule supprimé'));
exit;
