
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


    const vehicleForm = document.getElementById("vehicleForm");
    const successModal = document.getElementById("successModal");
    const closeSuccessModal = document.getElementById("closeSuccessModal");

    if (vehicleForm) {
        vehicleForm.addEventListener("submit", function(event) {
            event.preventDefault();

            const marque = document.getElementById("marque").value.trim();
            const modele = document.getElementById("modele").value.trim();
            const plaque = document.getElementById("plaque_immatriculation").value.trim();
            const dateImmat = document.getElementById("date_1ere_immat").value.trim();
            const energie = document.getElementById("energie").value.trim();
            const nbPlaces = document.getElementById("nb_places").value.trim();
            const preferences = document.getElementById("preferences") ? document.getElementById("preferences").value.trim() : "";
            const fumeur = document.getElementById("fumeur").checked ? 1 : 0;
            const animal = document.getElementById("animal").checked ? 1 : 0;

            if (!marque || !modele || !plaque || !dateImmat || !energie || !nbPlaces) {
                alert("Tous les champs obligatoires doivent être remplis !");
                return;
            }

            const formData = new FormData(vehicleForm);

            formData.append('fumeur', fumeur);
            formData.append('animal', animal);
            formData.append('preferences', preferences);



            const xhr = new XMLHttpRequest();
            xhr.open("POST", "ajouter_vehicule.php", true);

            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        successModal.style.display = "flex";
                        vehicleForm.reset();  // Réinitialiser le formulaire
                    } else {
                        alert(response.message);  // Afficher l'erreur
                    }
                } else {
                    alert("Erreur lors de l'ajout du véhicule.");
                }
            };
            

            xhr.send(formData);
        });
    }

    // Gérer la fermeture de la modale
    if (closeSuccessModal) {
        closeSuccessModal.addEventListener("click", function() {
            successModal.style.display = "none";
            location.reload();
        });
    }

    // Fermer la modale si l'utilisateur clique en dehors du contenu
    window.addEventListener("click", function(event) {
        if (event.target === successModal) {
            successModal.style.display = "none";
            location.reload();
        }
    });
});
