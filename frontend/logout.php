<?php
// Démarrer la session
session_start();

// Supprimer toutes les variables de session
session_unset();

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion ou d'accueil
header("Location: /frontend/accueil.php");  
exit;
?>
