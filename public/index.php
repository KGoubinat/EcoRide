<?php 

require __DIR__ . '/init.php';

if (empty($_SERVER['HTTPS']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https') {
    $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $httpsUrl", true, 301);
    exit();
}
include_once("home.php"); 

