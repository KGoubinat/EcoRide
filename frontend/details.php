<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['user_email'])) {
    echo "Utilisateur connecté : " . $_SESSION['user_email'];
} else {
    echo "Aucun utilisateur connecté.";
}

$isLoggedIn = isset($_SESSION['user_email']);
echo "Is user logged in? " . ($isLoggedIn ? "true" : "false");

$user_credit = 0;
$dsn = 'mysql:host=localhost;dbname=ecoride';
$username = 'root';
$password = 'nouveau_mot_de_passe';

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);   
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifier si un ID de covoiturage est passé dans l'URL
$id = isset($_GET['id']) ? intval($_GET['id']) : null;  // Vérification de l'existence de l'ID
if ($id === null) {
    echo "Aucun ID de covoiturage fourni.";
    exit;
}

// Récupérer les détails du covoiturage
$stmt = $pdo->prepare("SELECT * FROM covoiturages WHERE id = ?");
$stmt->execute([$id]);
$covoiturage = $stmt->fetch();

if (!$covoiturage) {
    die("Covoiturage non trouvé.");
}

// Récupérer les avis du conducteur
$stmtAvis = $pdo->prepare("
    SELECT 
        u.firstName, 
        u.lastName, 
        ac.commentaire, 
        ac.note, 
        ac.date_avis
    FROM avis_conducteurs ac
    JOIN users u ON ac.utilisateur_id = u.id
    WHERE ac.conducteur_id = ?
");
$stmtAvis->execute([$covoiturage['user_id']]);
$avis = $stmtAvis->fetchAll();

$isLoggedIn = isset($_SESSION['user_email']);
$users_credit = 0; // Initialisation de la variable $users_credit

if ($isLoggedIn) {
    // Récupérer l'ID de l'utilisateur
    $userEmail = $_SESSION['user_email'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$userEmail]);
    $user = $stmt->fetch();

    if ($user) {
        // Récupérer les crédits de l'utilisateur
        $stmtCredit = $pdo->prepare("SELECT credit FROM users_credit WHERE user_id = ?");
        $stmtCredit->execute([$user['id']]);
        $creditData = $stmtCredit->fetch();
        $users_credit = $creditData ? $creditData['credit'] : 0; // Si aucun crédit trouvé, définir $users_credit à 0
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du covoiturage</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
    <div class="header-container">
        <div class="logo">
            <h1>Détail du covoiturage</h1>
        </div>
        <nav>
            <ul>
                <li><a href="accueil.php">Accueil</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="Covoiturages.php">Covoiturages</a></li>
                <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>" data-user-email="<?= isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''; ?>"></li>

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
            <p><a href="connexion.html?redirect=<?= urlencode($_SERVER['REQUEST_URI']); ?>">Connectez-vous</a> pour participer.</p>
        <?php elseif ($covoiturage['places_restantes'] > 0 && $users_credit >= $covoiturage['prix']): ?>
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

    


</main>

<footer>
    <p>EcoRide@gmail.com / <a href="#">Mentions légales</a></p>
</footer>
<script src="js/details.js"></script>
</body>
</html>
