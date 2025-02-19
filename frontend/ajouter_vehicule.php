<?php
session_start();

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_email']);
if (!$isLoggedIn) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non connecté.']);
    exit;
}

// Connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=ecoride';  
$username = 'root';
$password = 'nouveau_mot_de_passe';  
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Impossible de se connecter à la base de données : ' . $e->getMessage()]);
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_email = $_SESSION['user_email'];
$stmtUser = $pdo->prepare("SELECT id, firstName, lastName FROM users WHERE email = ?");
$stmtUser->execute([$user_email]);
$user = $stmtUser->fetch();

// Vérifier si l'utilisateur existe
if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non trouvé.']);
    exit;
}

// Récupérer les informations du formulaire
$plaque = $_POST['plaque_immatriculation'];
$date_immat = $_POST['date_1ere_immat'];
$modele = $_POST['modele'];
$marque = $_POST['marque'];
$energie = $_POST['energie'];
$nb_places = $_POST['nb_places_disponibles'];
$preferences = $_POST['preferences'] ?? '';
$fumeur = isset($_POST['fumeur']) ? 1 : 0;
$animal = isset($_POST['animal']) ? 1 : 0;

// Valider la plaque d'immatriculation avec l'expression régulière
$pattern = "/^[A-Z]{2}\s?\d{3}\s?[A-Z]{2}$/i";
if (!preg_match($pattern, $plaque)) {
    echo json_encode(['status' => 'error', 'message' => 'La plaque d\'immatriculation n\'est pas valide. Le format attendu est : AB 123 CD.']);
    exit;
}

// Vérifier la validité de la date d'immatriculation
if (!strtotime($date_immat)) {
    echo json_encode(['status' => 'error', 'message' => 'La date d\'immatriculation n\'est pas valide.']);
    exit;
}

// Vérifier la validité du nombre de places
if (!filter_var($nb_places, FILTER_VALIDATE_INT) || $nb_places <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Le nombre de places doit être un entier positif.']);
    exit;
}

// Insertion des données dans la table chauffeur_info
$stmtInsert = $pdo->prepare("
    INSERT INTO chauffeur_info 
    (user_id, firstName, lastName, plaque_immatriculation, date_1ere_immat, modele, marque, nb_places_disponibles, preferences, smoker_preference, pet_preference, energie) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

try {
    $stmtInsert->execute([
        $user['id'],
        $user['firstName'],  
        $user['lastName'],    
        $plaque,
        $date_immat,
        $modele,
        $marque,
        $nb_places,
        $preferences,
        $fumeur,
        $animal,
        $energie,
    ]);
    echo json_encode(['status' => 'success', 'message' => 'Le véhicule a été ajouté avec succès !']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'ajout du véhicule : ' . $e->getMessage()]);
}
?>
