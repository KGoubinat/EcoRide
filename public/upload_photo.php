<?php
declare(strict_types=1);
require __DIR__ . '/init.php';

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

$isLocal = (getenv('APP_ENV') === 'local');

// --- Sécurité basique ---
if (empty($_SESSION['user_email'])) { http_response_code(401); exit('Non connecté'); }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Méthode non autorisée'); }
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
  http_response_code(400); exit('CSRF invalide');
}

// --- Cloudinary v3 ---
$cloudinaryUrl = getenv('CLOUDINARY_URL');
if ($cloudinaryUrl) {
    Configuration::instance($cloudinaryUrl);
} else {
    $cloud = getenv('CLOUDINARY_CLOUD_NAME');
    $key   = getenv('CLOUDINARY_API_KEY');
    $sec   = getenv('CLOUDINARY_API_SECRET');
    if (!$cloud || !$key || !$sec) {
        http_response_code(500);
        exit("Cloudinary non configuré.");
    }
    Configuration::instance([
        'cloud' => ['cloud_name'=>$cloud, 'api_key'=>$key, 'api_secret'=>$sec],
        'url'   => ['secure' => true],
    ]);
}

// --- Fichier côté PHP (poids, type, dimensions) ---
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400); exit("Aucune photo ou erreur d'upload.");
}
$file = $_FILES['photo'];

// Limite poids (3 Mo)
$maxBytes = 3 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
  http_response_code(400);
  exit("Fichier trop volumineux (max 3 Mo).");
}

// Limite formats
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$allowed = ['image/jpeg','image/png','image/gif','image/webp'];
if (!in_array($mime, $allowed, true)) {
  http_response_code(400);
  exit('Format non autorisé (JPEG/PNG/GIF/WEBP).');
}

// Limite dimensions (sécurité)
[$w, $h] = getimagesize($file['tmp_name']) ?: [0, 0];
if ($w === 0 || $h === 0) { http_response_code(400); exit('Image invalide.'); }
$maxW = 5000; $maxH = 5000;
if ($w > $maxW || $h > $maxH) {
  http_response_code(400);
  exit("Image trop grande (max {$maxW}×{$maxH}px).");
}

// --- Upload Cloudinary + génération d'une variante optimisée ---
try {
    $result = (new UploadApi())->upload(
        $file['tmp_name'],
        [
            'folder'          => 'profile_pictures',
            'unique_filename' => true,
            'overwrite'       => false,
            'resource_type'   => 'image',
            // On génère une version max 512px, qualité auto, format auto
            'eager' => [[
                'crop'         => 'limit',
                'width'        => 512,
                'height'       => 512,
                'quality'      => 'auto:good',
                'fetch_format' => 'auto',
            ]],
            'eager_async' => false,
        ]
    );
} catch (Throwable $e) {
    if ($isLocal) {
        http_response_code(500);
        echo "Erreur upload Cloudinary : " . htmlspecialchars($e->getMessage());
        exit;
    }
    http_response_code(500);
    exit('Erreur upload Cloudinary.');
}

// On privilégie l’URL de la variante "eager", sinon l’originale
$secureUrl = $result['eager'][0]['secure_url'] ?? ($result['secure_url'] ?? null);
if (!$secureUrl) { http_response_code(500); exit('Réponse Cloudinary invalide.'); }

// --- BDD : on enregistre la photo optimisée ---
$pdo = getPDO();
$upd = $pdo->prepare('UPDATE users SET photo = :url WHERE email = :email');
$upd->execute([':url' => $secureUrl, ':email' => $_SESSION['user_email']]);

// --- Retour profil ---
header('Location: ' . BASE_URL . 'profil.php');
exit;
