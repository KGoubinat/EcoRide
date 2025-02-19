<?php
// Connexion à la base de données
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
    echo "Connexion réussie à la base de données MySQL.";
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

// Vérification de l'ID de l'avis et du statut
if (isset($_GET['id']) && isset($_GET['status'])) {
    $review_id = $_GET['id'];
    $status = $_GET['status'];

    // Récupérer les détails de l'avis depuis la base de données
    $sql = "SELECT * FROM reviews WHERE id = :review_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['review_id' => $review_id]);
    $review = $stmt->fetch();

    // Vérifier si l'avis existe
    if ($review) {
        // Mettre à jour le statut de l'avis
        $update_sql = "UPDATE reviews SET status = :status WHERE id = :review_id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute(['status' => $status, 'review_id' => $review_id]);

        // Si l'avis est validé, l'insérer dans la table avis_conducteur
        if ($status === 'approved') {
            // Récupérer les informations nécessaires pour l'insertion
            $driver_id = $review['driver_id'];
            $user_id = $review['user_id'];
            $rating = $review['rating'];
            $comment = $review['comment'];
            $date_review = date('Y-m-d H:i:s'); // Date actuelle


            // Insérer l'avis validé dans la table avis_conducteur
            $insert_sql = "INSERT INTO avis_conducteurs (conducteur_id, utilisateur_id, note, commentaire, date_avis) 
                           VALUES (:driver_id, :user_id, :rating, :comment, :date_review)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                'driver_id' => $driver_id,
                'user_id' => $user_id,
                'rating' => $rating,
                'comment' => $comment,
                'date_review' => $date_review,
            ]);

            // Rediriger après l'insertion
            header("Location: employee_reviews.php?success=1"); // Redirige vers la page des avis avec un message de succès
            exit;
        }
    } else {
        // Si l'avis n'existe pas, rediriger vers la page des avis
        header("Location: employee_reviews.php?error=1");
        exit;
    }
} else {
    // Si les paramètres ne sont pas définis, rediriger vers la page des avis
    header("Location: employee_reviews.php?error=1");
    exit;
}
?>
