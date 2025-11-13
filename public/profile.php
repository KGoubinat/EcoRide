<?php
// profile.php


require __DIR__ . '/init.php'; // -> session_start(), BASE_URL, getPDO()

// Nonce unique par requÃªte (pour autoriser nos <script> inline sans 'unsafe-inline')
$nonce = base64_encode(random_bytes(16));

// (HTTPS recommandé en prod)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == '443');

// HSTS (uniquement si HTTPS)
if ($isHttps) {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// Isolation et clickjacking
header("Cross-Origin-Opener-Policy: same-origin");
header("X-Frame-Options: DENY"); // équivalent Ã  frame-ancestors 'none'

// Référent & permissions
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), camera=(), microphone=()");

// CSP stricte (ajuste si tu ajoutes des domaines)
$csp = [
  "default-src 'self'",
  "base-uri 'self'",
  "object-src 'none'",
  "img-src 'self' data: blob: https://res.cloudinary.com", // temporaire si Cloudinary direct
  "style-src 'self' 'unsafe-inline'", // garde 'unsafe-inline' pour ton CSS inline; idéalement Ã  retirer
  "font-src 'self' data:",
  "connect-src 'self'",
  "frame-ancestors 'none'",
  "script-src 'self' 'nonce-{$nonce}'", // autorise seulement scripts self + nonce (pas d'inline non-noncé)
  "require-trusted-types-for 'script'",
  "trusted-types default myapp", // nom de policy arbitraire
  // "upgrade-insecure-requests" // dé-commente si tu as du http mixte en prod
];

header('Content-Security-Policy: ' . implode('; ', $csp));
// Redirige si non connecté
if (empty($_SESSION['user_email'])) {
    header('Location: ' . BASE_URL . 'login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$pdo        = getPDO();
$user_email = $_SESSION['user_email'];

// CSRF token (créé si absent)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Récupération utilisateur
$stmtUser = $pdo->prepare("
    SELECT id, firstName, lastName, email, photo, credits, status
    FROM users
    WHERE email = ?
");
$stmtUser->execute([$user_email]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    exit('Utilisateur non trouvé.');
}

$userId = (int)$user['id'];

$isLoggedIn = true;

// Mise Ã  jour du statut utilisateur (POST classique depuis le formulaire)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['status'], $_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        exit('Token CSRF invalide.');
    }
    $newStatus = (string)$_POST['status'];
    if (in_array($newStatus, ['passager','chauffeur','passager_chauffeur'], true)) {
        $st = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $st->execute([$newStatus, $userId]);
        $user['status'] = $newStatus; // maj locale pour lâ€™affichage
    }
}

// Infos chauffeur (si chauffeur/passager_chauffeur)
$chauffeurInfo = null;
$smoker_preference = 0;
$pet_preference    = 0;

if (in_array($user['status'], ['chauffeur','passager_chauffeur'], true)) {
    $stCh = $pdo->prepare("SELECT * FROM chauffeur_info WHERE user_id = ?");
    $stCh->execute([$userId]);
    $chauffeurInfo = $stCh->fetch(PDO::FETCH_ASSOC) ?: null;

    $smoker_preference = (int)($chauffeurInfo['smoker_preference'] ?? 0);
    $pet_preference    = (int)($chauffeurInfo['pet_preference'] ?? 0);
}

// Note moyenne du conducteur
$avgNote = null;
$nbAvis  = 0;

$stAvg = $pdo->prepare("
    SELECT ROUND(AVG(note), 1) AS avg_note, COUNT(*) AS nb
    FROM avis_conducteurs
    WHERE conducteur_id = ? AND note IS NOT NULL
");
$stAvg->execute([$userId]);
$rowAvg = $stAvg->fetch(PDO::FETCH_ASSOC);

if ($rowAvg && (int)$rowAvg['nb'] > 0) {
    $avgNote = (float)$rowAvg['avg_note'];
    $nbAvis  = (int)$rowAvg['nb'];
}

// Réservations de lâ€™utilisateur
$stmtReservations = $pdo->prepare("
    SELECT 
        r.id AS reservation_id,
        c.id AS covoiturage_id,
        c.depart, c.destination,
        c.heure_depart,
        c.date AS date_traject,
        c.conducteur AS chauffeur_name,
        r.statut
    FROM reservations r
    JOIN covoiturages c ON r.covoiturage_id = c.id
    WHERE r.user_id = ?
    ORDER BY c.date DESC, c.heure_depart DESC
");
$stmtReservations->execute([$userId]);
$reservations = $stmtReservations->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Covoiturages proposés par lâ€™utilisateur
$stmtOffered = $pdo->prepare("
    SELECT 
        c.id AS ride_id,
        c.depart, c.destination,
        c.heure_depart, c.date AS date_traject,
        c.prix, c.places_restantes,
        c.statut AS ride_status,
        CONCAT(u.firstName, ' ', u.lastName) AS conducteur
    FROM covoiturages c
    JOIN users u ON c.user_id = u.id
    WHERE c.user_id = ?
    ORDER BY c.date DESC, c.heure_depart DESC
    LIMIT 5
");
$stmtOffered->execute([$userId]);
$offeredRides = $stmtOffered->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Villes (pour datalist)
$villes = [];
if (in_array($user['status'], ['chauffeur','passager_chauffeur'], true)) {
    $stVilles = $pdo->query("SELECT nom FROM villes");
    $villes = $stVilles->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

// Véhicules du chauffeur (si rÃ´le) 
$vehicules = [];
if (in_array($user['status'], ['chauffeur','passager_chauffeur'], true)) {
    $stVeh = $pdo->prepare("SELECT id, modele, marque FROM chauffeur_info WHERE user_id = ?");
    $stVeh->execute([$userId]);
    $vehicules = $stVeh->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Photo de profil (fallback)
$photo = trim((string)($user['photo'] ?? ''));
$photoUrl = $photo !== '' ? $photo : 'assets/images/default-avatar.png';

// --- Mes véhicules (liste complète) ---
$vehicules = [];
$stVeh = $pdo->prepare("
    SELECT id, plaque_immatriculation, date_1ere_immat, modele, marque,
           nb_places_disponibles, preferences, smoker_preference, pet_preference, energie
    FROM chauffeur_info
    WHERE user_id = ?
    ORDER BY id DESC
");
$stVeh->execute([$userId]);
$vehicules = $stVeh->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Pour éviter le tracking via Cloudinary, on sert les images via un proxy cÃ´té serveur
function cookieless_img(string $url): string {
    // fait /public/cdn.php?path=... Ã  partir dâ€™une URL Cloudinary
    if (preg_match('~^https?://res\.cloudinary\.com/(.+)$~i', $url, $m)) {
        return 'cdn.php?path=' . $m[1];
    }
    return $url; // déjÃ  first-party
}


function cld_variant_w(string $url, int $w): string {
  // carré, fill intelligent, qualité/format auto + léger sharpen
  $t = "c_fill,g_auto,w_{$w},h_{$w},f_auto,q_auto:good,e_sharpen:30";
  return preg_replace('~/upload/~', "/upload/{$t}/", $url, 1);
}

$avatarSrc = cookieless_img($photoUrl);
$avatarSet = '';
// largeur CSS visée : 360px max sur desktop
$sizes = "(min-width: 1200px) 360px, (min-width: 900px) 320px, (min-width: 600px) 240px, 180px";

if (preg_match('~^https?://res\.cloudinary\.com/[^/]+/image/upload/~', $photoUrl)) {
  $widths = [180, 240, 320, 360, 448, 512, 640, 768, 896, 1024];
  $parts = [];
  foreach ($widths as $w) {
    $urlW = htmlspecialchars(cookieless_img(cld_variant_w($photoUrl, $w)), ENT_QUOTES);
    $parts[] = "{$urlW} {$w}w";
  }
  $avatarSet = implode(', ', $parts);
  // src par défaut â‰ˆ cible desktop
  $avatarSrc = htmlspecialchars(cookieless_img(cld_variant_w($photoUrl, 360)), ENT_QUOTES);
}




?>
<!DOCTYPE html>
<html lang="fr">
<!-- Preload du fond réellement utilisé en mobile -->

<head>
    <meta charset="UTF-8">
    <title>EcoRide - Mon profil</title>
    <meta name="description" content="Gérez vos informations personnelles, vos trajets et préférences de covoiturage dans votre espace EcoRide.">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/modern.css">
    <link rel="preload" as="image" href="assets/images/Fond-768.jpg"  type="image/jpg" media="(max-width: 767px)"  fetchpriority="high">
    <link rel="preload" as="image" href="assets/images/Fond-1280.jpg" type="image/jpg" media="(min-width: 768px) and (max-width: 1279px)" fetchpriority="high">
    <link rel="preload" as="image" href="assets/images/Fond-1920.jpg" type="image/jpg" media="(min-width: 1280px)" fetchpriority="high">
    
    
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <h1>Profil de <?= htmlspecialchars($user['firstName']) ?></h1>
        </div>
        <div class="menu-toggle" id="menu-toggle">☰</div>
        <nav id="navbar">
            <ul>
                <li><a href="home.php">Accueil</a></li>
                <li><a href="contact_info.php">Contact</a></li>
                <li><a href="rides.php">Covoiturages</a></li>

                <!-- Uniformiser avec le JS des autres pages -->
                <li id="profilButton"
                    data-logged-in="true"><a href="profile.php"  aria-current="page" >Profil</a></li>
                <li id="authButton"
                    data-logged-in="true" data-user-email="<?= htmlspecialchars((string)$user_email, ENT_QUOTES) ?>">
                    <a href="logout.php">Déconnexion</a>

                </li>
            </ul>
        </nav>
    </div>
    <!-- Menu mobile -->
  <nav id="mobile-menu">
    <ul>
      <li><a href="home.php">Accueil</a></li>
      <li><a href="rides.php">Covoiturages</a></li>
      <li><a href="contact_info.php">Contact</a></li>
      <li id="profilButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
      <li id="authButtonMobile"   data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
    </ul>
  </nav>
</header>

<main class="adaptation">
    <div class="container">

        <!-- Informations personnelles -->
        <div class="user-info">
            <h2>Informations personnelles</h2>

            <div class="profil-photo">
                <a href="#" id="change-photo-link" class="photo-trigger"
                    aria-controls="change-photo-form" aria-expanded="false" title="Changer la photo">
                    <img
                        src="<?= $avatarSrc ?>"
                        <?php if ($avatarSet): ?>srcset="<?= $avatarSet ?>" sizes="<?= $sizes ?>"<?php endif; ?>
                        width="360" height="360"
                        alt="Photo de profil"
                        class="profile-img"
                        decoding="async"
                        fetchpriority="high">


                    <span class="profile-hover"><span class="icon"></span> CHANGER LA PHOTO</span>
                </a>

                <!-- Formulaire de téléchargement d'image caché -->
                <div id="change-photo-form" hidden>
                    <form id="upload-photo-form" action="../backend/handlers/upload_photo.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                    <input type="file" id="photo-input" name="photo" accept="image/*" required>
                    </form>
                </div>
                </div>


            <div class="info-card">
                <p><strong>Nom :</strong> <?= htmlspecialchars($user['lastName']) ?></p>
                <p><strong>Prénom :</strong> <?= htmlspecialchars($user['firstName']) ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Crédits :</strong> <?= (float)$user['credits'] ?> crédits</p>
                <p><strong>Note moyenne :</strong>
                    <?= $avgNote !== null ? number_format($avgNote, 1, ',', ' ') . ' / 5' : '—' ?>
                    <?php if ($nbAvis > 0): ?>
                        <small>(<?= $nbAvis ?> avis)</small>
                    <?php endif; ?>
                </p>
            </div>

            <div>
            <h2>Statut : <?= htmlspecialchars($user['status']) ?></h2>
            <form id="status-form" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                <label for="status">Choisissez votre statut :</label>
                <select name="status" id="status">
                    <option value="passager"           <?= $user['status']==='passager'?'selected':''; ?>>Passager</option>
                    <option value="chauffeur"          <?= $user['status']==='chauffeur'?'selected':''; ?>>Chauffeur</option>
                    <option value="passager_chauffeur" <?= $user['status']==='passager_chauffeur'?'selected':''; ?>>Passager & Chauffeur</option>
                </select>
                <button type="submit" class="button">Mettre à jour</button>

            </form>
        </div>
        </div> 

        <!-- Réservations passées -->
        <div class="user-reservations">
            <h2>Vos réservations</h2>
            <?php if ($reservations): ?>
                <div class="reservations-list">
                    <?php foreach ($reservations as $res): 
                        $resStatus = strtolower(trim((string)$res['statut']));
                        $canCancelRes = ($resStatus === 'en attente');
                        $canDeleteRes = in_array($resStatus, ['terminé','annulé'], true);
                    ?>
                        <div class="reservation-item">
                            <h3>Chauffeur : <?= htmlspecialchars($res['chauffeur_name']) ?></h3>
                            <p><strong>Trajet :</strong> <?= htmlspecialchars($res['depart']) ?> → <?= htmlspecialchars($res['destination']) ?></p>
                            <p><strong>Heure départ :</strong> <?= htmlspecialchars($res['heure_depart']) ?></p>
                            <p><strong>Date :</strong> <?= htmlspecialchars($res['date_traject']) ?></p>
                            <p><strong>Statut :</strong> <?= htmlspecialchars($res['statut']) ?></p>

                            <!-- Annuler la réservation (POST) -->
                            <?php if ($canCancelRes): ?>
                            <form action="../backend/handlers/cancel_reservation.php" method="POST">

                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                                <input type="hidden" name="reservation_id" value="<?= (int)$res['reservation_id'] ?>">
                                <button type="submit" class="btn-danger" data-reservation-id="<?= (int)$res['reservation_id'] ?>">
                                Annuler la réservation
                                </button>
                            </form>
                            <?php endif; ?>

                             <?php if ($canDeleteRes): ?>
                                <!-- Supprimer définitivement -->
                                <form action="../backend/handlers/delete_reservation.php" method="POST">

                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                                <input type="hidden" name="reservation_id" value="<?= (int)$res['reservation_id'] ?>">
                                <button type="submit" class="btn-danger">Supprimer</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <hr>
                        <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Aucune réservation trouvée.</p>
            <?php endif; ?>
        </div>

        <!-- Covoiturages proposés -->
        <div class="user-offered-rides">
            <h2>Covoiturages proposés</h2>
            <?php if ($offeredRides): ?>
                <div class="offered-rides-list">
                    <?php foreach ($offeredRides as $ride):
                        $rideId = (int)$ride['ride_id'];
                        $rideStatusRaw = (string)($ride['ride_status'] ?? '');     
                        $rideStatus    = strtolower(trim($rideStatusRaw));
                        $canCancel     = ($rideStatus === '' || $rideStatus === 'en attente');
                        $showStart     = ($rideStatus === 'en attente');
                        $showEnd       = ($rideStatus === 'en cours');
                        $canDelete     = ($rideStatus === 'terminé' || $rideStatus === 'annulé');
                    ?>
                        <div class="offered-ride-item ride" data-ride-id="<?= (int)$ride['ride_id'] ?>">
                            <h3>Conducteur : <?= htmlspecialchars($ride['conducteur']) ?></h3>
                            <p><strong>Trajet :</strong> <?= htmlspecialchars($ride['depart']) ?> → <?= htmlspecialchars($ride['destination']) ?></p>
                            <p><strong>Heure départ :</strong> <?= htmlspecialchars($ride['heure_depart']) ?></p>
                            <p><strong>Date :</strong> <?= htmlspecialchars($ride['date_traject']) ?></p>
                            <p><strong>Prix :</strong> <?= htmlspecialchars($ride['prix']) ?> crédits</p>
                            <p><strong>Places restantes :</strong> <?= (int)$ride['places_restantes'] ?></p>
                            <p><strong>Statut :</strong> <?= htmlspecialchars($rideStatusRaw) ?></p>
                            

                            <!-- Annuler le covoiturage (POST) -->
                                <?php if ($canCancel): ?>
                                    <form id="cancel-ride-form-<?= $rideId ?>"
                                            action="../backend/handlers/cancel_ride.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                                        <input type="hidden" name="covoiturage_id" value="<?= $rideId ?>">
                                        <button type="submit" class="cancel-ride-button" data-covoiturage-id="<?= $rideId ?>">
                                        Annuler le covoiturage
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if ($showStart): ?>
                                    <button id="start-trip-<?= $rideId ?>" class="btn-start-trip"
                                      data-ride-id="<?= $rideId ?>">Démarrer le covoiturage</button>
                                    <?php endif; ?>

                                    <?php if ($showEnd): ?>
                                    <button id="end-trip-<?= $rideId ?>" class="btn-end-trip"
                                         data-ride-id="<?= $rideId ?>">Arrivée à destination</button>
                                    <?php else: ?>
                                    <button id="end-trip-<?= $rideId ?>" class="btn-end-trip" style="display:none;">Arrivée à destination</button>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                    <form action="../backend/handlers/delete_ride.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                                    <input type="hidden" name="covoiturage_id" value="<?= $rideId ?>">
                                    <button type="submit" class="btn-danger">Supprimer</button>
                                    </form>
                                <?php endif; ?>
                                </div>
                                <hr>
                                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Aucun covoiturage proposé pour le moment.</p>
            <?php endif; ?>
        </div>

        <!-- Formulaire proposer un covoiturage (si chauffeur) -->
        <?php if (in_array($user['status'], ['chauffeur','passager_chauffeur'], true)): ?>
            <div class="saisir-voyage">
                <h2>Proposer un covoiturage</h2>
                <form id="voyageForm" method="POST" action="api/ajoutrides.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

                    <div class="form-group">
                        <label for="depart">Adresse de départ :</label>
                        <input list="cities" id="depart" name="depart" placeholder="Départ" required>
                    </div>

                    <div class="form-group">
                        <label for="destination">Adresse d'arrivée :</label>
                        <input list="cities" id="destination" name="destination" placeholder="Destination" required>
                    </div>

                    <div class="form-group">
                        <label for="places_restantes">Places restantes :</label>
                        <input type="number" id="places_restantes" name="places_restantes" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="date">Date :</label>
                        <input type="date" id="date" name="date" required>
                    </div>

                    <div class="form-group">
                        <label for="heure_depart">Heure de départ :</label>
                        <input type="time" id="heure_depart" name="heure_depart" required>
                    </div>

                    <div class="form-group">
                        <label for="duree">Durée du trajet :</label>
                        <input type="time" id="duree" name="duree" required>
                    </div>

                    <div class="form-group">
                        <label for="prix">Prix (en crédits) :</label>
                        <input type="number" id="prix" name="prix" min="0" step="1" placeholder="Prix du voyage" required>
                    </div>

                    <div class="form-group">
                        <label for="vehicule">Sélectionnez un véhicule :</label>
                        <select name="vehicule_id" id="vehicule" required>
                            <?php foreach ($vehicules as $v): ?>
                                <option value="<?= (int)$v['id'] ?>">
                                    <?= htmlspecialchars($v['modele']) . ' ' . htmlspecialchars($v['marque']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    
                        <button class="button" type="submit" id="saisirVoyageButton">Saisir le covoiturage</button>
                    
                    
                </form>

                <datalist id="cities">
                    <?php foreach ($villes as $ville): ?>
                        <option value="<?= htmlspecialchars($ville) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <!-- Informations véhicule -->
            <section class="chauffeur-info">
                <h2>Informations du véhicule</h2>
                <form id="vehicleForm" method="POST" action="api/add_vehicle.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

                    <div class="form-group">
                        <label for="plaque_immatriculation">Plaque (AB 123 CD) :</label>
                        <input title="Format attendu : AB 123 CD"
                               pattern="^[A-Z]{2}\s?\d{3}\s?[A-Z]{2}$"
                               placeholder="AB 123 CD"
                               type="text" id="plaque_immatriculation" name="plaque_immatriculation"
                               value="<?= htmlspecialchars($chauffeurInfo['plaque_immatriculation'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="date_1ere_immat">Date 1ère immatriculation :</label>
                        <input type="date" id="date_1ere_immat" name="date_1ere_immat"
                               value="<?= htmlspecialchars($chauffeurInfo['date_1ere_immat'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="modele">Modèle :</label>
                        <input type="text" id="modele" name="modele"
                               value="<?= htmlspecialchars($chauffeurInfo['modele'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="marque">Marque :</label>
                        <input type="text" id="marque" name="marque"
                               value="<?= htmlspecialchars($chauffeurInfo['marque'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="energie">Énergie :</label>
                        <select id="energie" name="energie" required>
                            <?php $eng = strtolower((string)($chauffeurInfo['energie'] ?? '')); ?>
                            <option value="diesel"     <?= $eng==='diesel'?'selected':''; ?>>Diesel</option>
                            <option value="essence"    <?= $eng==='essence'?'selected':''; ?>>Essence</option>
                            <option value="hybride"    <?= $eng==='hybride'?'selected':''; ?>>Hybride</option>
                            <option value="electrique" <?= $eng==='electrique'?'selected':''; ?>>Électrique</option>
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="nb_places_disponibles">Nombre de places dispo :</label>
                        <input
                            type="number"
                            id="nb_places_disponibles"
                            name="nb_places_disponibles"
                            min="1"
                            value="<?php
                            echo isset($chauffeurInfo) && is_array($chauffeurInfo) && isset($chauffeurInfo['nb_places_disponibles'])
                                ? htmlspecialchars((string)$chauffeurInfo['nb_places_disponibles'], ENT_QUOTES)
                                : '';
                            ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="preferences">Préférences (facultatif) :</label>
                        <textarea id="preferences" name="preferences"><?= htmlspecialchars($chauffeurInfo['preferences'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fumeur">Accepte fumeur</label>
                        <input type="checkbox" id="fumeur" name="fumeur" <?= $smoker_preference ? 'checked':''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="animal">Accepte animaux</label>
                        <input type="checkbox" id="animal" name="animal" <?= $pet_preference ? 'checked':''; ?>>
                    </div>

                    
                    <button class="button"type="submit">Ajouter</button>
                    
                </form>
            </section>
        <?php endif; ?>

        <!-- Récapitulatif des véhicules -->
<section class="user-vehicles">
  <h2>Mes véhicules</h2>

  <?php if (!$vehicules): ?>
    <p>Aucun véhicule enregistré.</p>
  <?php else: ?>
    <?php foreach ($vehicules as $v): 
      $vid   = (int)$v['id'];
      $smoke = (int)($v['smoker_preference'] ?? 0);
      $pets  = (int)($v['pet_preference'] ?? 0);
      $energie = strtolower((string)($v['energie'] ?? ''));
    ?>
      <article class="vehicule-card" data-veh-id="<?= $vid ?>">
        <header class="vehicule-header">
          <h3>
            <?= htmlspecialchars($v['marque'] . ' ' . $v['modele']) ?>
            <small>(<?= htmlspecialchars($v['plaque_immatriculation']) ?>)</small>
          </h3>
          
        </header>

        <ul class="vehicule-infos">
          <li><strong>Marque/Modèle :</strong> <?= htmlspecialchars($v['marque']) ?> <?= htmlspecialchars($v['modele']) ?></li>
          <li><strong>Plaque :</strong> <?= htmlspecialchars($v['plaque_immatriculation']) ?></li>
          <li><strong>1ère immatriculation :</strong> <?= htmlspecialchars($v['date_1ere_immat']) ?></li>
          <li><strong>Énergie :</strong> <?= htmlspecialchars((string)$v['energie']) ?></li>
          <li><strong>Places dispo :</strong> <?= (int)$v['nb_places_disponibles'] ?></li>
          <li><strong>Fumeur :</strong> <?= $smoke ? 'Oui' : 'Non' ?></li>
          <li><strong>Animaux :</strong> <?= $pets ? 'Acceptés' : 'Non' ?></li>
          <?php if (trim((string)$v['preferences']) !== ''): ?>
            <li><strong>Préférences :</strong> <?= nl2br(htmlspecialchars((string)$v['preferences'])) ?></li>
          <?php endif; ?>
        
        </ul>
    
            <button type="button" class="btn-edit" data-toggle="#edit-veh-<?= $vid ?>">Modifier</button>

            <form action="../backend/handlers/delete_vehicle.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
                <input type="hidden" name="vehicule_id" value="<?= $vid ?>">
                <button type="submit" class="btn-danger">Supprimer</button>
            </form>
    

        <!-- Formulaire d'édition (replié par défaut) -->
        <form id="edit-veh-<?= $vid ?>" class="vehicule-edit-form" action="../backend/handlers/edit_vehicle.php" method="POST" style="display:none;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
          <input type="hidden" name="vehicule_id" value="<?= $vid ?>">

          <div class="form-row">
            <label>Plaque</label>
            <input name="plaque_immatriculation" required
                   pattern="^[A-Z]{2}\s?\d{3}\s?[A-Z]{2}$"
                   title="Format AB 123 CD"
                   value="<?= htmlspecialchars($v['plaque_immatriculation']) ?>">
          </div>

          <div class="form-row">
            <label>Date 1ère immat.</label>
            <input type="date" name="date_1ere_immat" required
                   value="<?= htmlspecialchars($v['date_1ere_immat']) ?>">
          </div>

          <div class="form-row">
            <label>Modèle</label>
            <input name="modele" required value="<?= htmlspecialchars($v['modele']) ?>">
          </div>

          <div class="form-row">
            <label>Marque</label>
            <input name="marque" required value="<?= htmlspecialchars($v['marque']) ?>">
          </div>

          <div class="form-row">
            <label>Énergie</label>
            <select name="energie" required>
              <option value="diesel"     <?= $energie==='diesel'     ? 'selected':''; ?>>Diesel</option>
              <option value="essence"    <?= $energie==='essence'    ? 'selected':''; ?>>Essence</option>
              <option value="hybride"    <?= $energie==='hybride'    ? 'selected':''; ?>>Hybride</option>
              <option value="electrique" <?= $energie==='electrique' ? 'selected':''; ?>>Électrique</option>
            </select>
          </div>

          <div class="form-row">
            <label>Places dispo</label>
            <input type="number" min="1" max="9" name="nb_places_disponibles" required
                   value="<?= (int)$v['nb_places_disponibles'] ?>">
          </div>

          <div class="form-row">
            <label><input type="checkbox" name="smoker_preference" value="1" <?= $smoke ? 'checked':''; ?>> Accepte fumeur</label>
          </div>

          <div class="form-row">
            <label><input type="checkbox" name="pet_preference" value="1" <?= $pets ? 'checked':''; ?>> Accepte animaux</label>
          </div>

          <div class="form-row">
            <label>Préférences (texte libre)</label>
            <textarea name="preferences" rows="3"><?= htmlspecialchars((string)$v['preferences']) ?></textarea>
          </div>

          <div class="form-actions">
            <button type="submit">Enregistrer</button>
            <button type="button" class="btn-light" data-toggle="#edit-veh-<?= $vid ?>">Annuler</button>
          </div>
        </form>
      </article>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

    </div>
</main>

<footer>
        <div class="footer-links">
            <a href="#" id="open-cookie-modal">Gérer mes cookies</a>
            <span>|</span>
            <span>EcoRide@gmail.com</span>
            <span>|</span>
            <a href="legal_notice.php">Mentions légales</a>
        </div>
    </footer>

   <!-- Overlay bloquant -->
  <div id="cookie-blocker" class="cookie-blocker" hidden></div>
    <!-- Bandeau cookies -->
    <div id="cookie-banner" class="cookie-banner" hidden>
    <div class="cookie-content">
        <p>Nous utilisons des cookies pour améliorer votre expérience, mesurer l’audience et proposer des contenus personnalisés.</p>
        <div class="cookie-actions">
        <button data-action="accept-all" type="button">Tout accepter</button>
        <button data-action="reject-all" type="button">Tout refuser</button>
        <button data-action="customize"  type="button">Personnaliser</button>
        </div>
    </div>
    </div>

    <!-- Centre de préférences -->
    <div id="cookie-modal" class="cookie-modal" hidden>
    <div class="cookie-modal-card">
        <h3>Préférences de cookies</h3>
        <label><input type="checkbox" checked disabled> Essentiels (toujours actifs)</label><br>
        <label><input type="checkbox" id="consent-analytics"> Mesure d’audience</label><br>
        <label><input type="checkbox" id="consent-marketing"> Marketing</label>
        <div class="cookie-modal-actions">
        <button data-action="save"  type="button">Enregistrer</button>
        <button data-action="close" type="button">Fermer</button>
        </div>
    </div>
    </div>


 <!-- Pour la mise a jour du statut-->
    <!-- Modale de confirmation -->
    <div id="status-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="modal-title">Confirmer la mise à jour du statut</h2>
            <p id="modal-message">êtes-vous sûr de vouloir mettre à jour votre statut ?</p>
            <div class="modal-actions">
                <button id="modal-confirm" class="btn-confirm">Confirmer</button>
                <button id="modal-cancel" class="btn-cancel">Annuler</button>
            </div>
        </div>
    </div>

    <!-- Modale de succès -->
    <div id="status-success-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="success-modal-close">&times;</span>
            <p id="success-modal-message">Votre statut a été mis à jour avec succès !</p>
        </div>
    </div>

    <!-- Pour l'ajout d'un vehicule-->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>Le véhicule a été ajouté avec succès !</h2>
            <p>Vous pouvez maintenant voir votre véhicule dans votre liste.</p>
            <button id="closeSuccessModal">Fermer</button>
        </div>
    </div>

    <!-- Modale de confirmation pour le voyage -->
    <div id="travel-confirmation-modal" class="modal">
        <div class="modal-content">
            <h2 id="modal-title2">Confirmer la publication du voyage</h2>
            <p id="confirmation-message">Êtes-vous sûr de vouloir soumettre votre voyage ? 2 Credits seront retirés de votre solde</p>
            <button id="modal-travel-confirm" class="btn-confirm">Confirmer</button>
            <button id="modal-travel-cancel"class="btn-cancel">Annuler</button>
        </div>
    </div>

    <!-- Modale de succès pour l'ajout de voyage -->
    <div id="travel-success-modal" class="modal">
        <div class="modal-content">
            <p id="travel-success-message">Votre voyage a été ajouté avec succès !</p>
        </div>
    </div>

    <!-- Modale d'erreur en cas d'échec de l'ajout du voyage -->
    <div id="travel-error-modal" class="modal">
        <div class="modal-content">
            <p id="travel-error-message">Une erreur est survenue lors de l'ajout de votre voyage. Veuillez réessayer.</p>
        </div>
    </div>

    <!-- Modale de confirmation pour l'annulation -->
    <div id="cancel-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn" id="ride-cancel-close">&times;</span>
            <h2>Confirmer l'annulation</h2>
            <p>Êtes-vous sûr de vouloir annuler ce covoiturage ?</p>
            <div class="modal-actions">
            <button id="ride-cancel-confirm" class="btn-confirm">Confirmer</button>
            <button id="ride-cancel-cancel"  class="btn-cancel">Annuler</button>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation pour annulation de réservation -->
    <div id="cancel-reservation-modal" class="modal">
        <div class="modal-content">
            <span class="close-reservation-btn" id="resv-cancel-close">&times;</span>
            <h2>Confirmer l'annulation</h2>
            <p>Êtes-vous sûr de vouloir annuler cette réservation ?</p>
            <div class="modal-actions">
            <button id="resv-cancel-confirm" class="btn-confirm">Confirmer</button>
            <button id="resv-cancel-cancel"  class="btn-cancel">Annuler</button>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation pour suppression -->
<div id="delete-modal" class="modal">
  <div class="modal-content">
    <span class="close-btn" id="delete-close">&times;</span>
    <h2>Confirmer la suppression</h2>
    <p>Cette action est irréversible. Voulez-vous continuer&nbsp;?</p>
    <div class="modal-actions">
      <button id="delete-confirm" class="btn-confirm">Supprimer</button>
      <button id="delete-cancel"  class="btn-cancel">Annuler</button>
    </div>
  </div>
</div>

<script nonce="<?= $nonce ?>">
// Politique Trusted Types minimale
try {
  if (window.trustedTypes && !trustedTypes.defaultPolicy) {
    trustedTypes.createPolicy('default', {
      createHTML: (s) => s,
      createScript: (s) => s,
      createScriptURL: (s) => s
    });
  }
} catch (e) { /* no-op */ }
</script>

<script nonce="<?= $nonce ?>">
document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menu-toggle");
    const mobileMenu = document.getElementById("mobile-menu");
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener("click", () => mobileMenu.classList.toggle("active"));
        document.querySelectorAll("#mobile-menu a").forEach(link =>
            link.addEventListener("click", () => mobileMenu.classList.remove("active"))
        );
    }

  const changeLink = document.getElementById('change-photo-link');
  const formWrap   = document.getElementById('change-photo-form');
  const fileInput  = document.getElementById('photo-input');
  const formEl     = document.getElementById('upload-photo-form');

  if (changeLink && formWrap) {
    const toggle = (e) => {
      if (e) e.preventDefault();
      // bascule la propriété 'hidden' (plus fiable que style.display)
      formWrap.hidden = !formWrap.hidden;

      // accessibilité : mettre Ã  jour aria-expanded
      changeLink.setAttribute('aria-expanded', String(!formWrap.hidden));

      // option pratique : ouvrir le sélecteur de fichier directement
      if (!formWrap.hidden && fileInput) {
        fileInput.click();
      }
    };

    changeLink.addEventListener('click', toggle);
    // clavier : Enter/Espace sur le â€œlien-boutonâ€
    changeLink.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') toggle(e);
    });
  }

  // auto-submit quand un fichier est choisi (optionnel mais pratique)
  if (fileInput && formEl) {
    fileInput.addEventListener('change', () => {
      if (fileInput.files && fileInput.files.length) {
        formEl.submit();
      }
    });
  }
});
</script>

<script nonce="<?= $nonce ?>">
document.addEventListener('click', (e) => {
  const sel = e.target?.dataset?.toggle;
  if (!sel) return;
  const el = document.querySelector(sel);
  if (el) el.style.display = (el.style.display === 'none' || !el.style.display) ? 'block' : 'none';
});
</script>
<script nonce="<?= $nonce ?>">
document.addEventListener('DOMContentLoaded', () => {
  

  // 2) Démarrer / terminer un covoiturage (plus d'onclick inline)
  //    startTrip / endTrip doivent exister (ex: dans start_ride.js)
  document.querySelectorAll('.btn-start-trip[data-ride-id]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = parseInt(btn.getAttribute('data-ride-id'), 10);
      if (!Number.isNaN(id) && typeof window.startTrip === 'function') {
        window.startTrip(id);
      }
    });
  });

  document.querySelectorAll('.btn-end-trip[data-ride-id]').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = parseInt(btn.getAttribute('data-ride-id'), 10);
      if (!Number.isNaN(id) && typeof window.endTrip === 'function') {
        window.endTrip(id);
      }
    });
  });
});
</script>

<!-- Vos fichiers JS -->
<script src="assets/js/start_ride.js" defer></script>
<script src="assets/js/cancel_reservation.js" defer></script>
<script src="assets/js/cancel_ride.js" defer></script>
<script src="assets/js/add_vehicle.js" defer></script>
<script src="assets/js/profile.js" defer></script>
<script src="assets/js/create_ride.js" defer></script>
<script src="assets/js/ride_status.js" defer></script>
<script src="assets/js/home.js" defer></script>
<script src="assets/js/delete_confirm.js" defer></script>
<script src="assets/js/cookie_consent.js" defer></script>
</body>
</html>


