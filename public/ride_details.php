<?php
// details.php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session + BASE_URL + getPDO()

$isLoggedIn  = isset($_SESSION['user_id']);
$user_email  = $_SESSION['user_email'] ?? null;
$user_credit = 0;

// CSRF: ne pas régénérer si déjà présent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ID du covoiturage
$rideId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$rideId) {
    http_response_code(400);
    exit('ID invalide.');
}

$pdo = getPDO();

try {
    // Covoiturage
    $stmt = $pdo->prepare('SELECT * FROM covoiturages WHERE id = ?');
    $stmt->execute([$rideId]);
    $covoiturage = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$covoiturage) {
        http_response_code(404);
        exit('Covoiturage non trouvé.');
    }

    // Avis du conducteur
    $stmtAvis = $pdo->prepare("
        SELECT u.firstName, u.lastName, ac.commentaire, ac.note, ac.date_avis
        FROM avis_conducteurs ac
        JOIN users u ON ac.utilisateur_id = u.id
        WHERE ac.conducteur_id = ?
        ORDER BY ac.date_avis DESC
    ");
    $stmtAvis->execute([$covoiturage['user_id']]);
    $avis = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);

    // Préférences du conducteur (table chauffeur_info)
    $stPref = $pdo->prepare("
        SELECT preferences, smoker_preference, pet_preference
        FROM chauffeur_info
        WHERE user_id = ?
        LIMIT 1
    ");
    $stPref->execute([$covoiturage['user_id']]);
    $prefRow    = $stPref->fetch(PDO::FETCH_ASSOC) ?: [];
    $prefText   = trim((string)($prefRow['preferences'] ?? ''));
    $smokerPref = (int)($prefRow['smoker_preference'] ?? 0); // 1 = fumeur ok
    $petPref    = (int)($prefRow['pet_preference'] ?? 0);    // 1 = animaux ok

    // Crédit user
    if ($isLoggedIn && filter_var((string)$user_email, FILTER_VALIDATE_EMAIL)) {
        $st = $pdo->prepare('SELECT credits FROM users WHERE email = ?');
        $st->execute([$user_email]);
        $me = $st->fetch(PDO::FETCH_ASSOC);
        $user_credit = $me ? (int)$me['credits'] : 0;
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Erreur interne.');
}

// helpers d'affichage 
$hDepart  = !empty($covoiturage['heure_depart'])  ? date('H:i', strtotime($covoiturage['heure_depart']))   : '—';
$hArrivee = !empty($covoiturage['heure_arrivee']) ? date('H:i', strtotime($covoiturage['heure_arrivee']))  : '—';
$note     = isset($covoiturage['note']) ? (float)$covoiturage['note'] : 0.0;
$prix     = isset($covoiturage['prix']) ? (float)$covoiturage['prix'] : 0.0;
$places   = isset($covoiturage['places_restantes']) ? (int)$covoiturage['places_restantes'] : 0;
$dateDepart = !empty($covoiturage['date']) ? date('d/m/Y', strtotime($covoiturage['date'])) : '—';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Ecoride - Détails du covoiturage</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Base dynamique -->
  <base href="<?= htmlspecialchars((string)BASE_URL, ENT_QUOTES) ?>">

  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/modern.css">

  <!-- SEO dynamiques -->
  <link rel="canonical" href="<?= htmlspecialchars(current_url(false), ENT_QUOTES) ?>">
  <meta name="description" content="Détails du trajet, préférences du conducteur et avis des passagers.">
  <meta property="og:title" content="Détails du covoiturage">
  <meta property="og:description" content="Consultez les informations du trajet et du conducteur sur EcoRide.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= htmlspecialchars(current_url(true), ENT_QUOTES) ?>">
  <meta property="og:image" content="<?= htmlspecialchars(absolute_from_base('assets/images/cover.jpg'), ENT_QUOTES) ?>">
  <meta property="og:site_name" content="EcoRide">
  <meta property="og:locale" content="fr_FR">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#0ea5e9">
</head>
<body>
<header>
  <div class="header-container">
    <div class="logo"><h1>EcoRide</h1></div>
    <div class="menu-toggle" id="menu-toggle">☰</div>
    <nav id="navbar">
      <ul>
        <li><a href="home.php">Accueil</a></li>
        <li><a href="contact_info.php">Contact</a></li>
        <li><a href="rides.php">Covoiturages</a></li>
        <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
        <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
      </ul>
    </nav>
  </div>

  <nav id="mobile-menu">
    <ul>
      <li><a href="home.php">Accueil</a></li>
      <li><a href="rides.php">Covoiturages</a></li>
      <li><a href="contact_info.php">Contact</a></li>
      <li id="profilButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
      <li id="authButtonMobile"   data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"
          data-user-email="<?= htmlspecialchars((string)$user_email, ENT_QUOTES) ?>"></li>
    </ul>
  </nav>
</header>

<main class="covoit">
  <div class="covoiturage-details">
    <h2>
      <?= htmlspecialchars((string)$covoiturage['conducteur'], ENT_QUOTES) ?>
      — Note : <?= htmlspecialchars((string)$note, ENT_QUOTES) ?>/5
    </h2>
    <p><strong>Date :</strong> <?= htmlspecialchars($dateDepart, ENT_QUOTES) ?></p>
    <p><strong>Départ :</strong> <?= htmlspecialchars((string)$covoiturage['depart'], ENT_QUOTES) ?>
       à <?= htmlspecialchars($hDepart, ENT_QUOTES) ?></p>

    <p><strong>Arrivée :</strong> <?= htmlspecialchars((string)$covoiturage['destination'], ENT_QUOTES) ?>
       à <?= htmlspecialchars($hArrivee, ENT_QUOTES) ?></p>

    <p><strong>Prix :</strong> <?= htmlspecialchars(number_format($prix, 2, ',', ' '), ENT_QUOTES) ?> €</p>
    <p><strong>Places restantes :</strong> <?= (int)$places ?></p>
    <p><strong>Vos crédits :</strong> <?= (int)$user_credit ?></p>

    <h3>Préférences du conducteur</h3>
    <ul>
      <li>Animaux : <?= $petPref ? '🐶 Acceptés' : '🚫 Non autorisés' ?></li>
      <li>Fumeurs : <?= $smokerPref ? '🚬 Autorisé' : '🚭 Non-fumeur' ?></li>
      <li>Préférences : <?= $prefText !== '' ? htmlspecialchars($prefText, ENT_QUOTES) : '—' ?></li>
    </ul>

    <h3>Avis sur le conducteur</h3>
    <?php if ($avis): ?>
      <ul>
        <?php foreach ($avis as $a): ?>
          <li>
            <strong><?= htmlspecialchars((string)$a['lastName'], ENT_QUOTES) ?> :</strong>
            <?= htmlspecialchars((string)$a['commentaire'], ENT_QUOTES) ?>
            (<?= (int)$a['note'] ?>/5)
            — <?= htmlspecialchars(date('d/m/Y', strtotime((string)$a['date_avis'])), ENT_QUOTES) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Aucun avis pour ce conducteur.</p>
    <?php endif; ?>

    <?php if (!$isLoggedIn): ?>
      <p>
        <a href="login.php?redirect=<?= urlencode((string)($_SERVER['REQUEST_URI'] ?? 'home.php')) ?>">
          Connectez-vous
        </a> pour participer.
      </p>
    <?php elseif ($places > 0 && $user_credit >= $prix): ?>
      <button
        id="btnParticiper"
        data-id="<?= (int)$covoiturage['id'] ?>"
        data-prix="<?= htmlspecialchars((string)$prix, ENT_QUOTES) ?>"
        data-token="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
        Participer
      </button>
    <?php else: ?>
      <p style="color:red;">Pas assez de crédits ou aucune place disponible.</p>
    <?php endif; ?>
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


<!-- Modale de confirmation participation -->
<div id="participation-modal" class="modal">
  <div class="modal-content">
    <span class="close-btn" id="participation-close">&times;</span>
    <h2>Confirmer la participation</h2>
    <p id="participation-text">
      Voulez-vous participer à ce covoiturage ?
    </p>
    <div class="modal-actions">
      <button id="participation-confirm" class="btn-confirm">Confirmer</button>
      <button id="participation-cancel"  class="btn-cancel">Annuler</button>
    </div>
  </div>
</div>

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
<!-- JS -->
<script src="assets/js/ride_details.js" defer></script>
<script src="assets/js/home.js" defer></script>
<script src="assets/js/cookie_consent.js" defer></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  const menuToggle = document.getElementById("menu-toggle");
  const mobileMenu = document.getElementById("mobile-menu");
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener("click", () => mobileMenu.classList.toggle("active"));
    document.querySelectorAll("#mobile-menu a").forEach(link =>
      link.addEventListener("click", () => mobileMenu.classList.remove("active"))
    );
  }
});
</script>
</body>
</html>
