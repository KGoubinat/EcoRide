document.addEventListener("DOMContentLoaded", function() {
    const authButton = document.getElementById('authButton');
    const profilButton = document.getElementById('profilButton');

    const isLoggedIn = authButton.getAttribute('data-logged-in') === 'true';

    console.log("Is user logged in? " + isLoggedIn); // Affiche dans la console si l'utilisateur est connecté ou non

    if (isLoggedIn) {
        authButton.innerHTML = '<a href="deconnexion.php">Déconnexion</a>';
        profilButton.innerHTML = '<a href="profil.php">Profil</a>';
    } else {
        authButton.innerHTML = '<a href="connexion.html">Connexion</a>';
        profilButton.style.display = 'none';  // Masquer le bouton Profil
    }

})
