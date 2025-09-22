<?php
// admin_dashboard.php (ex.)
require __DIR__ . '/init.php'; // ← démarre la session, définit BASE_URL, fournit $pdo = getPDO()

header('X-Robots-Tag: noindex, nofollow', true);

// 1) Autorisation admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrateur') {
    header('Location: ' . BASE_URL . 'accueil.php');
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
    <link rel="stylesheet" href="styles.css" />
    <title>Tableau de bord Administrateur</title>
    <meta name="description" content="Tableau de bord administrateur EcoRide : statistiques des covoiturages, crédits gagnés par jour, et gestion des comptes (employés & utilisateurs).">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= htmlspecialchars(rtrim(BASE_URL,'/').'/admin_dashboard.php', ENT_QUOTES) ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header>
    <div class="header-container">
        <h1>Bienvenue, Administrateur</h1>

        <div class="menu-toggle" id="menu-toggle">☰</div>

        <nav id="navbar">
            <ul>
                <li><a href="admin_dashboard.php">Tableau de bord</a></li>
                <li><a href="add_employee.html">Ajouter un Employé</a></li>
                <li><a href="manage_employees.php">Gérer les Employés</a></li>
                <li><a href="manage_users.php">Gérer les Utilisateurs</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </div>

    <nav id="mobile-menu">
        <ul>
            <li><a href="admin_dashboard.php">Tableau de bord</a></li>
            <li><a href="add_employee.html">Ajouter un Employé</a></li>
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

    <section class="comptes">
        <h2>Gérer les Comptes</h2>
        <div class="table-container">
            <h3>Liste des Employés et Utilisateurs</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>État</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['role']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($u['etat'])) ?></td>
                        <td>
                            <?php if ($u['etat'] === 'active'): ?>
                                <a href="update_user_status.php?id=<?= (int)$u['id'] ?>&status=suspended&back=<?= $backParam ?>">Suspendre</a>
                            <?php else: ?>
                                <a href="update_user_status.php?id=<?= (int)$u['id'] ?>&status=active&back=<?= $backParam ?>">Activer</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<footer>
    <p>EcoRide@gmail.com / <a href="mentions_legales.php">Mentions légales</a></p>
</footer>

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

    // Menu burger
    document.addEventListener("DOMContentLoaded", function () {
        const menuToggle = document.getElementById("menu-toggle");
        const mobileMenu = document.getElementById("mobile-menu");
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener("click", function () {
                mobileMenu.classList.toggle("active");
            });
            document.querySelectorAll("#mobile-menu a").forEach(link => {
                link.addEventListener("click", () => mobileMenu.classList.remove("active"));
            });
        }
    });
</script>
</body>
</html>
