<?php
// logout.php
declare(strict_types=1);

// IMPORTANT : inclure init.php pour avoir BASE_URL et la session
require __DIR__ . '/init.php'; // doit faire session_start() + définir BASE_URL

// Vider la session
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];

    // Supprimer le cookie de session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            $params['secure'] ?? false,
            $params['httponly'] ?? true
        );
        // Ce doublon couvre le cas où le path par défaut serait différent
        setcookie(session_name(), '', time() - 42000, '/');
    }

    session_destroy();
}

// Redirection (par défaut vers la page de connexion)
$next = $_GET['next'] ?? 'connexion.php'; // adapte si besoin (connexion.php, accueil.php, etc.)
$next = ltrim($next, '/');                 // éviter un chemin absolu fourni en entrée
$location = rtrim(BASE_URL, '/') . '/' . $next;

header('Location: ' . $location);
exit;
