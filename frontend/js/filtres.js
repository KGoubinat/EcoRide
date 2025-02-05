// Fonction qui applique les filtres en modifiant l'URL
function applyFilters() {
    // Récupérer les valeurs des filtres
    const start = document.querySelector('select[name="start"]').value;
    const end = document.querySelector('select[name="end"]').value;
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
        url += `date=${date}&`;
    }

    // Nettoyer l'URL (supprimer le dernier "&")
    url = url.slice(0, -1);

    // Rediriger vers l'URL avec les filtres appliqués
    window.location.href = url;
}

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
});