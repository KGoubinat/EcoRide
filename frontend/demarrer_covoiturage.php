<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Vérifier si un utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Aucun utilisateur connecté.']);
    exit;
}

// Connexion à la base de données
$dsn = 'mysql:host=localhost;dbname=ecoride';
$username = 'root';
$password = 'nouveau_mot_de_passe'; // Remplacer par ton mot de passe
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Impossible de se connecter à la base de données : " . $e->getMessage());
}

// Déboguer : afficher le contenu brut de la requête
$rawData = file_get_contents('php://input');

$data = json_decode($rawData, true);
error_log("Données brutes : " . $rawData);
error_log("Données décodées : " . print_r($data, true));

// Vérifier si les paramètres sont présents
if (isset($data['id']) && isset($data['action']) && isset($data['type'])) {
    error_log("Paramètres reçus: ID = " . $data['id'] . ", Type = " . $data['type'] . ", Action = " . $data['action']);
    $id = $data['id'];
    $action = $data['action'];
    $type = $data['type']; // soit "covoiturage", soit "reservation"

    // Définir la requête et le message par défaut
    $query = "";
    $message = "";
    $deleteQuery = "";  // Ajouter la variable $deleteQuery pour la suppression

    // Vérifier l'action et le type
    if ($action == 'start') {
        if ($type == 'covoiturage') {
            // Mettre à jour le statut du covoiturage
            $query = "UPDATE covoiturages SET statut = 'en cours' WHERE id = :id";
            $message = "Covoiturage démarré avec succès !";

            // Mettre à jour les réservations associées
            $updateReservationsQuery = "UPDATE reservations SET statut = 'en cours' WHERE covoiturage_id = :id";
            $updateReservationsStmt = $pdo->prepare($updateReservationsQuery);
            $updateReservationsStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $updateReservationsStmt->execute();

        } else if ($type == 'reservation') {
            // Vérifier que la réservation existe avant de mettre à jour
            $query = "UPDATE reservations SET statut = 'en cours' WHERE id = :id";
            $message = "Réservation démarrée avec succès !";
        }
    } else if ($action == 'end') {
        if ($type == 'covoiturage') {
            // Mettre à jour le statut du covoiturage
            $query = "UPDATE covoiturages SET statut = 'terminé' WHERE id = :id";
            $message = "Covoiturage terminé avec succès !";

            // Mettre à jour les réservations associées
            $updateReservationsQuery = "UPDATE reservations SET statut = 'terminé' WHERE covoiturage_id = :id";
            $updateReservationsStmt = $pdo->prepare($updateReservationsQuery);
            $updateReservationsStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $updateReservationsStmt->execute();

        

        } else if ($type == 'reservation') {
            $query = "UPDATE reservations SET statut = 'terminé' WHERE id = :id";
            $message = "Réservation terminée avec succès !";

            
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non valide']);
        exit;
    }

    // Vérifier si $query est vide avant de continuer
    if (empty($query)) {
        echo json_encode(['success' => false, 'message' => 'La requête SQL est vide.']);
        exit;
    }

    // Préparer et exécuter la requête principale
    try {
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Vérifier si la mise à jour a affecté des lignes
        if ($stmt->rowCount() > 0) {
            // Si c'est un covoiturage, on met à jour également les réservations associées
            if ($type == 'covoiturage') {
                error_log("Mise à jour réussie du covoiturage pour l'ID : $id");
            } else {
                error_log("Mise à jour réussie de la réservation pour l'ID : $id");
            }
        } else {
            error_log("Aucune ligne affectée pour l'ID : $id");
        }

        // Supprimer les éléments associés après avoir mis à jour le statut
        if (!empty($deleteQuery)) {
            $deleteStmt = $pdo->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $id, PDO::PARAM_INT);
            $deleteStmt->execute();
            error_log("Suppression effectuée pour l'ID : $id");
        }

        // Retourner une réponse JSON
        echo json_encode(['success' => true, 'message' => $message]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
        exit;
    }

} else {

    exit;

}



?>
