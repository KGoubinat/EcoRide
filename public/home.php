<?php
require __DIR__ . '/init.php';  // <-- une ligne pour tout initialiser

// Vérifie si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']); 

function getConsent(): array {
  $name = 'ecoride_consent_v1';
  if (empty($_COOKIE[$name])) return [];
  $raw = urldecode($_COOKIE[$name]); 
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}


$consent = getConsent();

?>


<!DOCTYPE html> 
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Accueil</title>
    <base href="<?= htmlspecialchars((string)BASE_URL, ENT_QUOTES) ?>">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/modern.css">
    <meta name="description" content="Partagez vos trajets et réduisez votre empreinte carbone avec EcoRide. Rejoignez une communauté qui covoiture près de chez vous.">
    <link rel="canonical" href="<?= htmlspecialchars(current_url(false), ENT_QUOTES) ?>">
    <meta property="og:title" content="EcoRide — Covoiturage écologique et solidaire">
    <meta property="og:description" content="Partagez vos trajets et réduisez votre empreinte carbone avec EcoRide.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars(current_url(true), ENT_QUOTES) ?>">
    <meta property="og:image" content="<?= htmlspecialchars(absolute_from_base('assets/images/cover.jpg'), ENT_QUOTES) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="preload" as="image" href="assets/images/Fond-768.jpg"  type="image/jpg" media="(max-width: 767px)"  fetchpriority="high">
    <link rel="preload" as="image" href="assets/images/Fond-1280.jpg" type="image/jpg" media="(min-width: 768px) and (max-width: 1279px)" fetchpriority="high">
    <link rel="preload" as="image" href="assets/images/Fond-1920.jpg" type="image/jpg" media="(min-width: 1280px)" fetchpriority="high">


</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <h1>EcoRide</h1>
            </div>
            
            <!-- Bouton menu burger -->
            <div class="menu-toggle" id="menu-toggle">☰</div>

            <nav id="navbar">
                <ul>
                    <li><a href="home.php" aria-current="page">Accueil</a></li>
                    <li><a href="contact_info.php">Contact</a></li>
                    <li><a href="rides.php">Covoiturages</a></li>
                    <li id="profilButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                    <li id="authButton" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                </ul>
            </nav>
        </div>

        <!-- Menu mobile (caché par défaut) -->
        <nav id="mobile-menu">
            <ul>
                <li><a href="home.php">Accueil</a></li>
                <li><a href="rides.php">Covoiturages</a></li>
                <li><a href="contact_info.php">Contact</a></li>
                <li id="profilButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
                <li id="authButtonMobile" data-logged-in="<?= $isLoggedIn ? 'true' : 'false'; ?>"></li>
            </ul>
        </nav>
    </header>
    
    <main>
        <div class="first-column">
            <section class="presentation">
                <div class="intro">
                    <h2>Bienvenue sur EcoRide!</h2>
                    <p>Rejoignez la plateforme de covoiturage dédiée à la réduction de l'impact environnemental et à l'économie des déplacements. Chez EcoRide, nous facilitons les trajets en voiture partagée pour ceux qui souhaitent voyager de manière plus durable et économique. Que vous soyez conducteur ou passager, notre application vous aide à réduire votre empreinte carbone tout en économisant sur vos déplacements. Ensemble, faisons de chaque trajet un geste pour la planète !</p>
                </div>
            </section>
        </div>

        <div class="second-column">
            <section class="form">
                <div class="formulaire">
                    <h2 class="ecoride-title">EcoRide</h2>
                    <p>Voyagez ensemble, économisez ensemble.</p>
                    <form id="rechercheForm" action="resultatsrides.php" method="GET">
                        <input list="cities" id="start" placeholder="Départ" name="start" required><br>
                        <input list="cities" id="end" placeholder="Destination" name="end" required><br>
                        <input type="number" id="passengers" placeholder="Passager(s)" name="passengers" min="1" required><br>
                        <label for="date" class="sr-only">Date du covoiturage</label>
                        <input type="date" id="date" name="date" required><br>
                        <button type="submit" class="button">Rechercher</button>
                    </form>
                    
                    <datalist id="cities">
                        <?php foreach ($villes as $ville) : ?>
                            <option value="<?= htmlspecialchars($ville) ?>">
                        <?php endforeach; ?>
                    </datalist>


                </div>
                <div id="results"></div>
            </section>
        </div>

        <section class="third-column">
            <section class="details">
                <div class="highlight">
                    <h3>Écologique</h3>
                    <p>Nous croyons en un avenir plus vert grâce à une mobilité durable.</p>
                    <h3>Économique</h3>
                    <p>Partagez les frais de déplacement et économisez sur vos trajets quotidiens.</p>
                    <h3>Convivial</h3>
                    <p>Voyagez en bonne compagnie et faites de nouvelles rencontres.</p>
                </div>
            </section>

            <div class="rejoindre">
                <p>Plateforme intuitive et sécurisée.</p>
                <p>Large choix de trajets adaptés à vos besoins.</p>
                <p>Des conducteurs et passagers vérifiés.</p>
                <button onclick="location.href='register.php'" id="rejoindreBtn" class="button">Rejoignez-nous&nbsp;!</button>
            </div>
        </section>
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

    <!-- Script JavaScript -->
    <script src="assets/js/cookie_consent.js" defer></script>
    <script src="assets/js/home.js" defer></script>
    
    <!-- Analytics (bloqué tant que pas consenti) -->
    <script
        type="text/plain"
        data-consent="analytics"
        data-src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXX"
        async
    ></script>

    <script type="text/plain" data-consent="analytics">
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date()); 
        gtag('config', 'G-XXXXXXX');
    </script>

    <!-- Exemple marketing -->
    <script type="text/plain" data-consent="marketing">
     <!-- Initialisation d’un SDK marketing fictif -->
        console.log("SDK marketing initialisé");
    </script>
</body>
</html>
