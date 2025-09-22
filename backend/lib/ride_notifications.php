<?php
declare(strict_types=1);

require_once __DIR__ . '/mailer.php'; // doit fournir sendEmail($to,$subject,$html,$textAlt)

/**
 * Retourne détails d’un covoiturage + nom & email conducteur.
 */
function getRideDetails(PDO $pdo, int $rideId): ?array {
    $sql = "SELECT c.*,
                   u.email AS driver_email,
                   CONCAT(u.firstName,' ',u.lastName) AS driver_name
            FROM covoiturages c
            JOIN users u ON u.id = c.user_id
            WHERE c.id = ?";
    $st = $pdo->prepare($sql);
    $st->execute([$rideId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/**
 * Emails des passagers pour un trajet.
 * $mode = 'any' (par défaut), 'active' (hors annulés), 'finished' (statut terminé)
 * Retourne user_id, email, firstName.
 */
function getRidePassengers(PDO $pdo, int $rideId, string $mode = 'any'): array {
    $whereStatut = "1=1";
    if ($mode === 'active') {
        $whereStatut = "(r.statut IS NULL OR r.statut NOT IN ('annulée','annule','annulee','annulé','annule','cancelled'))";
    } elseif ($mode === 'finished') {
        $whereStatut = "r.statut = 'terminé'";
    }

    $sql = "SELECT DISTINCT u.id AS user_id, u.email, u.firstName
            FROM reservations r
            JOIN users u ON u.id = r.user_id
            WHERE r.covoiturage_id = ?
              AND $whereStatut";
    $st = $pdo->prepare($sql);
    $st->execute([$rideId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/** Crée/renouvelle un token de validation (48h par défaut). */
function createValidationToken(PDO $pdo, int $rideId, int $userId, int $ttlHours = 48): string {
    $token = bin2hex(random_bytes(32));
    $sql = "INSERT INTO validation_tokens (ride_id, user_id, token, expiration, used_at)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR), NULL)
            ON DUPLICATE KEY UPDATE token = VALUES(token),
                                    expiration = VALUES(expiration),
                                    used_at = NULL";
    $st = $pdo->prepare($sql);
    $st->execute([$rideId, $userId, $token, $ttlHours]);
    return $token;
}

/* ---------- Helpers d’email HTML ---------- */
function er_mail_layout(string $title, string $contentHtml): string {
    return "
<!doctype html>
<html>
<head>
<meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>".htmlspecialchars($title, ENT_QUOTES)."</title>
</head>
<body style='margin:0;padding:0;background:#f5f7fb;'>
  <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='background:#f5f7fb;padding:24px 0;'>
    <tr><td align='center'>
      <table role='presentation' width='600' cellspacing='0' cellpadding='0' style='background:#ffffff;border-radius:8px;font-family:Arial,Helvetica,sans-serif;color:#333;box-shadow:0 2px 10px rgba(0,0,0,.05);'>
        <tr>
          <td style='padding:20px 24px;border-bottom:1px solid #eee;'>
            <div style='font-size:20px;font-weight:700;color:#111;'>EcoRide</div>
          </td>
        </tr>
        <tr>
          <td style='padding:24px; font-size:15px; line-height:1.5;'>
            {$contentHtml}
          </td>
        </tr>
        <tr>
          <td style='padding:16px 24px;border-top:1px solid #eee; font-size:12px; color:#888;'>
            © ".date('Y')." EcoRide — Merci de votre confiance.
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>";
}

function er_button(string $href, string $label): string {
    $h = htmlspecialchars($href, ENT_QUOTES);
    $l = htmlspecialchars($label, ENT_QUOTES);
    return "<a href='{$h}' style='display:inline-block;padding:12px 18px;background:#26a69a;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;'>{$l}</a>";
}

/**
 * Envoie un email à tous (passagers + éventuellement conducteur).
 * $event: 'start' | 'end' | 'cancel'
 * - start/cancel : email générique avec lien vers profil + bouton "Signaler un problème"
 * - end : email personnalisé à CHAQUE passager (hors chauffeur) avec lien validation.php + token
 *         + bouton "Signaler un problème"
 */
function notifyRideEvent(PDO $pdo, int $rideId, string $event, int $triggerUserId, bool $includeDriver = false): void {
    $ride = getRideDetails($pdo, $rideId);
    if (!$ride) return;

    // Chauffeur
    $driverId = (int)$ride['user_id'];

    // Lien "Signaler un problème" pour TOUS les mails
    $reportLink = BASE_URL . "report_problem.php?ride_id={$rideId}";

    // ------- Cas "end" : email individualisé à chaque passager terminé -------
    if ($event === 'end') {
        $passengers = getRidePassengers($pdo, $rideId, 'finished');

        foreach ($passengers as $p) {
            if (empty($p['email'])) continue;

            $token = createValidationToken($pdo, $rideId, (int)$p['user_id']);
            $link  = BASE_URL . "validation.php?ride_id={$rideId}&token={$token}";

            $name    = trim((string)($p['firstName'] ?? '')) ?: 'Bonjour';
            $depart  = htmlspecialchars((string)$ride['depart'], ENT_QUOTES);
            $dest    = htmlspecialchars((string)$ride['destination'], ENT_QUOTES);
            $dateTxt = (new DateTime($ride['date']))->format('d/m/Y');
            $timeTxt = date('H:i', strtotime($ride['heure_depart']));
            $title   = 'Votre covoiturage est terminé';

            $content = "
              <p>{$name},</p>
              <p>Le trajet <strong>{$depart} → {$dest}</strong> du <strong>{$dateTxt}</strong> à <strong>{$timeTxt}</strong> est terminé.</p>
              <p>Votre avis aide la communauté :</p>
              <p>".er_button($link, "Donner mon avis")."</p>
              <p style='color:#777;font-size:13px'>Ce lien est valable 48h.</p>
              <hr style='border:none;border-top:1px solid #eee;margin:20px 0'>
              <p>Un problème pendant le trajet ?</p>
              <p>".er_button($reportLink, "Signaler un problème")."</p>
              <p style='color:#777;font-size:13px'>Vous devrez être connecté pour soumettre le signalement.</p>
            ";
            $html = er_mail_layout($title, $content);

            $alt  = "{$name},\n\n"
                  . "Trajet {$ride['depart']} → {$ride['destination']} le {$dateTxt} à {$timeTxt} terminé.\n"
                  . "Donnez votre avis : {$link}\n"
                  . "Signaler un problème : {$reportLink}\n\n"
                  . "— EcoRide";

            @sendEmail((string)$p['email'], "[EcoRide] {$title}", $html, $alt);
        }
        return; // pas d'email chauffeur ici (sauf besoin)
    }

    // ------- Cas "start" / "cancel" : mail générique -------
    $passengers = getRidePassengers($pdo, $rideId, 'active');

    // Liste des destinataires
    $recipients = [];
    foreach ($passengers as $p) {
        if (!empty($p['email'])) $recipients[$p['email']] = $p['email'];
    }
    if ($includeDriver && !empty($ride['driver_email'])) {
        $recipients[$ride['driver_email']] = $ride['driver_email'];
    }
    if (!$recipients) return;

    // Sujet + contenu
    $title =
        $event === 'start'  ? 'Votre covoiturage a démarré' :
        ($event === 'cancel' ? 'Covoiturage annulé' : 'Notification EcoRide');

    $details = sprintf(
        'Trajet %s → %s, le %s à %s',
        htmlspecialchars((string)$ride['depart']),
        htmlspecialchars((string)$ride['destination']),
        htmlspecialchars((string)$ride['date']),
        htmlspecialchars((string)$ride['heure_depart'])
    );

    $profilLink = BASE_URL . 'profil.php';
    $content = "
      <p>Bonjour,</p>
      <p>{$title}.</p>
      <p><strong>{$details}</strong></p>
      <p>Conducteur : " . htmlspecialchars((string)$ride['driver_name']) . "</p>
      <p>".er_button($profilLink, "Voir sur votre profil")."</p>
      <hr style='border:none;border-top:1px solid #eee;margin:20px 0'>
      <p>Un problème ?</p>
      <p>".er_button($reportLink, "Signaler un problème")."</p>
    ";
    $html = er_mail_layout($title, $content);

    $alt = "Bonjour,\n\n{$title}.\n{$details}\nConducteur : {$ride['driver_name']}\n"
         . "Voir sur votre profil : {$profilLink}\n"
         . "Signaler un problème : {$reportLink}\n— EcoRide";

    foreach ($recipients as $email) {
        @sendEmail($email, "[EcoRide] {$title}", $html, $alt);
    }
}
