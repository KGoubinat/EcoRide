<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$isLoggedIn = isset($_SESSION['user_email']);
$user_email = $_SESSION['user_email'] ?? null;

// Récupérer l'URL de la base de données depuis la variable d'environnement JAWSDB_URL
$databaseUrl = getenv('JAWSDB_URL');

// Utiliser une expression régulière pour extraire les éléments nécessaires de l'URL
$parsedUrl = parse_url($databaseUrl);

// Définir les variables pour la connexion à la base de données
$servername = $parsedUrl['host'];  // Hôte MySQL
$username = $parsedUrl['user'];  // Nom d'utilisateur MySQL
$password = $parsedUrl['pass'];  // Mot de passe MySQL
$dbname = ltrim($parsedUrl['path'], '/');  // Nom de la base de données (en enlevant le premier "/")

// Connexion à la base de données avec PDO
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

header('Content-Type: application/json');



if (!$isLoggedIn) {
    echo json_encode(['status' => 'error', 'message' => 'Vous devez être connecté pour saisir un voyage.']);
    exit;
}

$stmtUser = $conn->prepare("SELECT id, credits, status, lastName, firstName FROM users WHERE email = ?");
$stmtUser->execute([$user_email]);
$user = $stmtUser->fetch();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'Utilisateur non trouvé.']);
    exit;
}

if ($user['status'] !== 'chauffeur' && $user['status'] !== 'passager_chauffeur') {
    echo json_encode(['status' => 'error', 'message' => 'Vous n\'êtes pas autorisé à ajouter un voyage.']);
    exit;
}

$stmtVéhicules = $conn->prepare("SELECT id, modele, marque, energie, nb_places_disponibles FROM chauffeur_info WHERE user_id = ?");
$stmtVéhicules->execute([$user['id']]);
$vehicules = $stmtVéhicules->fetchAll() ?: [];

$stmtVilles = $conn->query("SELECT nom FROM villes");
$villes = $stmtVilles->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifie que tous les champs nécessaires sont présents
    if (!isset($_POST['depart'], $_POST['destination'], $_POST['prix'], $_POST['vehicule_id'], $_POST['heure_depart'], $_POST['duree'], $_POST['date'])) {
        echo json_encode(['status' => 'error', 'message' => 'Des champs sont manquants dans le formulaire.']);
        exit;
    }
    // Récupération des données
    $depart = $_POST['depart']; 
    $destination = $_POST['destination'];
    $prix = (float) $_POST['prix'];
    $vehicule_id = (int) $_POST['vehicule_id'];

    // Conversion de l'heure de départ et de la durée
    $heure_depart = date('H:i:s', strtotime($_POST['heure_depart']));
    $duree = $_POST['duree'];  // Durée au format HH:MM:SS

    // Récupérer directement la date du formulaire (format YYYY-MM-DD)
    $date = $_POST['date'];  // La date doit déjà être au format YYYY-MM-DD

    if ($prix < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Le prix ne peut pas être négatif.']);
        exit;
    }

    if ($user['credits'] < 2) {
        echo json_encode(['status' => 'error', 'message' => 'Vous n\'avez pas assez de crédits pour saisir ce voyage.']);
        exit;
    }

    $stmtVehicule = $conn->prepare("SELECT modele, marque, energie, nb_places_disponibles FROM chauffeur_info WHERE id = ? AND user_id = ?");
    $stmtVehicule->execute([$vehicule_id, $user['id']]);
    $vehicule = $stmtVehicule->fetch();

    if (!$vehicule) {
        echo json_encode(['status' => 'error', 'message' => 'Véhicule non trouvé ou non autorisé.']);
        exit;
    }

    // Vérification si nb_places est bien défini
    $nb_places_disponibles = $vehicule['nb_places_disponibles'] ?? 0;
    if ($nb_places_disponibles <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Le véhicule n\'a pas de places disponibles.']);
        exit;
    }

    $conducteur = $user['firstName'] . ' ' . $user['lastName'];
    $note = 0;
    $photo = 'default.jpg';
    $places_restantes = isset($_POST['places_restantes']) ? (int) $_POST['places_restantes'] : 0;
    $passagers = 0;

    if ($places_restantes <= 0 || $places_restantes > $nb_places_disponibles) {
        echo json_encode(['status' => 'error', 'message' => 'Nombre de places sélectionnées invalide.']);
        exit;
    }
    
    // Déterminer la valeur de "ecologique" en fonction du type de carburant
    $ecologique = 0; // Valeur par défaut (non écologique)

    if (in_array($vehicule['energie'], ['electrique', 'hybride'])) {
        $ecologique = 1;  // Si le véhicule utilise de l'électrique ou hybride, il est écologique
    }

    // Calcul de l'heure d'arrivée
    $heure_depart_timestamp = strtotime($heure_depart);
    $duree_timestamp = strtotime($duree) - strtotime('00:00:00');  // Convertir la durée en secondes
    $heure_arrivee_timestamp = $heure_depart_timestamp + $duree_timestamp;

    $heure_arrivee = date('H:i:s', $heure_arrivee_timestamp);  // Convertir l'heure d'arrivée au format H:i:s

    // Insertion dans la table covoiturages
    $stmtCovoiturage = $conn->prepare("INSERT INTO covoiturages (depart, destination, prix, vehicule_id, user_id, conducteur, places_restantes, note, heure_depart, duree, passagers, ecologique, photo, nb_places_disponibles, modele_voiture, marque_voiture, energie_voiture, heure_arrivee, `date`) 
                                 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");


    if ($stmtCovoiturage->execute([
        $depart,   // Le départ (ville ou autre donnée)
        $destination, 
        $prix - 2,  
        $vehicule_id, 
        $user['id'], 
        $conducteur, 
        $places_restantes, 
        $note, 
        $heure_depart, 
        $duree, 
        $passagers, 
        $ecologique,  // La valeur ajustée de `ecologique`
        $photo, 
        $nb_places_disponibles, 
        $vehicule['modele'], 
        $vehicule['marque'], 
        $vehicule['energie'], // Energie du véhicule
        $heure_arrivee,  // L'heure d'arrivée calculée
        $date  // La date du voyage (format YYYY-MM-DD)
    ])) {
        $stmtCredits = $conn->prepare("UPDATE users SET credits = credits - 2 WHERE id = ?");
        $stmtCredits->execute([$user['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Voyage ajouté avec succès.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'ajout du voyage.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Méthode non supportée.']);
}

exit();
?>
