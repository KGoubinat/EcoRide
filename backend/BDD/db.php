<?php
declare(strict_types=1);

if (!function_exists('getPDO')) {
    function getPDO(): PDO {
        $env = getenv('APP_ENV') ?: 'prod';

        // LOCAL
        if (strcasecmp($env, 'local') === 0) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = (int)(getenv('DB_PORT') ?: 3306);
            $db   = getenv('DB_NAME') ?: 'ecoride';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        // PROD (JawsDB)
        $databaseUrl = getenv('JAWSDB_URL') ?: getenv('JAWSDB_MARIA_URL');
        if (!$databaseUrl) {
            throw new RuntimeException('JAWSDB_URL manquante en prod.');
        }
        $parts = parse_url($databaseUrl);
        if (!isset($parts['host'], $parts['user'], $parts['pass'], $parts['path'])) {
            throw new RuntimeException('URL JawsDB invalide.');
        }

        $host = $parts['host'];
        $port = (int)($parts['port'] ?? 3306);
        $db   = ltrim($parts['path'], '/');
        $user = $parts['user'];
        $pass = $parts['pass'];

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
}
