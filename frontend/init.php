<?php
declare(strict_types=1);

// Debug local (optionnel)
if (!headers_sent()) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}
error_reporting(E_ALL);

// 1) BASE_URL + .env via bootstrap
require_once __DIR__ . '/bootstrap.php';

// 2) Accès BDD
require_once __DIR__ . '/../backend/bdd/db.php';

// 3) Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4) Fournir $pdo global
try {
    $pdo = getPDO();
} catch (Throwable $e) {
    http_response_code(500);
}

// 5) Helpers
if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        return !empty($_SESSION['user_email']) || !empty($_SESSION['user_id']);
    }
}

// 6) CSRF: générer une fois par session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
