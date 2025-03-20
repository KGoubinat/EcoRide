// Fonction qui applique les filtres en modifiant l'URL
function applyFilters() {
    // Récupérer les valeurs des filtres
    const start = document.querySelector('select[name="start"]').value;
    const end = document.querySelector('select[name="end"]').value;
    const passengers = document.getElementById('passengers').value;
    const ecolo = document.querySelector('input[name="ecolo"]:checked') ? document.querySelector('input[name="ecolo"]:checked').value : '';
    const prix = document.querySelector('select[name="prix"]').value;
    const duree = document.querySelector('select[name="duree"]').value;
    const note = document.querySelector('select[name="note"]').value;
    const date = document.querySelector('input[name="date"]').value;
    
    // Construire l'URL avec les paramètres de recherche
    let url = window.location.pathname + '?';

    // Ajouter les filtres à l'URL
    if (start) {
        url += `start=${start}&`;
    }
    if (end) {
        url += `end=${end}&`;
    }
    if (passengers) {
        url += `passengers=${passengers}&`;
    }
    if (ecolo) {
        url += `ecolo=${ecolo}&`;
    }
    if (prix) {
        url += `prix=${prix}&`;
    }
    if (duree) {
        url += `duree=${duree}&`;
    }
    if (note) {
        url += `note=${note}&`;
    }
    if (date) {
        // Ajouter une validation pour la date si nécessaire
        const isValidDate = date && !isNaN(Date.parse(date));
        if (isValidDate) {
            url += `date=${date}&`;
        }
    }

    // Nettoyer l'URL (supprimer le dernier "&")
    url = url.slice(0, -1);

    // Rediriger vers l'URL avec les filtres appliqués
    window.location.href = url;
}

document.addEventListener("DOMContentLoaded", function() {

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
});
