<?php
if (empty($_SERVER['HTTPS']) && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') !== 'https') {
    $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $httpsUrl", true, 301);
    exit();
}


session_start();

// Régénérer l'ID de session à chaque nouvelle connexion pour éviter la fixation de session
session_regenerate_id(true);

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_email']);
$user_credit = 0;

// Récupérer l'URL de la base de données depuis la variable d'environnement JAWSDB_URL
$databaseUrl = getenv('JAWSDB_URL');
$parsedUrl = parse_url($databaseUrl);

// Définir les variables pour la connexion à la base de données
$servername = $parsedUrl['host'];
$username = $parsedUrl['user'];
$password = $parsedUrl['pass'];
$dbname = ltrim($parsedUrl['path'], '/');

// Connexion sécurisée à la base de données avec PDO
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Erreur de connexion : " . $e->getMessage());
    exit("Une erreur est survenue. Veuillez réessayer plus tard.");
}

// Vérifier et valider l'ID de covoiturage
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    exit("ID invalide.");
}
$id = (int)$_GET['id'];

// Récupérer les détails du covoiturage
$stmt = $conn->prepare("SELECT * FROM covoiturages WHERE id = ?");
$stmt->execute([$id]);
$covoiturage = $stmt->fetch();

if (!$covoiturage) {
    exit("Covoiturage non trouvé.");
}

// Récupérer les avis du conducteur
$stmtAvis = $conn->prepare("SELECT u.firstName, u.lastName, ac.commentaire, ac.note, ac.date_avis FROM avis_conducteurs ac JOIN users u ON ac.utilisateur_id = u.id WHERE ac.conducteur_id = ?");
$stmtAvis->execute([$covoiturage['user_id']]);
$avis = $stmtAvis->fetchAll();

// Récupérer les crédits de l'utilisateur si connecté
if ($isLoggedIn) {
    $userEmail = filter_var($_SESSION['user_email'], FILTER_VALIDATE_EMAIL);
    if ($userEmail) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$userEmail]);
        $user = $stmt->fetch();

        if ($user) {
            $stmtCredit = $conn->prepare("SELECT credits FROM users WHERE id = ?");
            $stmtCredit->execute([$user['id']]);
            $creditData = $stmtCredit->fetch();
            $user_credit = $creditData ? $creditData['credits'] : 0;
        }
    }
}

// Gérer le jeton CSRF
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Sécuriser l'URL de redirection
$redirectUrl = htmlspecialchars(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL));
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
        <p><strong>Voyage écologique :</strong> <?= $covoiturage['ecologique'] ? 'Oui' : 'Non' ?></p>
        
        <h3>Véhicule</h3>
        <p><strong>Marque :</strong> <?= htmlspecialchars($covoiturage['marque_voiture']) ?></p>
        <p><strong>Modèle :</strong> <?= htmlspecialchars($covoiturage['modele_voiture']) ?></p>
        <p><strong>Énergie :</strong> <?= htmlspecialchars($covoiturage['energie_voiture']) ?></p>

        <h3>Préférences du conducteur</h3>
        <ul>
            <li>Musique : 🎵 Autorisée</li>
            <li>Animaux : 🐶 Acceptés</li>
            <li>Fumeurs : 🚭 Non-fumeur</li>
        </ul>

        <h3>Avis sur le conducteur</h3>
        
        <?php if (count($avis) > 0): ?>
            <ul>
                <?php foreach ($avis as $commentaire): ?>
                    <li>
                        <strong><?= htmlspecialchars($commentaire['lastName']) ?> :</strong> <?= htmlspecialchars($commentaire['commentaire']) ?>
                        (Note : <?= htmlspecialchars($commentaire['note']) ?>/5) - <?= htmlspecialchars($commentaire['date_avis']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucun avis pour ce conducteur.</p>
        <?php endif; ?>
        
        <?php if (!$isLoggedIn): ?>
            <p><a href="/frontend/connexion.html?redirect=<?= urlencode($_SERVER['REQUEST_URI']); ?>">Connectez-vous</a> pour participer.</p>
        <?php elseif ($covoiturage['places_restantes'] > 0 && $user_credits >= $covoiturage['prix']): ?>
            <button class="participer" id="btnParticiper" data-id="<?= $covoiturage['id'] ?>" data-prix="<?= $covoiturage['prix'] ?>">
                Participer
            </button>
        <?php else: ?>
            <p style="color: red;">Impossible de participer (pas assez de crédits).</p>
        <?php endif; ?>
    </div>

    <!-- Modale 1 - Confirmation du prix -->
    <div id="modalConfirmation1" class="modal">
        <div class="modal-content">
            <h2>Confirmer</h2>
            <p id="modalMessage1"></p>
            <div class="modal-actions">
                <button id="modalConfirm1" class="btn-confirm">Oui</button>
                <button id="modalCancel1" class="btn-cancel">Non</button>
            </div>
        </div>
    </div>

    <!-- Modale 2 - Confirmation finale -->
    <div id="modalConfirmation2" class="modal">
        <div class="modal-content">
            <h2>Confirmer</h2>
            <p>Êtes-vous sûr(e) de vouloir utiliser vos crédits pour ce covoiturage ?</p>
            <div class="modal-actions">
                <button id="modalConfirm2" class="btn-confirm">Oui</button>
                <button id="modalCancel2" class="btn-cancel">Non</button>
            </div>
        </div>
    </div>

    <!-- Modale 3 - Réservation effectuée avec succès -->
    <div id="modalReservationReussie" class="modal">
        <div class="modal-content">
            <h2>Réservation réussie !</h2>
            <p>Vous avez réservé ce covoiturage avec succès. Bon voyage !</p>
            <div class="modal-actions">
                <button id="modalConfirmReservation" class="btn-confirm">OK</button>
            </div>
        </div>
    </div>
    <p>Crédits utilisateur : <?= htmlspecialchars($user_credit) ?></p>
    <p>Prix du trajet : <?= htmlspecialchars($covoiturage['prix']) ?></p>

</main>

<footer>
    <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
</footer>

<script src="/frontend/js/details.js"></script>

</body>
</html>
