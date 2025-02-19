document.addEventListener("DOMContentLoaded", function() {
    const voyageForm = document.getElementById("voyageForm");

    if (voyageForm) {
        voyageForm.addEventListener("submit", function(event) {
            event.preventDefault();  // Empêcher la soumission par défaut
            
            let isValid = true;

            // Récupérer les valeurs des champs
            const depart = document.getElementById("depart").value.trim();
            const destination = document.getElementById("destination").value.trim();
            const placeRestantes = document.getElementById("places_restantes").value.trim();
            const date = document.getElementById("date").value;
            const heureDepart = document.getElementById("heure_depart").value.trim();
            const duree = document.getElementById("duree").value.trim();
            const prix = document.getElementById("prix").value.trim();
            const vehiculeId = document.getElementById("vehicule").value;  // Récupérer la valeur du véhicule sélectionné

            // Vérifier si tous les champs sont remplis
            if (!depart || !destination || !placeRestantes || !date || !heureDepart || !duree || !prix || !vehiculeId) {
                alert("Tous les champs doivent être remplis.");
                isValid = false;
            }

            // Vérifier si l'heure de départ est valide (HH:MM)
            if (!isValidTimeFormat(heureDepart)) {
                alert("L'heure de départ doit être au format HH:MM.");
                isValid = false;
            }

            // Vérifier si la durée est valide (HH:MM)
            if (!isValidTimeFormat(duree)) {
                alert("La durée doit être au format HH:MM.");
                isValid = false;
            }

            // Vérifier si le nombre de places restantes est un nombre entier valide
            if (isNaN(placeRestantes) || placeRestantes <= 0) {
                alert("Le nombre de places restantes doit être un nombre positif.");
                isValid = false;
            }

            // Vérifier si le prix est un nombre et supérieur à 0
            if (isNaN(prix) || prix <= 0) {
                alert("Le prix doit être un nombre positif.");
                isValid = false;
            }

            // Si le formulaire est valide, afficher la modale de confirmation
            if (isValid) {
                showModal("Êtes-vous sûr de vouloir soumettre ce voyage ?\n       2 Credits seront retirés de votre solde", function() {
                    submitForm(depart, destination, placeRestantes, date, heureDepart, duree, prix, vehiculeId);  // Passer vehiculeId à la fonction submitForm
                });
            }
        });
    }

    // Fonction pour valider le format HH:MM pour l'heure et la durée
    function isValidTimeFormat(value) {
        return /^([01]?[0-9]|2[0-3]):([0-5]?[0-9])$/.test(value);
    }

    // Fonction pour afficher une modale de confirmation
    function showModal(message, onConfirm) {
        var modal = document.getElementById("travel-confirmation-modal");
        var modalMessage = document.getElementById("confirmation-message");
        var confirmButton = document.getElementById("modal-travel-confirm");
        var cancelButton = document.getElementById("modal-travel-cancel");

        if (!modal || !modalMessage || !confirmButton || !cancelButton) {
            console.error("Erreur: Élément(s) de la modale introuvable(s).");
            return;
        }

        modalMessage.textContent = message;
        modal.classList.add('show'); // Affiche la modale

        // Gestion du clic sur le bouton de confirmation
        confirmButton.addEventListener("click", function() {
            onConfirm();
            modal.classList.remove('show'); // Ferme la modale
        });

        // Gestion du clic sur le bouton d'annulation
        cancelButton.addEventListener("click", function() {
            modal.classList.remove('show'); // Ferme la modale
        });

        // Gestion du clic à l'extérieur de la modale pour la fermer
        window.addEventListener("click", function(event) {
            if (event.target === modal) {
                modal.classList.remove('show'); // Ferme la modale si clic à l'extérieur
            }
        });
    }

    // Fonction pour soumettre le formulaire
    function submitForm(depart, destination, placeRestantes, date, heureDepart, duree, prix, vehiculeId) {
        const formData = new FormData();
        formData.append("depart", depart);
        formData.append("destination", destination);
        formData.append("places_restantes", placeRestantes);
        formData.append("date", date);
        formData.append("heure_depart", heureDepart);
        formData.append("duree", duree);
        formData.append("prix", prix);
        formData.append("vehicule_id", vehiculeId);  // Ajouter vehicule_id au FormData

        const xhr = new XMLHttpRequest();
        xhr.open("POST", "ajoutCovoiturages.php", true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                console.log("Réponse brute du serveur : ", xhr.responseText);  // Affiche la réponse brute
                try {
                    const response = JSON.parse(xhr.responseText);  // Essaie de parser la réponse en JSON
                    if (response.status === 'success') {
                        showSuccessModal("Votre voyage a été ajouté avec succès !");
                    } else {
                        alert("Erreur: " + response.message);
                    }
                } catch (error) {
                    console.error("Erreur de parsing:", error);
                    alert("Erreur lors du traitement de la réponse.");
                }
            } else {
                alert("Erreur HTTP: " + xhr.status);
            }
        };
        
        xhr.send(formData);
    }

    // Fonction pour afficher la modale de succès
    function showSuccessModal(message) {
        var successModal = document.getElementById("travel-success-modal");
        var successMessage = document.getElementById("travel-success-message");

        if (!successModal || !successMessage) {
            console.error("Erreur: L'élément de la modale de succès est introuvable.");
            return;
        }

        successMessage.textContent = message;
        successModal.style.display = "flex";

        window.onclick = function(event) {
            if (event.target === successModal) {
                successModal.style.display = "none";
                location.reload();
            }
        };
    }

    // Fonction pour afficher la modale d'erreur
    function showErrorModal(message) {
        var errorModal = document.getElementById("travel-error-modal");
        var errorMessage = document.getElementById("travel-error-message");

        if (!errorModal || !errorMessage) {
            console.error("Erreur: L'élément de la modale d'erreur est introuvable.");
            return;
        }

        errorMessage.textContent = message;
        errorModal.style.display = "flex";

        window.onclick = function(event) {
            if (event.target === errorModal) {
                errorModal.style.display = "none";
            }
        };
    }
});
