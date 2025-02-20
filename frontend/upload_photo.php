<?php
// Vérifier si un fichier a été téléchargé
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Obtenir les informations du fichier
    $fileTmpPath = $_FILES['photo']['tmp_name'];
    $fileName = $_FILES['photo']['name'];
    $fileSize = $_FILES['photo']['size'];
    $fileType = $_FILES['photo']['type'];

    // Définir le dossier où les photos seront enregistrées
    $uploadDir = '/frontend/uploads/photos/';
    $filePath = $uploadDir . basename($fileName);

    // Vérifier si le fichier est une image
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (in_array($fileType, $allowedTypes)) {
        // Déplacer le fichier dans le dossier
        if (move_uploaded_file($fileTmpPath, $filePath)) {
            // Mettre à jour le chemin de la photo de profil dans la base de données
            $stmt = $conn->prepare("UPDATE users SET photo = ? WHERE id = ?");
            $stmt->execute([$filePath, $user['id']]);
            echo "Photo de profil mise à jour avec succès!";
            header('Location: /frontend/profil.php'); // Rediriger vers la page de profil pour voir les changements
        } else {
            echo "Erreur lors de l'upload de l'image.";
        }
    } else {
        echo "Le fichier téléchargé n'est pas une image valide.";
    }
} else {
    echo "Aucun fichier téléchargé ou erreur lors de l'upload.";
}
?>
