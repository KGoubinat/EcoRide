document.addEventListener("DOMContentLoaded", function () {

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

    // Récupérer l'email de l'utilisateur depuis l'attribut 'data-user-email'
    const userEmail = authButton.getAttribute('data-user-email');

    if (!userEmail) {
        console.log("Aucun email utilisateur trouvé.");
    } else {
        console.log("Email utilisateur trouvé :", userEmail);
    }

    // Autres parties du code
    if (authButton && profilButton) {
        const isLoggedIn = authButton.getAttribute('data-logged-in') === 'true';
        
        if (isLoggedIn) {
            authButton.innerHTML = '<a href="/frontend/deconnexion.php">Déconnexion</a>';
            profilButton.innerHTML = '<a href="/frontend/profil.php">Profil</a>';
        } else {
            authButton.innerHTML = '<a href="/frontend/connexion.html">Connexion</a>';
            profilButton.style.display = 'none'; // Masquer le bouton Profil
        }
    }

    const btnParticiper = document.getElementById('btnParticiper');
    const modalConfirmation1 = document.getElementById('modalConfirmation1');
    const modalConfirmation2 = document.getElementById('modalConfirmation2');
    const modalMessage1 = document.getElementById('modalMessage1');
    
    // Nouvelle modale pour la réservation réussie
    const modalReservationReussie = document.getElementById('modalReservationReussie');

    if (btnParticiper) {
        btnParticiper.addEventListener("click", function () {
            console.log("bouton cliqué")
            const idCovoiturage = this.getAttribute("data-id");
            const prixCovoiturage = this.getAttribute("data-prix");

            // Récupérer le nombre de passagers depuis l'URL
            const urlParams = new URLSearchParams(window.location.search);
            const passengers = urlParams.get('passengers');  // Récupérer la valeur des passagers depuis l'URL

            if (!passengers) {
                alert("Le nombre de passagers n'est pas défini.");
                return;
            }

            // Affichage de la première modale avec le prix
            modalMessage1.textContent = `Ce covoiturage coûte ${prixCovoiturage} crédits. Voulez-vous continuer ?`;
            modalConfirmation1.style.display = 'flex';

            // Gestion de la première confirmation (oui/non)
            document.getElementById('modalConfirm1').onclick = function() {
                modalConfirmation1.style.display = 'none';
                modalConfirmation2.style.display = 'flex';
            };

            document.getElementById('modalCancel1').onclick = function() {
                modalConfirmation1.style.display = 'none';
            };
        });
    }

    // Gestion de la deuxième confirmation (oui/non)
    document.getElementById('modalConfirm2').onclick = function() {
        const idCovoiturage = btnParticiper.getAttribute("data-id");

        // Vérifier si l'email de l'utilisateur est disponible
        if (!userEmail) {
            alert("Utilisateur non connecté.");
            return;
        }

        // S'assurer que la variable 'passengers' est définie ici avant de l'utiliser
        const urlParams = new URLSearchParams(window.location.search);
        const passengers = urlParams.get('passengers');

        if (!passengers) {
            alert("Le nombre de passagers est manquant.");
            return;
        }

        // Envoi de la requête pour participer au covoiturage
        fetch("/frontend/participer.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `id=${encodeURIComponent(idCovoiturage)}&user_email=${encodeURIComponent(userEmail)}&passengers=${encodeURIComponent(passengers)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.message === "Réservation effectuée avec succès.") {
                    modalReservationReussie.style.display = 'flex';
                }
            } else {
                alert(data.message);  // Affiche le message d'erreur
            }
            modalConfirmation2.style.display = 'none'; // Fermer la modale de confirmation finale
            // Rediriger vers la page profil.php après la fermeture de la modale
            window.location.href = '/frontend/profil.php';
        })
        .catch(error => console.error("Erreur :", error));
    };

    // Fermer la deuxième modale en cas de "Non"
    document.getElementById('modalCancel2').onclick = function() {
        modalConfirmation2.style.display = 'none';
    };

    // Fermeture de la modale de réservation réussie lorsque l'utilisateur clique sur "OK"
    document.getElementById('modalConfirmReservation').onclick = function() {
        modalReservationReussie.style.display = 'none';
        location.reload();  // Optionnel : recharge la page pour voir les modifications
    };
});
