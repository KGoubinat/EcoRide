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

$vehId  = filter_input(INPUT_POST, 'vehicule_id', FILTER_VALIDATE_INT);
if (!$vehId) { http_response_code(400); exit('ID véhicule invalide.'); }

$plaque = trim((string)($_POST['plaque_immatriculation'] ?? ''));
$dateIm = trim((string)($_POST['date_1ere_immat'] ?? ''));
$modele = trim((string)($_POST['modele'] ?? ''));
$marque = trim((string)($_POST['marque'] ?? ''));
$energie= trim((string)($_POST['energie'] ?? ''));
$places = filter_input(INPUT_POST, 'nb_places_disponibles', FILTER_VALIDATE_INT);
$smoker = isset($_POST['smoker_preference']) ? 1 : 0;
$pets   = isset($_POST['pet_preference']) ? 1 : 0;
$prefs  = trim((string)($_POST['preferences'] ?? ''));

if ($plaque === '' || $dateIm === '' || $modele === '' || $marque === '' || !$places) {
  http_response_code(400); exit('Champs manquants.');
}
if (!in_array($energie, ['diesel','essence','hybride','electrique'], true)) {
  http_response_code(400); exit('Énergie invalide.');
}
if ($places < 1 || $places > 9) { http_response_code(400); exit('Nombre de places invalide.'); }

$pdo = getPDO();

// vérifier ownership
$st = $pdo->prepare("
  SELECT c.id FROM chauffeur_info c
  JOIN users u ON u.id = c.user_id
  WHERE c.id = ? AND u.email = ?
");
$st->execute([$vehId, $_SESSION['user_email']]);
if (!$st->fetch()) { http_response_code(403); exit('Accès refusé.'); }

$up = $pdo->prepare("
  UPDATE chauffeur_info
  SET plaque_immatriculation = ?, date_1ere_immat = ?, modele = ?, marque = ?,
      nb_places_disponibles = ?, preferences = ?, smoker_preference = ?, pet_preference = ?, energie = ?
  WHERE id = ?
");
$up->execute([$plaque,$dateIm,$modele,$marque,$places,$prefs,$smoker,$pets,$energie,$vehId]);

header('Location: ' . BASE_URL . 'profil.php?message=' . urlencode('Véhicule mis à jour'));
exit;
