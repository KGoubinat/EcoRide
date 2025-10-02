<?php
// frontend/cdn.php
declare(strict_types=1);

// 1) sécurisation basique du paramètre
$path = $_GET['path'] ?? '';
if ($path === '' || str_contains($path, '..')) {
  http_response_code(400); exit('Bad path');
}
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif','webp','avif','svg'];
if (!in_array($ext, $allowed, true)) {
  http_response_code(415); exit('Bad ext');
}

// 2) cible Cloudinary originale
$origin = 'https://res.cloudinary.com/' . ltrim($path, '/');

// 3) requête upstream via cURL
$ch = curl_init($origin);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_TIMEOUT => 15,
  CURLOPT_HEADER => true,
  CURLOPT_USERAGENT => 'EcoRide-CDN-Proxy',
  // on NE forward PAS les cookies du client
  CURLOPT_HTTPHEADER => array_filter([
    isset($_SERVER['HTTP_IF_NONE_MATCH']) ? 'If-None-Match: '.$_SERVER['HTTP_IF_NONE_MATCH'] : null,
    isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? 'If-Modified-Since: '.$_SERVER['HTTP_IF_MODIFIED_SINCE'] : null,
  ]),
]);

$resp = curl_exec($ch);
if ($resp === false) { http_response_code(502); exit('Upstream error'); }
$code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$rawH  = substr($resp, 0, $hsize);
$body  = substr($resp, $hsize);
curl_close($ch);

// 4) 304/erreurs
if ($code === 304) { http_response_code(304); exit; }
if ($code >= 400)  { http_response_code($code); exit('Upstream '.$code); }

// 5) récupère Content-Type & ETag, mais surtout NE PAS renvoyer Set-Cookie
$ct   = 'application/octet-stream';
$etag = null;
foreach (explode("\r\n", $rawH) as $line) {
  if (stripos($line, 'content-type:') === 0) $ct   = trim(substr($line, 13));
  if (stripos($line, 'etag:')         === 0) $etag = trim(substr($line, 5));
}

// 6) entêtes vers le client (cookieless + cache long)
header('Content-Type: '.$ct);
header('Cache-Control: public, max-age=31536000, immutable');
if ($etag) header('ETag: '.$etag);

// 7) body
echo $body;
