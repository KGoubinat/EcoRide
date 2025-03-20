<?php
// Charger le fichier autoload de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Charger le fichier .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Connexion à la base de données via l'URL définie dans le fichier .env
$databaseUrl = getenv('JAWSDB_URL');

if (!$databaseUrl) {
    die("Erreur : La variable d'environnement 'JAWSDB_URL' est absente ou mal configurée.");
}

$parsedUrl = parse_url($databaseUrl);

// Vérification de la présence des éléments essentiels dans l'URL
if (!isset($parsedUrl['host'], $parsedUrl['user'], $parsedUrl['pass'], $parsedUrl['path'])) {
    die("Erreur : L'URL de la base de données est mal configurée.");
}

$servername = $parsedUrl['host'];
$username = $parsedUrl['user'];
$password = $parsedUrl['pass'];
$dbname = ltrim($parsedUrl['path'], '/');

try {
    // Connexion à la base de données
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion réussie !"; // Pour vérifier si la connexion fonctionne
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
