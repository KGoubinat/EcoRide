<?php 
if (empty($_SERVER['HTTPS']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https') {
    $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $httpsUrl", true, 301);
    exit();
}
include_once("frontend/accueil.php"); 

\Cloudinary::config(array(
    "cloud_name" => "dj9iiquhw",
    "api_key" => "191869388494711",
    "api_secret" => "pjhNfoa_aSfLssECHSy_kpUliHQ"
));