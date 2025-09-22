<?php
require __DIR__ . '/init.php'; // session_start + BASE_URL + $pdo=getPDO()

// 1) Auth obligatoire
if (empty($_SESSION['user_email'])) {
    http_response_code(401);
    exit('Aucun utilisateur connecté.');
}

// 2) Méthode POST uniquement
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

// 3) CSRF
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit('Token CSRF invalide.');
}

// 4) Valider l'ID de réservation
$reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
if (!$reservationId) {
    http_response_code(400);
    exit('ID de réservation invalide ou manquant.');
}

// 5) Récupérer l'utilisateur courant
$stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmtUser->execute([$_SESSION['user_email']]);
$user = $stmtUser->fetch();
if (!$user) {
    http_response_code(404);
    exit('Utilisateur non trouvé.');
}

try {
    $pdo->beginTransaction();

    // 6) Charger la réservation (appartenant à l'utilisateur)
    $stmtReservation = $pdo->prepare("
        SELECT id, covoiturage_id, places_reservees, statut
        FROM reservations
        WHERE id = ? AND user_id = ?
        FOR UPDATE
    ");
    $stmtReservation->execute([$reservationId, $user['id']]);
    $reservation = $stmtReservation->fetch();

    if (!$reservation) {
        $pdo->rollBack();
        http_response_code(403);
        exit('Réservation non trouvée ou accès non autorisé.');
    }

    $rideId          = (int)$reservation['covoiturage_id'];
    $placesReservees = (int)$reservation['places_reservees'];

    // 7) Charger le covoiturage pour ajuster les compteurs
    $stmtCovoiturage = $pdo->prepare("
        SELECT id, passagers, places_restantes, nb_places_disponibles, statut, date, heure_depart, prix
        FROM covoiturages
        WHERE id = ?
        FOR UPDATE
    ");
    $stmtCovoiturage->execute([$rideId]);
    $covoiturage = $stmtCovoiturage->fetch();

    if (!$covoiturage) {
        $pdo->rollBack();
        http_response_code(404);
        exit('Covoiturage introuvable.');
    }

    $statutRide = strtolower(trim((string)$covoiturage['statut']));
    if ($statutRide === 'en cours' || $statutRide === 'terminé') {
        $pdo->rollBack();
        http_response_code(409);
        exit("Vous ne pouvez plus annuler : le covoiturage a déjà démarré (ou est terminé).");
        }

    $passagers           = (int)$covoiturage['passagers'];
    $placesRestantes     = (int)$covoiturage['places_restantes'];
    $nbPlacesDisponibles = (int)$covoiturage['nb_places_disponibles'];
    
    //) Politique simple : rembourser si le trajet n’a pas commencé
        $refund = false;
        $prix      = (float)$covoiturage['prix'];
        $places    = (int)$reservation['places_reservees'];
        $refundAmt = $prix * $places;

        // Exemple : rembourser si statut 'en attente'
        if ($statutRide === 'en attente') {
        $refund = true;
        }

        if ($refund) {
        $stRefund = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
        $stRefund->execute([$refundAmt, (int)$user['id']]);
        }

    // 8) Calculs sécurisés
    $newPassagers       = max(0, $passagers - $placesReservees);
    $newPlacesRestantes = min($nbPlacesDisponibles, $placesRestantes + $placesReservees);

    // 9) Mettre à jour le covoiturage
    $stmtUpdate = $pdo->prepare("
        UPDATE covoiturages
        SET passagers = ?, places_restantes = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$newPassagers, $newPlacesRestantes, $rideId]);

    // 10) Supprimer la réservation
    $stmtDelete = $pdo->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ?");
    $stmtDelete->execute([$reservationId, $user['id']]);

    if ($stmtDelete->rowCount() !== 1) {
        $pdo->rollBack();
        http_response_code(409);
        exit("Annulation impossible (déjà annulée ?).");
    }

    $pdo->commit();

    header('Location: ' . BASE_URL . 'profil.php?message=' . urlencode('Annulation réussie'));
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    exit("Erreur lors de l'annulation de la réservation.");
}

