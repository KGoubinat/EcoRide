<?php

declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1) BASE_URL + .env via bootstrap
require_once __DIR__ . '/bootstrap.php';

// 2) Session (toujours AVANT l’envoi des headers)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3) Headers de sécurité HTTP
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("X-Frame-Options: DENY");

// 3bis) Contenu CSP (UNE SEULE LIGNE !)
header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data:; style-src 'self' 'unsafe-inline'; script-src 'self';");




// 4) URL helpers
if (!function_exists('site_origin')) {
    function site_origin(): string {
        $base = defined('BASE_URL') ? (string)BASE_URL : '';
        if ($base) {
            $p = parse_url($base);
            if (!empty($p['scheme']) && !empty($p['host'])) {
                return $p['scheme'] . '://' . $p['host'];
            }
        }
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

// 5) Accès BDD
require_once __DIR__ . '/../backend/BDD/db.php';

// 6) Fournir $pdo global
try {
    $pdo = getPDO();
} catch (Throwable $e) {
    http_response_code(500);
}

// 7) Helpers
if (!function_exists('isLoggedIn')) {
    function isLoggedIn(): bool {
        return !empty($_SESSION['user_email']) || !empty($_SESSION['user_id']);
    }
}

// 8) CSRF : générer une fois par session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('current_url')) {
    function current_url(bool $absolute = true): string {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $url = $scheme . '://' . $host . $uri;
        return $absolute ? $url : strtok($url, '?');
    }
}

if (!function_exists('absolute_from_base')) {
    function absolute_from_base(string $path): string {
        $base = defined('BASE_URL') ? BASE_URL : '/';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}
