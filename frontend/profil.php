<?php
session_start();

// Récupérer l'URL de la base de données depuis la variable d'environnement JAWSDB_URL
$databaseUrl = getenv('JAWSDB_URL');

// Utiliser une expression régulière pour extraire les éléments nécessaires de l'URL
$parsedUrl = parse_url($databaseUrl);

// Définir les variables pour la connexion à la base de données
$servername = $parsedUrl['host'];  // Hôte MySQL
$username = $parsedUrl['user'];  // Nom d'utilisateur MySQL
$password = $parsedUrl['pass'];  // Mot de passe MySQL
$dbname = ltrim($parsedUrl['path'], '/');  // Nom de la base de données (en enlevant le premier "/")

// Connexion à la base de données avec PDO
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}



// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_email']);
$user_email = $_SESSION['user_email'] ?? null;

if ($isLoggedIn) {
    // Récupérer les informations de l'utilisateur
    $stmtUser = $conn->prepare("SELECT id, firstName, lastName, email, photo, credits, status FROM users WHERE email = ?");
    $stmtUser->execute([$user_email]);
    $user = $stmtUser->fetch();

    if (!$user) {
        echo "Utilisateur non trouvé.";
        exit;
    }

  
        // Récupérer le statut du covoiturage depuis la base de données
   
    // Si l'utilisateur a des réservations
if (!empty($reservations)) {
    // Prendre le premier covoiturage de la liste des réservations
    $rideId = $reservations[0]['reservation_id'];  // L'ID de la réservation

    // Récupérer l'ID du covoiturage lié à cette réservation
    $stmt = $conn->prepare("SELECT covoiturage_id FROM reservations WHERE id = ?");
    $stmt->execute([$rideId]);
    $covoiturageId = $stmt->fetchColumn();

    // Si un covoiturage a été trouvé avec cet ID, récupérer son statut
    // Si tu veux récupérer un statut pour un covoiturage spécifique
    if (isset($_GET['ride_id'])) {
        $covoiturageId = $_GET['ride_id']; // Récupère l'ID du covoiturage passé dans l'URL (ex : ?ride_id=1)

        // Vérifier si un covoiturage avec cet ID existe
        $stmt = $conn->prepare("SELECT statut FROM covoiturages WHERE id = ?");
        $stmt->execute([$covoiturageId]);
        $rideStatus = $stmt->fetchColumn(); // Récupère le statut du covoiturage

        if ($rideStatus) {
            // Afficher le statut du covoiturage
            echo "Le statut du covoiturage (ID: $covoiturageId) est : " . $rideStatus;
        } else {
            echo "Aucun covoiturage trouvé avec cet ID.";
        }
    } else {
        echo "Aucun ID de covoiturage spécifié.";
    }

    }


    // Mise à jour du statut si la méthode POST est utilisée
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
        $newStatus = $_POST['status'];
        $stmtUpdateStatus = $conn->prepare("UPDATE users SET status = ? WHERE email = ?");
        $stmtUpdateStatus->execute([$newStatus, $user_email]);
    }

    // Si l'utilisateur est un chauffeur, récupérer ses informations de chauffeur
    if ($user['status'] == 'chauffeur' || $user['status'] == 'passager_chauffeur') {
        $stmtChauffeurInfo = $conn->prepare("SELECT * FROM chauffeur_info WHERE user_id = ?");
        $stmtChauffeurInfo->execute([$user['id']]);
        $chauffeurInfo = $stmtChauffeurInfo->fetch();
    }

    // Récupérer les préférences fumeur et animaux de l'utilisateur
    if ($user['status'] == 'chauffeur' || $user['status'] == 'passager_chauffeur') {
        $stmtPreferences = $conn->prepare("SELECT smoker_preference, pet_preference FROM chauffeur_info WHERE user_id = ?");
        $stmtPreferences->execute([$user['id']]);
        $preferences = $stmtPreferences->fetch();

        // Assigner les préférences à des variables
        $smoker_preference = $preferences['smoker_preference'] ?? 0; // 0 si non défini
        $pet_preference = $preferences['pet_preference'] ?? 0;   // 0 si non défini
    }

    // Récupérer les réservations passées de l'utilisateur
    $stmtReservations = $conn->prepare("
        SELECT 
            r.id AS reservation_id,
            c.depart AS depart,
            c.destination AS destination,
            c.heure_depart AS heure_depart,
            c.date AS date_traject,
            c.conducteur AS chauffeur_name,
            r.statut
        FROM reservations r
        JOIN covoiturages c ON r.covoiturage_id = c.id
        WHERE r.user_id = ?
    ");

    $stmtReservations->execute([$user['id']]);
    $reservations = $stmtReservations->fetchAll();
} else {
    echo "Utilisateur non connecté.";
}

    // Récupérer les covoiturages proposés
    $stmtOfferedRides = $conn->prepare("
        SELECT 
            c.id AS ride_id, 
            c.depart, 
            c.destination, 
            c.heure_depart, 
            c.date AS date_traject, 
            c.prix, 
            c.places_restantes, 
            u.lastName AS conducteur,
            c.statut AS ride_status  
        FROM covoiturages c
        JOIN users u ON c.user_id = u.id
        WHERE c.user_id = ? LIMIT 5
    ");
    $stmtOfferedRides->execute([$user['id']]);
    $offeredRides = $stmtOfferedRides->fetchAll();





?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Utilisateur</title>
    <link rel="stylesheet" href="/frontend/styles.css"> <!-- Ajoute ton fichier CSS ici -->
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>Bienvenue sur votre profil, <?php echo $isLoggedIn ? htmlspecialchars($user['lastName']) : 'Utilisateur'; ?> !</h1>

            </div>
            <nav>
                <ul>
                    <li><a href="/frontend/accueil.php">Accueil</a></li>
                    <li><a href="/frontend/contact-info.php">Contact</a></li>
                    <li><a href="/frontend/Covoiturages.php">Covoiturages</a></li>
                    <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                    <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class=adaptation>
        <div class="container">
            <!-- Informations personnelles -->
            <div class="user-info">
                <h2>Informations personnelles</h2>
                <div class="profil-photo">
                <?php 
                // Vérifier si l'utilisateur a une photo et si le fichier existe
                if (!empty($user['photo']) && file_exists($user['photo'])) {
                    // Afficher la photo de profil avec un lien pour la changer
                    echo '<a href="javascript:void(0);" id="change-photo-link">
                            <img src="' . htmlspecialchars($user['photo']) . '" alt="Photo de profil" class="profile-img">
                        </a>';
                } else {
                    // Afficher l'image par défaut avec un lien pour la changer
                    echo '<a href="javascript:void(0);" id="change-photo-link">
                            <img src="/frontend/images/default-avatar.png" alt="Photo de profil" class="profile-img">
                        </a>';
                }
                ?>
                <!-- Formulaire de téléchargement d'image caché -->
                <div id="change-photo-form" style="display: none;">
                    <form action="/frontend/upload_photo.php" method="POST" enctype="multipart/form-data">
                        <label for="photo">Choisir une nouvelle photo de profil :</label>
                        <input type="file" name="photo" id="photo" accept="image/*" required>
                        <button type="submit">Changer la photo</button>
                    </form>
                </div>
                <div class="info-card">
                
                    <p><strong>Nom :</strong> <?php echo htmlspecialchars($user['firstName']); ?></p>
                    <p><strong>Prénom :</strong> <?php echo htmlspecialchars($user['lastName']); ?></p>
                    <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Crédits :</strong> <?php echo $user['credits']; ?> crédits</p>
                </div>
            </div>

            <!-- Choisir statut (chauffeur, passager ou les deux) -->
            <div class="user-status">
                <h2>Statut : <?php echo htmlspecialchars($user['status']); ?></h2>
                <form id="status-form" method="POST">
                    <label for="status">Choisissez votre statut :</label>
                    <select name="status" id="status">
                        <option value="passager" <?php echo ($user['status'] == 'Passager') ? 'selected' : ''; ?>>Passager</option>
                        <option value="chauffeur" <?php echo ($user['status'] == 'Chauffeur') ? 'selected' : ''; ?>>Chauffeur</option>
                        <option value="passager_chauffeur" <?php echo ($user['status'] == 'Passager_Chauffeur') ? 'selected' : ''; ?>>Passager & Chauffeur</option>
                    </select>
                    <div class=button>
                        <button type="submit">Mettre à jour</button>
                    </div>
                </form>
            </div>

        

            <!-- Réservations passées -->
<div class="user-reservations">
    <h2>Vos réservations passées</h2>
    <?php if (isset($reservations) && count($reservations) > 0): ?>
        <div class="reservations-list">
            <?php foreach ($reservations as $reservation): ?>
                <div class="reservation-item">
                    <h3>Chauffeur : <?php echo htmlspecialchars($reservation['chauffeur_name']); ?></h3>
                    <p><strong>Trajet :</strong> <?php echo htmlspecialchars($reservation['depart']) . " à " . htmlspecialchars($reservation['destination']); ?></p>
                    <p><strong>Heure de départ :</strong> <?php echo htmlspecialchars($reservation['heure_depart']); ?></p>
                    <p><strong>Date du trajet :</strong> <?php echo htmlspecialchars($reservation['date_traject']); ?></p>
                    <p><strong>Status :</strong> <?php echo htmlspecialchars($reservation['statut']); ?></p>
                    
                    <!-- Formulaire d'annulation -->
                    <form action="/frontend/annuler_reservation.php" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                        <!-- Champ caché pour envoyer l'ID de la réservation -->
                        <input type="hidden" name="reservation_id" value="<?php echo htmlspecialchars($reservation['reservation_id']); ?>">
                        <button type="submit" class="btn-danger">Annuler la réservation</button>
                    </form>
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
                <?php if (!empty($offeredRides)): ?>
                            <div class="offered-rides-list">
                                
                                <?php foreach ($offeredRides as $ride):  
                                        $startButtonStyle = ($ride['ride_status'] === 'en attente') ? 'inline-block' : 'none';
                                        $endButtonStyle = ($ride['ride_status'] === 'en cours') ? 'inline-block' : 'none';?>
                                    <div class="offered-ride-item">
                                        <h3>Conducteur : <?php echo htmlspecialchars($ride['conducteur']); ?></h3>
                                        <p><strong>Trajet :</strong> <?php echo htmlspecialchars($ride['depart']) . " à " . htmlspecialchars($ride['destination']); ?></p>
                                        <p><strong>Heure de départ :</strong> <?php echo htmlspecialchars($ride['heure_depart']); ?></p>
                                        <p><strong>Date du trajet :</strong> <?php echo htmlspecialchars($ride['date_traject']); ?></p>
                                        <p><strong>Prix :</strong> <?php echo htmlspecialchars($ride['prix']); ?> crédits</p>
                                        <p><strong>Places restantes :</strong> <?php echo htmlspecialchars($ride['places_restantes']); ?></p>
                                        <form action="/frontend/annuler_covoiturage.php" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce covoiturage ?');">
                                            <button type="submit" class="cancel-ride-button" data-covoiturage-id=<?php echo htmlspecialchars($ride['ride_id']); ?> >Annuler le covoiturage</button>
                                        </form>
                                        

                                        <!-- Bouton "Démarrer le covoiturage" visible uniquement si le statut est "en attente" -->
                                        <button id="start-trip-<?php echo $ride['ride_id']; ?>" class="btn-start-trip" 
                                            onclick="startTrip(<?php echo $ride['ride_id']; ?>)" style="display: <?php echo $startButtonStyle; ?>;">
                                            Démarrer le covoiturage
                                        </button>

                                        <!-- Bouton "Arrivée à destination" visible uniquement si le statut est "en cours" -->
                                        <button id="end-trip-<?php echo $ride['ride_id']; ?>" class="btn-end-trip" 
                                            onclick="endTrip(<?php echo $ride['ride_id']; ?>)" style="display: <?php echo $endButtonStyle; ?>;">
                                            Arrivée à destination
                                        </button>


                                        

                                    </div>
                                    <hr>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>Aucun covoiturage proposé pour le moment.</p>
                        <?php endif; ?>
                    </div>
                        </div>

        
            <div class="container">
            <?php if ($user['status'] == 'chauffeur' || $user['status'] == 'passager_chauffeur'): ?>
                <?php
                // Récupérer les véhicules du chauffeur
                $stmtVéhicules = $conn->prepare("SELECT id, modele, marque FROM chauffeur_info WHERE user_id = ?");
                $stmtVéhicules->execute([$user['id']]);
                $vehicules = $stmtVéhicules->fetchAll();
                if (!$vehicules) {
                    $vehicules = [];
                }
                $stmtVilles = $conn->query("SELECT nom FROM villes");
                $villes = $stmtVilles->fetchAll(PDO::FETCH_COLUMN);

                ?>
                <div class="saisir-voyage">
                    <h2>Proposer un covoiturage</h2>
                    <form id="voyageForm" method="POST" action="/frontend/ajoutCovoiturages.php">
                        <div class="form-group">
                            <label for="depart">Adresse de depart :</label>
                            <input list="cities" id="depart" placeholder="Départ" name="depart" required><br>
                        </div>
                        <div class="form-group">
                            <label for="destination">Adresse d'arrivee :</label>
                            <input list="cities" id="destination" placeholder="Destination" name="destination" required><br>
                        </div>
                        <div class="form-group">
                            <label for="places_restantes">Place restantes:</label>
                            <input id="places_restantes"  name="places_restantes" required><br>
                        </div>
                        <div class="form-group">
                            <label for="date">Date:</label>
                            <input type="date" id="date" name="date" required><br>
                        </div>
                        <div class="form-group">
                            <label for="heure_depart">Heure de depart:</label>
                            <input  id="heure_depart" type="time" placeholder="HH:MM" name="heure_depart" required><br>
                        </div>
                        <div class="form-group">
                            <label for="duree">Durée du trajet:</label>
                            <input  id="duree" type="time" placeholder="HH:MM" name="duree" required><br>
                        </div>
                        <div class="form-group">
                            <label for="prix">Prix (en crédits) :</label>
                            <input type="number" id="prix" name="prix" placeholder="Prix du voyage" required>
                        </div>
                        <div class="form-group">
                            <label for="vehicule">Sélectionnez un véhicule :</label>
                            <select name="vehicule_id" id="vehicule" required>
                                <?php foreach ($vehicules as $vehicule): ?>
                                    <option value="<?php echo $vehicule['id']; ?>"><?php echo htmlspecialchars($vehicule['modele']) . ' ' . htmlspecialchars($vehicule['marque']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="button">
                            <button type="submit" id="saisirVoyageButton">Saisir le covoiturage</button>
                        </div>
                    </form>
                    <datalist id="cities">
                        <?php foreach ($villes as $ville) : ?>
                            <option value="<?= htmlspecialchars($ville) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
            <?php endif; ?>
        </div>


         <!-- Informations spécifiques chauffeur -->
         <?php if ($user['status'] == 'chauffeur' || $user['status'] == 'passager_chauffeur'): ?>
            <section class="chauffeur-info">
                <h2>Informations du véhicule</h2>
                <form id="vehicleForm" method="POST">
                    <div class="form-group">
                        <label for="plaque_immatriculation">Plaque d'immatriculation :</label>
                        <input title="Format attendu : AB 123 CD (2 lettres, 3 chiffres, 2 lettres)" pattern="^[A-Z]{2}\s?\d{3}\s?[A-Z]{2}$" placeholder="AB 123 CD" type="text" id="plaque_immatriculation" name="plaque_immatriculation" value="<?php echo htmlspecialchars($chauffeurInfo['plaque_immatriculation'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="date_1ere_immat">Date de première immatriculation :</label>
                        <input type="date" id="date_1ere_immat" name="date_1ere_immat" value="<?php echo htmlspecialchars($chauffeurInfo['date_1ere_immat'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="modele">Modèle :</label>
                        <input type="text" id="modele" name="modele" value="<?php echo htmlspecialchars($chauffeurInfo['modele'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="marque">Marque :</label>
                        <input type="text" id="marque" name="marque" value="<?php echo htmlspecialchars($chauffeurInfo['marque'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="energie">Energie :</label>
                        <select id="energie" name="energie" required>
                            <option value="diesel" <?php echo (isset($chauffeurInfo['energie']) && $chauffeurInfo['energie'] == 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
                            <option value="essence" <?php echo (isset($chauffeurInfo['energie']) && $chauffeurInfo['energie'] == 'Éssence') ? 'selected' : ''; ?>>Essence</option>
                            <option value="hybride" <?php echo (isset($chauffeurInfo['energie']) && $chauffeurInfo['energie'] == 'Hybride') ? 'selected' : ''; ?>>Hybride</option>
                            <option value="electrique" <?php echo (isset($chauffeurInfo['energie']) && $chauffeurInfo['energie'] == 'Électrique') ? 'selected' : ''; ?>>Électrique</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="nb_places">Nombre de places disponibles :</label>
                        <input type="number" id="nb_places" name="nb_places_disponibles" value="<?php echo htmlspecialchars($chauffeurInfo['nb_places_disponibles'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="preferences">Préférences (facultatif) :</label>
                        <textarea id="preferences" name="preferences"><?php echo htmlspecialchars($chauffeurInfo['preferences'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="fumeur">Accepte fumeur ?</label>
                        <input type="checkbox" id="fumeur" name="fumeur" <?php echo ($smoker_preference == 1) ? 'checked' : ''; ?>>
                    </div>
                    <div class="form-group">
                        <label for="animal">Accepte animaux ?</label>
                        <input type="checkbox" id="animal" name="animal" <?php echo ($pet_preference == 1) ? 'checked' : ''; ?>>
                    </div>
                    <div class="button">
                        <button  type="submit">Ajouter un véhicule</button>
                    </div>
                </form>
            </section>
        <?php endif; ?>

    </main>

    <footer>
        <p>EcoRide@gmail.com / <a href="/frontend/mentions_legales.php">Mentions légales</a></p>
    </footer>

    <!-- Pour la mise a jour du statut-->
    <!-- Modale de confirmation -->
    <div id="status-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="modal-title">Confirmer la mise à jour du statut</h2>
            <p id="modal-message">Êtes-vous sûr de vouloir mettre à jour votre statut ?</p>
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
            <p id="confirmation-message">Êtes-vous sûr de vouloir soumettre votre voyage ?<br> 2 Credits seront retirés de votre solde</p>
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
            <span class="close-btn">&times;</span>
            <h2>Confirmer l'annulation</h2>
            <p>Êtes-vous sûr de vouloir annuler ce covoiturage ?</p>
            <div class="modal-actions">
                <button id="modal-cancel-confirm" class="btn-confirm">Confirmer</button>
                <button id="modal-cancel-cancel" class="btn-cancel">Annuler</button>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation pour annulation de réservation -->
    <div id="cancel-reservation-modal" class="modal">
        <div class="modal-content">
            <span class="close-reservation-btn">&times;</span>
            <h2>Confirmer l'annulation</h2>
            <p>Êtes-vous sûr de vouloir annuler cette réservation ?</p>
            <button id="modal-cancel-confirm" class="btn-confirm">Confirmer</button>
            <button id="modal-cancel-cancel" class="btn-cancel">Annuler</button>
        </div>
    </div>
    <script>
        // Lorsque l'utilisateur clique sur l'image de profil
        document.getElementById('change-photo-link').addEventListener('click', function() {
            // Afficher le formulaire de changement de photo
            document.getElementById('change-photo-form').style.display = 'block';
        });
    </script>
    <script src="/frontend/js/demarrerCovoiturages.js" defer></script>
    <script src="/frontend/js/annulerReservation.js"></script>
    <script src="/frontend/js/annulerCovoiturage.js"></script> 
    <script src="/frontend/js/ajoutVehicle.js"></script>
    <script src="/frontend/js/profil.js"></script>
    <script src="/frontend/js/ajoutCovoiturages.js"></script>
    <script src="/frontend/js/status.js"></script>

</body>
</html>