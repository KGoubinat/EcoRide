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

// Sélectionner tous les boutons "Annuler"
const cancelButtons = document.querySelectorAll('.cancel-ride-button');  // Tous les boutons "Annuler"

// Sélectionner la modale et les boutons de confirmation
const cancelModal = document.getElementById('cancel-modal');  // La modale
const modalCancelConfirm = document.getElementById('modal-cancel-confirm');  // Bouton "Confirmer"
const modalCancelCancel = document.getElementById('modal-cancel-cancel');  // Bouton "Annuler"

// Déclare une variable pour l'ID du covoiturage
let covoiturageId = null;

// Afficher la modale lorsqu'on clique sur un bouton d'annulation
cancelButtons.forEach(button => {
    button.addEventListener('click', (e) => {
        e.preventDefault();  // Empêche le formulaire de fonctionner immédiatement

        // Récupérer l'ID du covoiturage depuis l'attribut data-covoiturage-id
        covoiturageId = button.getAttribute('data-covoiturage-id');
        
        // Vérifier si l'ID est récupéré, sinon afficher un message d'erreur
        if (covoiturageId) {
            cancelModal.style.display = 'flex';  // Afficher la modale
        } else {
            console.log("Aucun ID de covoiturage trouvé dans l'attribut.");
        }
    });
});

// Confirmer l'annulation
modalCancelConfirm.addEventListener('click', () => {
    if (covoiturageId) {
        // Log pour vérifier que l'ID est correct avant de rediriger
        console.log("Redirection vers /frontend/annuler_covoiturage.php?id=" + covoiturageId);
        
        // Rediriger vers la page d'annulation du covoiturage avec l'ID du covoiturage
        window.location.href = `/frontend/annuler_covoiturage.php?id=${covoiturageId}`;
    } else {
        console.log("Aucun ID de covoiturage trouvé.");
    }
});

// Fermer la modale si l'utilisateur annule
modalCancelCancel.addEventListener('click', () => {
    cancelModal.style.display = 'none';  // Cacher la modale
});

// Fermer la modale si l'utilisateur clique sur la croix
const closeModalButton = cancelModal.querySelector('.close-btn');
closeModalButton.addEventListener('click', () => {
    cancelModal.style.display = 'none';  // Cacher la modale
});

// Fermer la modale si l'utilisateur clique en dehors de celle-ci
window.addEventListener('click', (e) => {
    if (e.target === cancelModal) {
        cancelModal.style.display = 'none';
    }
});
