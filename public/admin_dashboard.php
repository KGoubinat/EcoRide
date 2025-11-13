<?php
// admin_dashboard.php (ex.)
require __DIR__ . '/init.php'; // ← démarre la session, définit BASE_URL, fournit $pdo = getPDO()

header('X-Robots-Tag: noindex, nofollow', true);

// 1) Autorisation admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'home.php');
    exit;
}

// 2) Fonctions data
function getCarpoolData(PDO $pdo): array {
    $sql = "
        SELECT DATE(`date`) AS jour, COUNT(*) AS total_covoiturages
        FROM covoiturages
        GROUP BY jour
        ORDER BY jour ASC
    ";
    return $pdo->query($sql)->fetchAll();
}

function getCreditsData(PDO $pdo): array {
    $sql = "
        SELECT DATE(`date`) AS jour, COUNT(*) * 2 AS total_credits
        FROM covoiturages
        WHERE statut = 'terminé'
        GROUP BY jour
        ORDER BY jour ASC
    ";
    return $pdo->query($sql)->fetchAll();
}

function getTotalCredits(PDO $pdo): int {
    $row = $pdo->query("
        SELECT COUNT(*) AS total_covoiturages
        FROM covoiturages
        WHERE statut = 'terminé'
    ")->fetch();
    return (int)$row['total_covoiturages'] * 2;
}

// 3) Récupération des datasets
try {
    $carpoolData   = getCarpoolData($pdo);
    $creditsData   = getCreditsData($pdo);
    $totalCredits  = getTotalCredits($pdo);

    // Utilisateurs
    $users = $pdo->query("SELECT id, firstName, lastName, etat, email, role FROM users ORDER BY id ASC")->fetchAll();
} catch (Throwable $e) {
    http_response_code(500);
    exit('Erreur lors du chargement des statistiques.');
}

// 4) Prépare pour Chart.js
$carpoolLabels = array_column($carpoolData, 'jour');
$carpoolValues = array_map('intval', array_column($carpoolData, 'total_covoiturages'));
$creditsLabels = array_column($creditsData, 'jour');
$creditsValues = array_map('intval', array_column($creditsData, 'total_credits'));

// Prépare le paramètre back pour revenir ici après l'action
$back = 'admin_dashboard.php';
if (isset($_GET['role'])) {
    $back .= '?role=' . urlencode((string)$_GET['role']);
}
$backParam = urlencode($back);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- base dynamique, marche en local & Heroku (assure un slash final) -->
    <base href="<?= htmlspecialchars(rtrim(BASE_URL, '/').'/', ENT_QUOTES) ?>">
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="assets/css/modern.css">
    <title>Tableau de bord</title>
    <meta name="description" content="Tableau de bord administrateur EcoRide : statistiques des covoiturages, crédits gagnés par jour, et gestion des comptes (employés & utilisateurs).">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= htmlspecialchars(rtrim(BASE_URL,'/').'/admin_dashboard.php', ENT_QUOTES) ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
    <div class="header-container">
        <h1>Tableau de bord</h1>

        <div class="menu-toggle" id="menu-toggle">☰</div>

        <nav id="navbar">
            <ul>
                <li><a aria-current="page" href="admin_dashboard.php">Tableau de bord</a></li>
                <li><a href="add_employee.php">Ajouter un Employé</a></li>
                <li><a href="manage_employees.php">Gérer les Employés</a></li>
                <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </div>

    <nav id="mobile-menu">
        <ul>
            <li><a href="admin_dashboard.php">Tableau de bord</a></li>
            <li><a href="add_employee.php">Ajouter un Employé</a></li>
            <li><a href="manage_employees.php">Gérer les Employés</a></li>
            <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
            <li><a href="logout.php">Déconnexion</a></li>
        </ul>
    </nav>
</header>

<main class="adaptation">
    <section class="stats">
        <h2>Statistiques de la plateforme</h2>

        <div>
            <h3>Nombre de Covoiturages par Jour</h3>
            <canvas id="carpoolChart" width="800" height="400"></canvas>
        </div>

        <div>
            <h3>Crédit Gagné par Jour</h3>
            <canvas id="creditChart" width="800" height="400"></canvas>
        </div>

        <div>
            <h3>Total de Crédit Gagné par la Plateforme</h3>
            <p id="totalCredits"></p>
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

<script src="assets/js/accueil.js" defer></script>
<script src="assets/js/cookie-consent.js" defer></script>
<script>
    // Graphiques Chart.js
    const carpoolLabels = <?= json_encode($carpoolLabels, JSON_UNESCAPED_UNICODE) ?>;
    const carpoolValues = <?= json_encode($carpoolValues, JSON_UNESCAPED_UNICODE) ?>;

    const creditsLabels = <?= json_encode($creditsLabels, JSON_UNESCAPED_UNICODE) ?>;
    const creditsValues = <?= json_encode($creditsValues, JSON_UNESCAPED_UNICODE) ?>;

    new Chart(document.getElementById('carpoolChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: carpoolLabels,
        datasets: [{
            label: 'Nombre de Covoiturages',
            data: carpoolValues,
            borderWidth: 1,
            backgroundColor: '#1b46beff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: { color: "#ffffff" }
            },
            title: {
                display: true,
                text: "Nombre de Covoiturages par Jour",
                color: "#ffffff"
            },
            tooltip: {
                bodyColor: "#ffffff",
                titleColor: "#ffffff"
            }
        },
        scales: {
            x: {
                ticks: { color: "#ffffff" },
                grid: { color: "rgba(255,255,255,0.2)" }
            },
            y: {
                beginAtZero: true,
                ticks: { color: "#ffffff" },
                grid: { color: "rgba(255,255,255,0.2)" }
            }
        }
    }
});

    new Chart(document.getElementById('creditChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: creditsLabels,
        datasets: [{
            label: 'Crédit Gagné',
            data: creditsValues,
            borderWidth: 2,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,0.5)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: { color: "#ffffff" }
            },
            title: {
                display: true,
                text: "Crédit Gagné par Jour",
                color: "#ffffff"
            },
            tooltip: {
                bodyColor: "#ffffff",
                titleColor: "#ffffff"
            }
        },
        scales: {
            x: {
                ticks: { color: "#ffffff" },
                grid: { color: "rgba(255,255,255,0.2)" }
            },
            y: {
                beginAtZero: true,
                ticks: { color: "#ffffff" },
                grid: { color: "rgba(255,255,255,0.2)" }
            }
        }
    }
});


    // Total des crédits
    const totalCredits = <?= (int)$totalCredits ?>;
    document.getElementById("totalCredits").textContent =
        "Total des crédits gagnés: " + totalCredits + " crédits";


</script>
</body>
</html>
