<?php

declare(strict_types=1);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);


// 1) BASE_URL + .env via bootstrap
require_once __DIR__ . '/bootstrap.php';



// ==== URL helpers ====

if (!function_exists('site_origin')) {
    function site_origin(): string {
        // Si BASE_URL est défini, on récupère seulement le schéma + host
        $base = defined('BASE_URL') ? (string)BASE_URL : '';
        if ($base) {
            $p = parse_url($base);
            if (!empty($p['scheme']) && !empty($p['host'])) {
                return $p['scheme'] . '://' . $p['host'];
            }
        }
        // Fallback via $_SERVER
        $https = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ||
            ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443)
        );
        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}

if (!function_exists('current_url')) {
    function current_url(bool $keep_query = true): string {
        $origin = site_origin();
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        if (!$keep_query) {
            $p = parse_url($uri);
            $uri = $p['path'] ?? '/';
        }
        return $origin . $uri;
    }
}

if (!function_exists('absolute_from_base')) {
    function absolute_from_base(string $path): string {
        // Construit une URL absolue à partir de BASE_URL (qui inclut déjà le bon sous-chemin en local)
        $base = rtrim((string)BASE_URL, '/');
        return $base . '/' . ltrim($path, '/');
    }
}


// 2) Accès BDD
require_once __DIR__ . '/../backend/BDD/db.php';

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
