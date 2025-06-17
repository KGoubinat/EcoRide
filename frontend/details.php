<?php
if (empty($_SERVER['HTTPS']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit();
}

session_start();
session_regenerate_id(true);

$isLoggedIn = isset($_SESSION['user_email']);
$user_credit = 0;
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Connexion à la base de données
$parsedUrl = parse_url(getenv('JAWSDB_URL'));
$servername = $parsedUrl['host'];
$username = $parsedUrl['user'];
$password = $parsedUrl['pass'];
$dbname = ltrim($parsedUrl['path'], '/');

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    exit("Erreur interne.");
}

// Validation ID
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    exit("ID invalide.");
}
$id = (int)$_GET['id'];

// Covoiturage
$stmt = $conn->prepare("SELECT * FROM covoiturages WHERE id = ?");
$stmt->execute([$id]);
$covoiturage = $stmt->fetch();
if (!$covoiturage) {
    exit("Covoiturage non trouvé.");
}

// Avis
$stmtAvis = $conn->prepare("SELECT u.firstName, u.lastName, ac.commentaire, ac.note, ac.date_avis FROM avis_conducteurs ac JOIN users u ON ac.utilisateur_id = u.id WHERE ac.conducteur_id = ?");
$stmtAvis->execute([$covoiturage['user_id']]);
$avis = $stmtAvis->fetchAll();

// Crédit utilisateur
if ($isLoggedIn && filter_var($_SESSION['user_email'], FILTER_VALIDATE_EMAIL)) {
    $stmt = $conn->prepare("SELECT id, credits FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['user_email']]);
    $user = $stmt->fetch();
    $user_credit = $user ? $user['credits'] : 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du covoiturage</title>
    <link rel="stylesheet" href="/frontend/styles.css">
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <h1>Détail du covoiturage</h1>
        </div>
        <div class="menu-toggle" id="menu-toggle">☰</div>
        <nav id="navbar">
            <ul>
                <li><a href="/frontend/accueil.php">Accueil</a></li>
                <li><a href="/frontend/contact_info.php">Contact</a></li>
                <li><a href="/frontend/covoiturages.php">Covoiturages</a></li>
                <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>" data-user-email="<?= $_SESSION['user_email'] ?? ''; ?>"></li>
            </ul>
        </nav>
    </div>
</header>

<main class="covoit">
    <div class="covoiturage-details">
        <h2><?= htmlspecialchars($covoiturage['conducteur']) ?> - Note : <?= htmlspecialchars($covoiturage['note']) ?>/5</h2>
        <p><strong>Départ :</strong> <?= htmlspecialchars($covoiturage['depart']) ?> à <?= htmlspecialchars($covoiturage['heure_depart']) ?></p>
        <p><strong>Arrivée :</strong> <?= htmlspecialchars($covoiturage['destination']) ?> à <?= htmlspecialchars($covoiturage['heure_arrivee']) ?></p>
        <p><strong>Prix :</strong> <?= htmlspecialchars($covoiturage['prix']) ?>€</p>
        <p><strong>Places restantes :</strong> <?= htmlspecialchars($covoiturage['places_restantes']) ?></p>
        <p><strong>Crédits disponibles :</strong> <?= $user_credit ?> €</p>

        <h3>Véhicule</h3>
        <p><strong>Marque :</strong> <?= htmlspecialchars($covoiturage['marque_voiture']) ?></p>
        <p><strong>Modèle :</strong> <?= htmlspecialchars($covoiturage['modele_voiture']) ?></p>
        <p><strong>Énergie :</strong> <?= htmlspecialchars($covoiturage['energie_voiture']) ?></p>

        <h3>Préférences du conducteur</h3>
        <ul>
            <li>Musique : <?= $covoiturage['musique'] ? '🎵 Autorisée' : '🚫 Interdite' ?></li>
            <li>Animaux : <?= $covoiturage['animaux'] ? '🐶 Acceptés' : '🚫 Non autorisés' ?></li>
            <li>Fumeurs : <?= $covoiturage['fumeurs'] ? '🚬 Autorisé' : '🚭 Non-fumeur' ?></li>
        </ul>

        <h3>Avis sur le conducteur</h3>
        <?php if ($avis): ?>
            <ul>
                <?php foreach ($avis as $a): ?>
                    <li>
                        <strong><?= htmlspecialchars($a['lastName']) ?> :</strong> <?= htmlspecialchars($a['commentaire']) ?>
                        (<?= $a['note'] ?>/5) - <?= $a['date_avis'] ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucun avis pour ce conducteur.</p>
        <?php endif; ?>

        <?php if (!$isLoggedIn): ?>
            <p><a href="/frontend/connexion.html?redirect=<?= urlencode($_SERVER['REQUEST_URI']); ?>">Connectez-vous</a> pour participer.</p>
        <?php elseif ($covoiturage['places_restantes'] > 0 && (float)$user_credit >= (float)$covoiturage['prix']): ?>
            <button class="participer" id="btnParticiper"
                data-id="<?= $covoiturage['id'] ?>"
                data-prix="<?= $covoiturage['prix'] ?>"
                data-token="<?= $csrf_token ?>">
                Participer
            </button>
        <?php else: ?>
            <p style="color: red;">Vous n’avez pas assez de crédits ou aucune place n’est disponible.</p>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
</footer>
    <!-- Modale 1 : Confirmation du prix -->
    <div id="modalConfirmation1" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:5px; max-width:400px; text-align:center;">
        <p id="modalMessage1"></p>
        <button id="modalConfirm1">Oui</button>
        <button id="modalCancel1">Non</button>
    </div>
    </div>

    <!-- Modale 2 : Confirmation participation -->
    <div id="modalConfirmation2" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:5px; max-width:400px; text-align:center;">
        <p>Voulez-vous confirmer votre participation ?</p>
        <button id="modalConfirm2">Oui</button>
        <button id="modalCancel2">Non</button>
    </div>
    </div>

    <!-- Modale réservation réussie -->
    <div id="modalReservationReussie" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:5px; max-width:400px; text-align:center;">
        <p>Réservation effectuée avec succès.</p>
        <button id="modalConfirmReservation">OK</button>
    </div>
</div>
<script src="/frontend/js/details.js"></script>
</body>
</html>
