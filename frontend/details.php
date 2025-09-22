<?php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session + BASE_URL + getPDO()

$isLoggedIn  = isset($_SESSION['user_email']);
$user_email  = $_SESSION['user_email'] ?? null;
$user_credit = 0;

// CSRF
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// ID
$rideId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$rideId) {
    exit('ID invalide.');
}

$pdo = getPDO();

try {
    // Covoiturage
    $stmt = $pdo->prepare('SELECT * FROM covoiturages WHERE id = ?');
    $stmt->execute([$rideId]);
    $covoiturage = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$covoiturage) {
        exit('Covoiturage non trouvé.');
    }

    // Avis
    $stmtAvis = $pdo->prepare("
        SELECT u.firstName, u.lastName, ac.commentaire, ac.note, ac.date_avis
        FROM avis_conducteurs ac
        JOIN users u ON ac.utilisateur_id = u.id
        WHERE ac.conducteur_id = ?
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
    $prefRow = $stPref->fetch(PDO::FETCH_ASSOC) ?: [];
    $prefText   = trim((string)($prefRow['preferences'] ?? ''));
    $smokerPref = (int)($prefRow['smoker_preference'] ?? 0); // 1 = fumeur ok
    $petPref    = (int)($prefRow['pet_preference'] ?? 0);    // 1 = animaux ok
    

    // Crédit user
    if ($isLoggedIn && filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $st = $pdo->prepare('SELECT credits FROM users WHERE email = ?');
        $st->execute([$user_email]);
        $me = $st->fetch(PDO::FETCH_ASSOC);
        $user_credit = $me ? (int)$me['credits'] : 0;
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    exit('Erreur interne.');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du covoiturage</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES) ?>">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo"><h1>EcoRide</h1></div>
        <div class="menu-toggle" id="menu-toggle">☰</div>
        <nav id="navbar">
            <ul>
                <li><a href="accueil.php">Accueil</a></li>
                <li><a href="contact_info.php">Contact</a></li>
                <li><a href="covoiturages.php">Covoiturages</a></li>
                <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
            </ul>
        </nav>
    </div>

    <nav id="mobile-menu">
        <ul>
            <li><a href="accueil.php">Accueil</a></li>
            <li><a href="covoiturages.php">Covoiturages</a></li>
            <li><a href="contact_info.php">Contact</a></li>
            <li id="profilButtonMobile"
                data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"></li>
            <li id="authButtonMobile"
                data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"
                data-user-email="<?= htmlspecialchars((string)$user_email, ENT_QUOTES) ?>"></li>
        </ul>
    </nav>
</header>

<main class="covoit">
    <div class="covoiturage-details">
        <h2><?= htmlspecialchars($covoiturage['conducteur']) ?> - Note :
            <?= htmlspecialchars((string)$covoiturage['note']) ?>/5</h2>

        <p><strong>Départ :</strong> <?= htmlspecialchars($covoiturage['depart']) ?>
           à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?></p>

        <p><strong>Arrivée :</strong> <?= htmlspecialchars($covoiturage['destination']) ?>
           à <?= date('H:i', strtotime($covoiturage['heure_arrivee'])) ?></p>

        <p><strong>Prix :</strong> <?= htmlspecialchars((string)$covoiturage['prix']) ?> €</p>
        <p><strong>Places restantes :</strong> <?= (int)$covoiturage['places_restantes'] ?></p>
        <p><strong>Crédits disponibles :</strong> <?= htmlspecialchars((string)$user_credit) ?> €</p>

        <h3>Préférences du conducteur</h3>
            <ul>
                <li>Animaux : <?= $petPref ? '🐶 Acceptés' : '🚫 Non autorisés' ?></li>
                <li>Fumeurs : <?= $smokerPref ? '🚬 Autorisé' : '🚭 Non-fumeur' ?></li>
                <li>Préférences : <?= $prefText !== '' ? htmlspecialchars($prefText) : '—' ?></li>
            </ul>

        <h3>Avis sur le conducteur</h3>
        <?php if ($avis): ?>
            <ul>
                <?php foreach ($avis as $a): ?>
                    <li><strong><?= htmlspecialchars($a['lastName']) ?> :</strong>
                        <?= htmlspecialchars($a['commentaire']) ?>
                        (<?= (int)$a['note'] ?>/5) - <?= htmlspecialchars($a['date_avis']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucun avis pour ce conducteur.</p>
        <?php endif; ?>

        <?php if (!$isLoggedIn): ?>
            <p><a href="connexion.html?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>">Connectez-vous</a> pour participer.</p>
        <?php elseif ((int)$covoiturage['places_restantes'] > 0 && $user_credit >= (float)$covoiturage['prix']): ?>
            <button id="btnParticiper"
                    data-id="<?= (int)$covoiturage['id'] ?>"
                    data-prix="<?= (float)$covoiturage['prix'] ?>"
                    data-token="<?= htmlspecialchars($csrf_token, ENT_QUOTES) ?>">
                Participer
            </button>
        <?php else: ?>
            <p style="color:red;">Pas assez de crédits ou aucune place dispo.</p>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>

<script src="js/details.js" defer></script>
<script src="js/accueil.js" defer></script>
</body>
</html>
