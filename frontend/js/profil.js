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
    
     // Gestion de l'authentification utilisateur
     const authButton = document.getElementById('authButton');
     const profilButton = document.getElementById('profilButton');
     const authButtonMobile = document.getElementById('authButtonMobile');
     const profilButtonMobile = document.getElementById('profilButtonMobile');
 
     if (authButton && profilButton) {
         const isLoggedIn = authButton.getAttribute('data-logged-in') === 'true';
 
         console.log("Is user logged in? " + isLoggedIn); // Debug console
 
         if (isLoggedIn) {
             authButton.innerHTML = '<a href="/frontend/deconnexion.php">Déconnexion</a>';
             profilButton.innerHTML = '<a href="/frontend/profil.php">Profil</a>';
 
             if (authButtonMobile && profilButtonMobile) {
                 authButtonMobile.innerHTML = '<a href="/frontend/deconnexion.php">Déconnexion</a>';
                 profilButtonMobile.innerHTML = '<a href="/frontend/profil.php">Profil</a>';
             }
         } else {
             authButton.innerHTML = '<a href="/frontend/connexion.html">Connexion</a>';
             profilButton.style.display = 'none';  // Masquer le bouton Profil
 
             if (authButtonMobile && profilButtonMobile) {
                 authButtonMobile.innerHTML = '<a href="/frontend/connexion.html">Connexion</a>';
                 profilButtonMobile.style.display = 'none';  // Masquer le bouton Profil
             }
         }
     }

})
