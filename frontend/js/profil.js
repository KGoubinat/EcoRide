document.addEventListener("DOMContentLoaded", function() {

    // Gestion du menu burger
    const menuToggle = document.getElementById("menu-toggle");
    const mobileMenu = document.getElementById("mobile-menu");

    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener("click", function () {
            mobileMenu.classList.toggle("active");
        });

        // Fermer le menu après un clic sur un lien
        document.querySelectorAll("#mobile-menu a").forEach(link => {
            link.addEventListener("click", function () {
                mobileMenu.classList.remove("active");
            });
        });
    }
    
    const authButton = document.getElementById('authButton');
    const profilButton = document.getElementById('profilButton');

    const isLoggedIn = authButton.getAttribute('data-logged-in') === 'true';

    console.log("Is user logged in? " + isLoggedIn); // Affiche dans la console si l'utilisateur est connecté ou non

    if (isLoggedIn) {
        authButton.innerHTML = '<a href="/frontend/deconnexion.php">Déconnexion</a>';
        profilButton.innerHTML = '<a href="/frontend/profil.php">Profil</a>';
    } else {
        authButton.innerHTML = '<a href="/frontend/connexion.html">Connexion</a>';
        profilButton.style.display = 'none';  // Masquer le bouton Profil
    }

})
