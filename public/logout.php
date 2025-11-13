<?php
declare(strict_types=1);

require __DIR__ . '/init.php';

// -------------------------
// 1. Déconnexion sécurisée
// -------------------------
if (session_status() === PHP_SESSION_ACTIVE) {

    $_SESSION = [];

    // Supprimer cookie session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );

        // fallback
        setcookie(session_name(), '', time() - 42000, '/');
    }

    session_destroy();
}

// -------------------------
// 2. Redirection sécurisée
// -------------------------
$allowedPages = ['login.php', 'home.php', 'index.php'];

$next = $_GET['next'] ?? 'login.php';
$next = basename($next); // élimine chemins type ../ ou URLs

if (!in_array($next, $allowedPages, true)) {
    $next = 'login.php';
}

$location = rtrim(BASE_URL, '/') . '/' . $next;
header("Location: $location");
exit;
?>
