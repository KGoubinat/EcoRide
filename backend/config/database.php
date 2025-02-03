<?php
// Paramètres de connexion à la base de données
$host = 'localhost';      // L'hôte de la base de données (généralement localhost)
$dbname = 'ecoride';      // Le nom de la base de données
$username = 'root';       // Ton nom d'utilisateur pour MySQL
$password = 'nouveau_mot_de_passe';           // Le mot de passe de MySQL (généralement vide sur localhost)

try {
    // Création de la connexion PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    
    // Définir le mode d'erreur de PDO à Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configurer le jeu de caractères
    $pdo->exec("SET NAMES 'utf8'");
} catch (PDOException $e) {
    // Si la connexion échoue, afficher l'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
