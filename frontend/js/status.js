document.addEventListener("DOMContentLoaded", function() {
// Gérer la soumission du formulaire de statut
const statusForm = document.getElementById("status-form");
if (statusForm) {
    statusForm.addEventListener("submit", function(e) {
        e.preventDefault();

        var status = document.getElementById("status")?.value;
        console.log("Tentative de mise à jour du statut : " + status);

        var modal = document.getElementById("status-modal");
        if (!modal) {
            console.error("Erreur: Modale introuvable !");
            return;
        }

        showModal("Êtes-vous sûr de vouloir mettre à jour votre statut ?", function() {
            console.log("Statut confirmé, mise à jour...");

            var formData = new FormData();
            formData.append("status", status);

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_status.php", true);

            // Lorsque la requête est terminée, traite la réponse
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        // Parse la réponse JSON
                        const response = JSON.parse(xhr.responseText);
                        if (response.status === 'success') {
                            showSuccessModal("Votre statut a été mis à jour avec succès !");
                            const statusElement = document.querySelector('.user-status h2');
                            if (statusElement) {
                                statusElement.innerText = "Statut : " + response.newStatus; // Afficher le nouveau statut
                            }

                            // Appeler la fonction pour forcer la mise à jour de la grille
                            forceGridUpdate();

                            // Masquer ou afficher des sections en fonction du statut
                            toggleSectionsBasedOnStatus(response.newStatus);
                        } else {
                            alert("Erreur lors de la mise à jour.");
                        }
                    } catch (error) {
                        console.error("Erreur lors du traitement de la réponse JSON", error);
                    }
                } else {
                    alert("Erreur lors de la mise à jour.");
                }
            };

            xhr.send(formData);
        });
    });
}

// Fonction pour afficher ou masquer des sections selon le statut de l'utilisateur
function toggleSectionsBasedOnStatus(status) {
    const vehicleInfoSection = document.getElementById("vehicleForm"); // ID de la section d'info véhicule
    const travelFormSection = document.getElementById("voyageForm"); // ID de la section de saisie de voyage

    if (status === "passager") {
        // Si le statut est "passager", on cache les sections liées au véhicule et au voyage
        if (vehicleInfoSection) {
            vehicleInfoSection.style.display = "none";
        }
        if (travelFormSection) {
            travelFormSection.style.display = "none";
        }
    } else {
        // Sinon, on affiche ces sections
        if (vehicleInfoSection) {
            vehicleInfoSection.style.display = "block";
        }
        if (travelFormSection) {
            travelFormSection.style.display = "block";
        }
    }
}

// Fonction pour afficher une modale de confirmation
function showModal(message, onConfirm) {
    console.log("Affichage de la modale avec le message : " + message);
    var modal = document.getElementById("status-modal");
    var modalMessage = document.getElementById("modal-message");
    var confirmButton = document.getElementById("modal-confirm");
    var cancelButton = document.getElementById("modal-cancel");

    if (!modal || !modalMessage || !confirmButton || !cancelButton) {
        console.error("Erreur: Élément(s) de la modale introuvable(s).");
        return;
    }

    modalMessage.textContent = message;
    modal.classList.add('show'); // Affiche la modale

    confirmButton.onclick = function() {
        onConfirm();
        modal.classList.remove('show'); // Ferme la modale
    };

    cancelButton.onclick = function() {
        modal.classList.remove('show'); // Ferme la modale
    };

    window.onclick = function(event) {
        if (event.target === modal) {
            modal.classList.remove('show'); // Ferme la modale si clic à l'extérieur
        }
    };
}

// Fonction pour afficher une modale de succès
function showSuccessModal(message) {
    var successModal = document.getElementById("status-success-modal");
    var successMessage = document.getElementById("success-modal-message");

    if (!successModal || !successMessage) {
        console.error("Erreur: L'élément de la modale de succès est introuvable.");
        return;
    }

    successMessage.textContent = message;
    successModal.style.display = "flex";

    window.onclick = function(event) {
        if (event.target === successModal) {
            successModal.style.display = "none";
            location.reload(); // Recharger la page pour s'assurer du bon affichage
        }
    };
}

// Fonction pour forcer le changement de la grille à une seule colonne
function forceGridUpdate() {
    const grid = document.querySelector('.adaptation');
    
    if (grid) {
        // Désactiver les styles de grille définis dans le CSS
        grid.style.gridTemplateColumns = "1fr";  // Forcer une seule colonne

        // Forcer le recalcul du layout en cachant et réaffichant la grille
        grid.style.display = 'none'; // Cacher la grille temporairement
        grid.offsetHeight;  // Reflow forcé
        grid.style.display = 'grid'; // Réafficher la grille

        console.log("La grille a été mise à jour en une seule colonne");
    }
}
})