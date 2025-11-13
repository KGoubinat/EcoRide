<?php
// logout.php
require __DIR__ . '/../../public/init.php'; // gère BASE_URL et session_start

// Vider la session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Redirection vers la page de connexion
header('Location: ' . BASE_URL . 'connexion.php');
exit;
