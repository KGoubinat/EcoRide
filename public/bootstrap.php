<?php
putenv("APP_ENV=local");
$_ENV["APP_ENV"] = "local";

// --- Charger Composer + .env ---
$root = dirname(__DIR__);

if (file_exists($root . '/vendor/autoload.php')) {

    require $root . '/vendor/autoload.php';
    Dotenv\Dotenv::createImmutable($root)->safeLoad();

    // variables pour getenv()
    if (isset($_ENV['APP_ENV'])) putenv("APP_ENV=" . $_ENV['APP_ENV']);
    if (isset($_ENV['DB_HOST'])) putenv("DB_HOST=" . $_ENV['DB_HOST']);
    if (isset($_ENV['DB_PORT'])) putenv("DB_PORT=" . $_ENV['DB_PORT']);
    if (isset($_ENV['DB_NAME'])) putenv("DB_NAME=" . $_ENV['DB_NAME']);
    if (isset($_ENV['DB_USER'])) putenv("DB_USER=" . $_ENV['DB_USER']);
    if (isset($_ENV['DB_PASS'])) putenv("DB_PASS=" . $_ENV['DB_PASS']);

} else {

    // fallback sans composer
    $envFile = $root . '/.env';
    if (is_file($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if ($line[0] === '#' || !str_contains($line, '=')) continue;
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }

}

// Rendre les variables .env visibles via getenv()
if (!empty($_ENV)) {
    foreach ($_ENV as $k => $v) {
        if (getenv($k) === false) {
            putenv("$k=$v");
        }
    }
}

// helper env()
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $v = $_ENV[$key] ?? getenv($key);
        return ($v === false || $v === null) ? $default : $v;
    }
}

// --- BASE_URL & HTTPS ---
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Protocole
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $protoList = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO']);
    $protocol = (trim($protoList[0]) === 'https') ? 'https' : 'http';
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
}

// Chemins
if ($host === 'localhost') {
    $basePath = '/ecoride/public/';
} else {
    $basePath = '/';
}

$envBase = env('BASE_URL', '');
if ($envBase) {
    define('BASE_URL', rtrim($envBase, '/') . '/');
} else {
    define('BASE_URL', $protocol . '://' . $host . rtrim($basePath, '/') . '/');
}

// Forcer le HTTPS en PROD uniquement
if ($host !== 'localhost') {
    $xfp = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if ($xfp && strpos($xfp, 'https') !== 0) {
        header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
}
