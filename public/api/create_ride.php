<?php
// api_add_trip.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../init.php';             // ← lance session + BASE_URL + $pdo = getPDO()
header('Content-Type: application/json; charset=utf-8');

$isLoggedIn  = isset($_SESSION['user_email']);
$user_email  = $_SESSION['user_email'] ?? null;

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Vous devez être connecté pour saisir un voyage.']);
    exit;
}

try {
    // Récupère l’utilisateur courant
    $stmtUser = $pdo->prepare("SELECT id, credits, status, lastName, firstName FROM users WHERE email = ?");
    $stmtUser->execute([$user_email]);
    $user = $stmtUser->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Utilisateur non trouvé.']);
        exit;
    }

    if (!in_array($user['status'], ['chauffeur','passager_chauffeur'], true)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Vous n’êtes pas autorisé à ajouter un voyage.']);
        exit;
    }

    // Méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Méthode non supportée.']);
        exit;
    }

    // Récup des champs (form-data)
    $depart        = trim($_POST['depart']        ?? '');
    $destination   = trim($_POST['destination']   ?? '');
    $prix          = (float)($_POST['prix']       ?? 0);
    $vehicule_id   = (int)  ($_POST['vehicule_id']?? 0);
    $heure_depart  = $_POST['heure_depart'] ?? '';
    $duree         = $_POST['duree']        ?? '';   // HH:MM:SS
    $date          = $_POST['date']         ?? '';   // YYYY-MM-DD
    $places_restantes = (int)($_POST['places_restantes'] ?? 0);

    // Validations minimales
    $errors = [];
    if ($depart === '')         $errors['depart'] = 'Depart requis';
    if ($destination === '')    $errors['destination'] = 'Destination requise';
    if ($prix < 0)              $errors['prix'] = 'Prix invalide';
    if ($vehicule_id <= 0)      $errors['vehicule_id'] = 'Véhicule invalide';
    if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $heure_depart)) $errors['heure_depart'] = 'Heure invalide';
    if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $duree))        $errors['duree'] = 'Durée invalide';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))            $errors['date'] = 'Date invalide';
    if ($places_restantes <= 0) $errors['places_restantes'] = 'Places invalides';

    if ($errors) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Validation échouée', 'fields' => $errors]);
        exit;
    }

    if ((int)$user['credits'] < 2) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Crédits insuffisants (2 requis).']);
        exit;
    }

    // Vérifier le véhicule (appartenance + places)
    $stmtVehicule = $pdo->prepare("SELECT id, modele, marque, energie, nb_places_disponibles FROM chauffeur_info WHERE id = ? AND user_id = ?");
    $stmtVehicule->execute([$vehicule_id, $user['id']]);
    $vehicule = $stmtVehicule->fetch();

    if (!$vehicule) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Véhicule introuvable ou non autorisé.']);
        exit;
    }

    $nb_places_disponibles = (int)($vehicule['nb_places_disponibles'] ?? 0);
    if ($places_restantes > $nb_places_disponibles) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Nombre de places sélectionnées supérieur au disponible.']);
        exit;
    }

    // Heure arrivée = heure_depart + durée
    $hdep_ts   = strtotime($heure_depart);
    $duree_sec = strtotime($duree) - strtotime('00:00:00');
    $harr_ts   = $hdep_ts + max(0, $duree_sec);
    $heure_arrivee = date('H:i:s', $harr_ts);

    // Eco score selon énergie
    $ecologique = in_array(strtolower($vehicule['energie']), ['electrique','hybride'], true) ? 1 : 0;

    // Prix facturé en base 
    $prix_facture = max(0, $prix - 2);

    // Transaction : insert + débit de crédits ensemble
    $pdo->beginTransaction();

    $stmtInsert = $pdo->prepare("
        INSERT INTO covoiturages
            (depart, destination, prix, vehicule_id, user_id, conducteur, places_restantes,
             note, heure_depart, duree, passagers, ecologique, photo, nb_places_disponibles,
             modele_voiture, marque_voiture, energie_voiture, heure_arrivee, `date`)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $conducteur = trim($user['firstName'].' '.$user['lastName']);
    $note = 0;
    $photo = 'default.jpg';
    $passagers = 0;

    $stmtInsert->execute([
        $depart,
        $destination,
        $prix_facture,
        $vehicule_id,
        $user['id'],
        $conducteur,
        $places_restantes,
        $note,
        date('H:i:s', $hdep_ts),
        date('H:i:s', strtotime($duree)), // normalise HH:MM:SS
        $passagers,
        $ecologique,
        $photo,
        $nb_places_disponibles,
        $vehicule['modele'],
        $vehicule['marque'],
        $vehicule['energie'],
        $heure_arrivee,
        $date
    ]);

    // Débiter 2 crédits
    $stmtCredits = $pdo->prepare("UPDATE users SET credits = credits - 2 WHERE id = ? AND credits >= 2");
    $stmtCredits->execute([$user['id']]);

    if ($stmtCredits->rowCount() !== 1) {
        // Protection si double soumission
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Crédits insuffisants.']);
        exit;
    }

    $pdo->commit();

    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Voyage ajouté avec succès.', 'id' => $pdo->lastInsertId()]);

} catch (Throwable $e) {
    if ($pdo?->inTransaction()) {
        $pdo->rollBack();
    }
    // En dev, loggue $e->getMessage()
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur.']);
}

exit;
