<?php
// Démarrer la session
session_start();

// Supprimer toutes les variables de session
session_unset();

// Détruire la session
session_destroy();

// Rediriger vers la page de connexion ou d'accueil
header("Location: accueil.php");  // Remplace 'login.php' par l'URL de ta page de connexion
exit;
?>
