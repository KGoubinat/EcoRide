<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

session_start();

if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Aucun utilisateur connecté.']);
    exit;
}

$databaseUrl = getenv('JAWSDB_URL');

if (!$databaseUrl) {
    echo json_encode(['success' => false, 'message' => 'Configuration de la base de données manquante.']);
    exit;
}

$parsedUrl = parse_url($databaseUrl);

$servername = $parsedUrl['host'];
$username = $parsedUrl['user'];
$password = $parsedUrl['pass'];
$dbname = ltrim($parsedUrl['path'], '/');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion : ' . $e->getMessage()]);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/emails/PHPMailer-master/src/Exception.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/emails/PHPMailer-master/src/PHPMailer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/emails/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateToken($userId, $rideId) {
    global $conn;
    $token = bin2hex(random_bytes(16));
    $query = "INSERT INTO validation_tokens (user_id, ride_id, token, expiration) VALUES (:user_id, :ride_id, :token, DATE_ADD(NOW(), INTERVAL 24 HOUR))";
    $stmt = $conn->prepare($query);
    $stmt->execute(['user_id' => $userId, 'ride_id' => $rideId, 'token' => $token]);
    return $token;
}

function sendValidationEmail($toEmail, $rideId, $userId) {
    $mail = new PHPMailer(true);
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    $token = generateToken($userId, $rideId);
    $validationLink = "https://ecoride-covoiturage-app-fe35411c6ec7.herokuapp.com/frontend/validation.php?ride_id=$rideId&token=$token";

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'kevingoub@gmail.com';
        $mail->Password = getenv('MAIL_PASSWORD');
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('kevingoub@gmail.com', 'Kevin Goub');
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Validation de votre participation';
        $mail->Body = "
        Bonjour,<br><br>
        Nous vous remercions pour votre participation à notre événement. Nous espérons que tout s'est bien déroulé.<br><br>
        Afin de valider votre expérience, nous vous invitons à vous rendre sur votre espace personnel. Une fois connecté(e), veuillez confirmer que tout s'est bien passé en validant la case correspondante.<br><br>
        Si vous avez rencontré un problème ou si vous avez des questions, n'hésitez pas à nous contacter.<br><br>
        Merci encore pour votre participation et à bientôt !<br><br>
        Cordialement,<br>
        L'équipe EcoRides<br><br>
        <a href='$validationLink'>Cliquez ici pour valider votre participation</a>
        ";

    // DEBUG SMTP VERBEUX DANS LES LOGS
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        error_log("SMTP Debug level $level; message: $str");
    };
    
        return $mail->send();
    } catch (Exception $e) {
        error_log('Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        return false;
    }
}

function getParticipantsForRide($rideId) {
    global $conn;
    $query = "SELECT users.email, users.id FROM users 
              INNER JOIN reservations ON users.id = reservations.user_id 
              WHERE covoiturage_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute(['id' => $rideId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$user_id = $_SESSION['user_id'];

$query = "SELECT id FROM covoiturages WHERE user_id = :user_id";
$stmt = $conn->prepare($query);
$stmt->execute(['user_id' => $user_id]);
$covoiturage = $stmt->fetch(PDO::FETCH_ASSOC);

$response = [
    'success' => false,
    'message' => 'Aucun covoiturage trouvé pour l\'utilisateur connecté.',
    'emails_sent' => [],
    'emails_failed' => []
];

if ($covoiturage && isset($covoiturage['id'])) {
    $rideId = $covoiturage['id'];
    $participants = getParticipantsForRide($rideId);

    if (!empty($participants)) {
        $response['success'] = true;
        $response['message'] = "Participants trouvés pour le covoiturage ID : $rideId";

        foreach ($participants as $participant) {
            $email = $participant['email'];
            $participantId = $participant['id'];

            if (sendValidationEmail($email, $rideId, $participantId)) {
                $response['emails_sent'][] = $email;
            } else {
                $response['emails_failed'][] = $email;
            }
        }
    } else {
        $response['message'] = "Aucun participant trouvé pour le covoiturage ID : $rideId";
    }
}

echo json_encode($response);
exit;
?>
