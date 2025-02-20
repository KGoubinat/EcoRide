<?php
session_start();
require_once '/../cloudinary_php_master/src/Cloudinary.php';

\Cloudinary::config(array(
    "cloud_name" => "dj9iiquhw",
    "api_key" => "191869388494711",
    "api_secret" => "pjhNfoa_aSfLssECHSy_kpUliHQ"
));

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_email'])) {
    echo "Utilisateur non connecté.";
    exit;
}

$user_email = $_SESSION['user_email'];

// Vérifier si une photo a été téléchargée
if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
    $photo = $_FILES['photo'];
    
    // Vérification du type et taille de l'image
    if (!in_array($photo['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
        echo "Format de fichier non autorisé. Veuillez télécharger une image JPEG, PNG ou GIF.";
        exit;
    }
    
    // Envoi de l'image à Cloudinary
    try {
        $uploadResult = \Cloudinary\Uploader::upload($photo['tmp_name'], [
            "folder" => "profile_pictures"  // Choisir un dossier pour l'image
        ]);

        // Récupérer l'URL de l'image téléchargée
        $imageUrl = $uploadResult['secure_url'];

        // Connexion à la base de données
        $databaseUrl = getenv('JAWSDB_URL');
        $parsedUrl = parse_url($databaseUrl);
        
        $servername = $parsedUrl['host'];
        $username = $parsedUrl['user'];
        $password = $parsedUrl['pass'];
        $dbname = ltrim($parsedUrl['path'], '/');
        
        // Connexion à la base de données avec PDO
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Mise à jour de la photo dans la base de données
            $stmtUpdatePhoto = $conn->prepare("UPDATE users SET photo = ? WHERE email = ?");
            $stmtUpdatePhoto->execute([$imageUrl, $user_email]);

            echo "Photo mise à jour avec succès.";

        } catch (PDOException $e) {
            echo "Erreur de connexion à la base de données : " . $e->getMessage();
        }

    } catch (Exception $e) {
        echo "Erreur lors du téléchargement de la photo vers Cloudinary : " . $e->getMessage();
    }

} else {
    echo "Aucune photo téléchargée ou erreur lors de l'upload.";
}
?>
