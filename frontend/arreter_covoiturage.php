<?php
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
    echo json_encode(['success' => false, 'message' => 'Impossible de se connecter à la base de données : ' . $e->getMessage()]);
    exit;
}

// Inclure les fichiers nécessaires de PHPMailer
require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/emails/PHPMailer-master/src/Exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/emails/PHPMailer-master/src/PHPMailer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/emails/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// fonction pour generer un token
function generateToken($id, $ride_id) {
    global $pdo;
    $token = bin2hex(random_bytes(16));
    $query = "INSERT INTO validation_tokens (user_id, ride_id, token, expiration) VALUES (:user_id, :ride_id, :token, NOW() + INTERVAL 24 HOUR)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $id, 'ride_id' => $ride_id, 'token' => $token]);
    return $token;
}

// Fonction pour envoyer l'email de validation
function sendValidationEmail($toEmail, $rideId, $id) {
    $mail = new PHPMailer(true);
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];    
    $token = generateToken($id, $rideId);
    $validationLink = "http://localhost/ecoride/frontend/validation.php?ride_id=$rideId&token=$token";
    // Configuration PHPMailer et envoi de l'email

    

    try {
        // Paramètres SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  
        $mail->SMTPAuth = true;
        $mail->Username = 'kevingoub@gmail.com';  
        $mail->Password = 'otpl hcnf ityj jhwb';  
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Destinataire
        $mail->setFrom('kevingoub@gmail.com', 'Kevin Goub');
        $mail->addAddress($toEmail); 

        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = 'Validation de votre participation';
        // Générer un token unique pour l'utilisateur
        $validationLink = "http://localhost/ecoride/frontend/validation.php?ride_id=" . $rideId . "&token=" . $token;
        $mail->Body = '
        Bonjour ,<br><br>
        Nous vous remercions pour votre participation à notre événement. Nous espérons que tout s\'est bien déroulé.<br><br>
        Afin de valider votre expérience, nous vous invitons à vous rendre sur votre espace personnel. Une fois connecté(e), veuillez confirmer que tout s\'est bien passé en validant la case correspondante.<br><br>
        Si vous avez rencontré un problème ou si vous avez des questions, n\'hésitez pas à nous contacter.<br><br>
        Merci encore pour votre participation et à bientôt !<br><br>
        Cordialement,<br>
        L\'équipe EcoRides
        <a href="' . $validationLink . '">Cliquez ici pour valider votre participation</a>
        ';

        // Envoi de l'email
        if ($mail->send()) {
            return true; // Retourne true si l'email a été envoyé
        } else {
            return false; // Retourne false si l'envoi échoue
        }
    } catch (Exception $e) {
        echo 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage(); // Afficher l'erreur
        return false; // Retourne false si une exception se produit
    }
}

// Fonction pour récupérer les participants d'un covoiturage
function getParticipantsForRide($id) {
    global $pdo;
    $query = "SELECT users.email, users.id FROM users INNER JOIN reservations ON users.id = reservations.user_id WHERE covoiturage_id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer l'ID du covoiturage en fonction de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$query = "SELECT id FROM covoiturages WHERE user_id = :user_id";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_STR);
$stmt->execute();
$covoiturage = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialiser la réponse JSON
$response = [
    'success' => false,
    'message' => 'Aucun covoiturage trouvé pour l\'utilisateur connecté.',
    'emails_sent' => [],
    'emails_failed' => []
];

// Vérifier si un covoiturage a été trouvé pour l'utilisateur
if ($covoiturage && isset($covoiturage['id'])) {
    $id = $covoiturage['id'];  // Récupérer l'ID du covoiturage

    // Récupérer les participants pour ce covoiturage
    $participants = getParticipantsForRide($id);

    // Vérifier si des participants ont été trouvés
    if (!empty($participants)) {
        $response['success'] = true;
        $response['message'] = 'Participants trouvés pour le covoiturage ID : ' . $id;

        // Envoi des emails de validation
        foreach ($participants as $participant) {
            $email = $participant['email'];
            $userID = $participant['id'];

            if (sendValidationEmail($email, $id, $userID)) {
                $response['emails_sent'][] = $email;
            } else {
                $response['emails_failed'][] = $email;
            }
        }
    } else {
        $response['message'] = 'Aucun participant trouvé pour le covoiturage ID : ' . $id;
    }
}

// Retourner la réponse JSON
echo json_encode($response);
exit;
?>
