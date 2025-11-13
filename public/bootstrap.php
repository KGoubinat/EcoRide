<?php
// --- Charger Composer + .env ---
$root = dirname(__DIR__); // dossier racine du projet (là où se trouve .env)
if (file_exists($root . '/vendor/autoload.php')) {
    require $root . '/vendor/autoload.php';
    Dotenv\Dotenv::createImmutable($root)->safeLoad(); // charge .env -> getenv()/$_ENV dispo
} else {
    // (fallback sans composer) : charger .env à la main
    $envFile = $root . '/.env';
    if (is_file($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if ($line[0] === '#' || !str_contains($line, '=')) continue;
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'"); // enlève guillemets éventuels
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

/* === Rendez les variables .env dispo via getenv() et un helper env() === */
if (!empty($_ENV)) {
    foreach ($_ENV as $k => $v) {
        if (getenv($k) === false) {
            putenv("$k=$v");
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $v = $_ENV[$key] ?? getenv($key);
        return ($v === false || $v === null) ? $default : $v;
    }
}


// --- BASE_URL & HTTPS ---
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Détection du protocole (Heroku est derrière un proxy)
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $protoList = explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO']);
    $protocol = (trim($protoList[0]) === 'https') ? 'https' : 'http';
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
}

// Chemin de base différent selon environnement
if ($host === 'localhost') {
    $basePath = '/MesGrossesCouilles/public/';
} else {
    $basePath = '/'; // Heroku/Prod : racine
}

$envBase = env('BASE_URL', '');
if ($envBase) {
    $envBase = rtrim($envBase, '/') . '/';
    define('BASE_URL', $envBase);
} else {
    // ... garde ton code existant (détection protocole + $basePath)
    define('BASE_URL', $protocol . '://' . $host . rtrim($basePath, '/') . '/');
}



// Forcer HTTPS en prod uniquement
if ($host !== 'localhost') {
    $xfp = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if ($xfp && strpos($xfp, 'https') !== 0) {
        header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
}
