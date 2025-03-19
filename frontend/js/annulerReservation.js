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
// Sélectionner les éléments
(function() {
const cancelButtons = document.querySelectorAll('.btn-danger'); 
const cancelModal = document.getElementById('cancel-reservation-modal');  // La modale de confirmation
const modalCancelConfirm = document.getElementById('modal-cancel-confirm');  // Bouton confirmer
const modalCancelCancel = document.getElementById('modal-cancel-cancel');  // Bouton annuler

let reservationId = null;  // L'ID de la réservation à annuler

// Afficher la modale lorsqu'on clique sur un bouton d'annulation
cancelButtons.forEach(button => {
    button.addEventListener('click', (e) => {
        e.preventDefault();  // Empêche le bouton de fonctionner immédiatement
        reservationId = button.getAttribute('data-reservation-id');  // Récupérer l'ID de la réservation
        if (reservationId) {
            cancelModal.style.display = 'flex';  // Afficher la modale
        } else {
            console.log("Aucun ID de covoiturage trouvé dans l'attribut.");
        }
    });
});

// Confirmer l'annulation
modalCancelConfirm.addEventListener('click', () => {
    if (reservationId) {
        // Log pour vérifier que l'ID est correct avant de rediriger
        console.log("Redirection vers annuler_reservation.php?id=" + reservationId);
        
        // Rediriger vers la page d'annulation de la réservation avec l'ID de la réservation
        window.location.href = `/frontend/annuler_reservation.php?id=${reservationId}`;
    } else {
        console.log("Aucun ID de réservation trouvé.");
    }
});

// Fermer la modale si l'utilisateur annule
modalCancelCancel.addEventListener('click', () => {
    cancelModal.style.display = 'none';  // Cacher la modale
});

// Fermer la modale si l'utilisateur clique sur la croix
const closeModalButton = cancelModal.querySelector('.close-reservation-btn');
closeModalButton.addEventListener('click', () => {
    cancelModal.style.display = 'none';  // Cacher la modale
});

// Fermer la modale si l'utilisateur clique en dehors de celle-ci
window.addEventListener('click', (e) => {
    if (e.target === cancelModal) {
        cancelModal.style.display = 'none';
    }
});
})