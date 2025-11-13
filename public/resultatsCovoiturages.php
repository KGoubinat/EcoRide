<?php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session + BASE_URL + getPDO() + $pdo

$isLoggedIn = !empty($_SESSION['user_email'] ?? null);

// Liste simple de villes FR (optionnel, pour le select)
$villesFrance = [
    "Paris","Marseille","Lyon","Toulouse","Nice","Nantes","Strasbourg","Montpellier","Bordeaux",
    "Lille","Rennes","Reims","Le Havre","Saint-Étienne","Toulon","Grenoble","Dijon","Angers","Nîmes","Villeurbanne"
];

// GET + nettoyage
$start      = isset($_GET['start'])      ? trim((string)$_GET['start']) : '';
$end        = isset($_GET['end'])        ? trim((string)$_GET['end'])   : '';
$passengers = isset($_GET['passengers']) ? (int)$_GET['passengers']     : 1;
$date       = isset($_GET['date'])       ? trim((string)$_GET['date'])  : '';
$ecolo      = isset($_GET['ecolo'])      ? trim((string)$_GET['ecolo']) : '';
$prix       = isset($_GET['prix'])       ? trim((string)$_GET['prix'])  : '';
$duree      = isset($_GET['duree'])      ? trim((string)$_GET['duree']) : '';
$note       = isset($_GET['note'])       ? trim((string)$_GET['note'])  : '';

// Requête dynamique
$sql   = "SELECT * FROM covoiturages WHERE 1=1";
$conds = [];
$args  = [];

// Départ / Destination (LIKE)
if ($start !== '') {
    $conds[] = "depart LIKE ?";
    $args[]  = "%{$start}%";
}
if ($end !== '') {
    $conds[] = "destination LIKE ?";
    $args[]  = "%{$end}%";
}

// Date exacte
if ($date !== '') {
    $conds[] = "`date` = ?";
    $args[]  = $date;
}

// Passagers mini
if ($passengers > 0) {
    $conds[] = "places_restantes >= ?";
    $args[]  = $passengers;
}

// Écologie
if ($ecolo === 'oui') {
    $conds[] = "ecologique = 1";
} elseif ($ecolo === 'non') {
    $conds[] = "ecologique = 0";
}

// Prix max
if ($prix !== '' && is_numeric($prix)) {
    $conds[] = "prix <= ?";
    $args[]  = (float)$prix;
}

// Durée max (minutes -> comparer en secondes)
if ($duree !== '' && ctype_digit($duree)) {
    $conds[] = "TIME_TO_SEC(duree) <= ?";
    $args[]  = (int)$duree * 60;
}

// Note min
if ($note !== '' && is_numeric($note)) {
    $conds[] = "note >= ?";
    $args[]  = (float)$note;
}

// Optionnel : n'afficher que les trajets à venir
$conds[] = "(`date` > CURDATE() OR (`date` = CURDATE() AND `heure_depart` >= CURTIME()))";

// Assemblage final
if ($conds) {
    $sql .= " AND " . implode(" AND ", $conds);
}
$sql .= " ORDER BY `date` ASC, `heure_depart` ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$covoiturages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Suggestion si aucun résultat
$suggestedRide = null;
if (!$covoiturages) {
    $sqlS = "SELECT * FROM covoiturages
             WHERE depart LIKE ? AND destination LIKE ?
               AND places_restantes >= ?
               AND (`date` > CURDATE() OR (`date` = CURDATE() AND `heure_depart` >= CURTIME()))
             ORDER BY `date` ASC, `heure_depart` ASC
             LIMIT 1";
    $stS = $pdo->prepare($sqlS);
    $stS->execute(["%{$start}%", "%{$end}%", max(1, $passengers)]);
    $suggestedRide = $stS->fetch(PDO::FETCH_ASSOC);
}

$otherRides = [];
if (!$covoiturages && $suggestedRide) {
    $sqlO = "SELECT * FROM covoiturages
             WHERE depart LIKE ? AND destination LIKE ?
               AND date = ?
               AND places_restantes >= ?
               AND (`date` > CURDATE() OR (`date` = CURDATE() AND `heure_depart` >= CURTIME()))
             ORDER BY heure_depart ASC";
    $stO = $pdo->prepare($sqlO);
    $stO->execute([
        "%{$start}%",
        "%{$end}%",
        $suggestedRide['date'], // même jour que le suggéré
        max(1, $passengers)
    ]);
    $otherRides = $stO->fetchAll(PDO::FETCH_ASSOC);
}

// Helper: photo conducteur
function driverPhoto(PDO $pdo, array $row): string {
    $driverKey = array_key_exists('conducteur_id', $row) ? 'conducteur_id' : 'user_id';
    $driverId  = $row[$driverKey] ?? null;
    if (!$driverId) return "assets/images/default-avatar.png";

    $st = $pdo->prepare("SELECT photo FROM users WHERE id = ?");
    $st->execute([$driverId]);
    $photo = $st->fetchColumn();
    if (!empty($photo)) {
        return $photo;
    }
    return "assets/images/default-avatar.png";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>EcoRide - Résultats</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?= htmlspecialchars(rtrim((string)BASE_URL, '/').'/', ENT_QUOTES) ?>">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/modern.css">
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
                <li id="authButton"   data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
            </ul>
        </nav>
    </div>
    <nav id="mobile-menu">
        <ul>
            <li><a href="accueil.php">Accueil</a></li>
            <li><a href="covoiturages.php">Covoiturages</a></li>
            <li><a href="contact_info.php">Contact</a></li>
            <li id="profilButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
            <li id="authButtonMobile"   data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
        </ul>
    </nav>
</header>

<main class="adaptation">
    <div class="card">
        <div class="card-body-filters">
            <h2>FILTRES</h2>

            <div class="filters">
                <label>Voiture écologique :</label>
                <input type="radio" id="ecolo-oui" name="ecolo" value="oui" onchange="applyFilters()" <?= $ecolo==='oui'?'checked':''; ?>>
                <label for="ecolo-oui">Oui</label>
                <input type="radio" id="ecolo-non" name="ecolo" value="non" onchange="applyFilters()" <?= $ecolo==='non'?'checked':''; ?>>
                <label for="ecolo-non">Non</label>
            </div>

            <div class="filters">
                <label for="prix">Prix maximum :</label>
                <select id="prix" name="prix" onchange="applyFilters()">
                    <option value="">-- Sélectionnez une tranche --</option>
                    <?php foreach (['10','20','30','50','100'] as $p): ?>
                        <option value="<?= $p ?>" <?= ($prix===$p)?'selected':''; ?>><?= $p ?>€ ou moins</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filters">
                <label for="duree">Durée du voyage :</label>
                <select id="duree" name="duree" onchange="applyFilters()">
                    <option value="">-- Sélectionnez une durée --</option>
                    <?php foreach (['30'=>'30 minutes','60'=>'1 heure','120'=>'2 heures','180'=>'3 heures','240'=>'4 heures'] as $m=>$label): ?>
                        <option value="<?= $m ?>" <?= ($duree===(string)$m)?'selected':''; ?>><?= $label ?> ou moins</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filters">
                <label for="note">Note minimale :</label>
                <select id="note" name="note" onchange="applyFilters()">
                    <option value="">-- Sélectionnez une note --</option>
                    <?php for ($n=1;$n<=5;$n++): ?>
                        <option value="<?= $n ?>" <?= ($note===(string)$n)?'selected':''; ?>><?= $n ?> étoile<?= $n>1?'s':''; ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filters">
                <label for="depart">Départ :</label>
                <select id="depart" name="start" onchange="applyFilters()">
                    <option value="">-- Sélectionnez une ville de départ --</option>
                    <?php foreach ($villesFrance as $ville): ?>
                        <option value="<?= htmlspecialchars($ville) ?>" <?= $start===$ville?'selected':''; ?>>
                            <?= htmlspecialchars($ville) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filters">
                <label for="arrivee">Arrivée :</label>
                <select id="arrivee" name="end" onchange="applyFilters()">
                    <option value="">-- Sélectionnez une ville d'arrivée --</option>
                    <?php foreach ($villesFrance as $ville): ?>
                        <option value="<?= htmlspecialchars($ville) ?>" <?= $end===$ville?'selected':''; ?>>
                            <?= htmlspecialchars($ville) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filters">
                <label for="passengers">Passagers :</label>
                <select id="passengers" name="passengers" onchange="applyFilters()">
                    <option value="">-- Sélectionnez le nombre de passagers --</option>
                    <?php for ($i=1;$i<=5;$i++): ?>
                        <option value="<?= $i ?>" <?= ($passengers===$i)?'selected':''; ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="filters">
                <label for="date">Date du voyage :</label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="applyFilters()">
            </div>
        </div>
    </div>

    <!-- Résultats -->
    <?php if ($covoiturages): ?>
        <?php foreach ($covoiturages as $covoiturage): ?>
            <div class="card">
                <div class="card-header">
                    <?php
                        $imgSrc = driverPhoto($pdo, $covoiturage);
                        $driverName = $covoiturage['conducteur'] ?? 'Conducteur';
                    ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Photo de <?= htmlspecialchars($driverName) ?>">
                </div>
                <div class="card-body">
                    <h2><?= htmlspecialchars($driverName) ?> <span><?= htmlspecialchars((string)$covoiturage['note']) ?>/5</span></h2>
                    <p>
                        Départ : <?= htmlspecialchars($covoiturage['depart']) ?><br>
                        Arrivée : <?= htmlspecialchars($covoiturage['destination']) ?><br>
                        Places restantes : <?= (int)$covoiturage['places_restantes'] ?><br>
                        Tarif : <?= htmlspecialchars((string)$covoiturage['prix']) ?>€<br>
                        Le : <?= date('d/m/Y', strtotime($covoiturage['date'])) ?>
                        à <?= date('H:i', strtotime($covoiturage['heure_depart'])) ?><br>
                        Durée du trajet :
                        <?php
                            // format HH:MM:SS -> Hh mm
                            $t = explode(':', (string)$covoiturage['duree'] ?: '0:0:0');
                            $h = (int)($t[0] ?? 0);
                            $m = (int)($t[1] ?? 0);
                            echo sprintf('%dh %02dm', $h, $m);
                        ?><br>
                        Voyage écologique : <?= !empty($covoiturage['ecologique']) ? 'Oui' : 'Non' ?>
                    </p>
                    <a
                        href="details.php?id=<?= (int)$covoiturage['id'] ?>
                            &start=<?= urlencode($start) ?>&end=<?= urlencode($end) ?>
                            &date=<?= urlencode($date) ?>&passengers=<?= urlencode((string)$passengers) ?>
                            &ecolo=<?= urlencode($ecolo) ?>&prix=<?= urlencode($prix) ?>
                            &duree=<?= urlencode($duree) ?>&note=<?= urlencode($note) ?>"
                        class="btn-detail">+ d'informations</a>
                </div>
            </div>
        <?php endforeach; ?>

    <?php elseif ($suggestedRide): ?>
        <div class="suggestion">
            <p>Aucun covoiturage trouvé avec ces critères.</p>
            <p>
                Premier itinéraire le plus proche le
                <strong><?= htmlspecialchars((new DateTime($suggestedRide['date']))->format('d/m/Y')) ?></strong>
                à <?= date('H:i', strtotime($suggestedRide['heure_depart'])) ?>
            </p>
            <?php
                $sq = http_build_query([
                    'start' => $start,
                    'end'   => $end,
                    'date'  => $suggestedRide['date'],
                ]);
            ?>
            <button onclick="location.href='resultatsCovoiturages.php?<?= htmlspecialchars($sq) ?>'" class="button">Voir ce trajet&nbsp;</button>
        </div>

        <?php if ($otherRides): ?>
  <div class="suggestion">
    <h3>Plus de covoiturages ce jour-là</h3>
    <?php foreach ($otherRides as $ride): ?>
      <div class="card">
        <div class="card-header">
          <?php $imgSrc = driverPhoto($pdo, $ride); ?>
          <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Conducteur">
        </div>
        <div class="card-body">
          <h2><?= htmlspecialchars($ride['depart']) ?> → <?= htmlspecialchars($ride['destination']) ?></h2>
          <p>
            Départ à <?= date('H:i', strtotime($ride['heure_depart'])) ?><br>
            Places restantes : <?= (int)$ride['places_restantes'] ?><br>
            Prix : <?= htmlspecialchars((string)$ride['prix']) ?> €
          </p>
          <a href="details.php?id=<?= (int)$ride['id'] ?>" class="btn-detail">+ d'informations</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

    <?php else: ?>
        <div class="no-results">
            <p>Aucun covoiturage trouvé avec ces critères.</p>
        </div>
    <?php endif; ?>
</main>

<footer>
        <div class="footer-links">
            <a href="#" id="open-cookie-modal">Gérer mes cookies</a>
            <span>|</span>
            <span>EcoRide@gmail.com</span>
            <span>|</span>
            <a href="mentions_legales.php">Mentions légales</a>
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


<script src="assets/js/cookie-consent.js" defer></script>
<script src="assets/js/filtres.js" defer></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const menuToggle = document.getElementById("menu-toggle");
    const mobileMenu = document.getElementById("mobile-menu");
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener("click", function () {
            mobileMenu.classList.toggle("active");
        });
        document.querySelectorAll("#mobile-menu a").forEach(link => {
            link.addEventListener("click", function () {
                mobileMenu.classList.remove("active");
            });
        });
    }
});
</script>
</body>
</html>
