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
    const modalReservationReussie = document.getElementById('modalReservationReussie');

    if (btnParticiper) {
        btnParticiper.addEventListener("click", function () {
            const idCovoiturage = this.getAttribute("data-id");
            const prixCovoiturage = this.getAttribute("data-prix");

            const urlParams = new URLSearchParams(window.location.search);
            const passengers = urlParams.get('passengers');

            if (!passengers) {
                alert("Le nombre de passagers n'est pas défini.");
                return;
            }

            modalMessage1.textContent = `Ce covoiturage coûte ${prixCovoiturage} crédits. Voulez-vous continuer ?`;
            modalConfirmation1.style.display = 'flex';

            document.getElementById('modalConfirm1').onclick = function () {
                modalConfirmation1.style.display = 'none';
                modalConfirmation2.style.display = 'flex';
            };

            document.getElementById('modalCancel1').onclick = function () {
                modalConfirmation1.style.display = 'none';
            };
        });
    }

    document.getElementById('modalConfirm2').onclick = function () {
        const idCovoiturage = btnParticiper.getAttribute("data-id");
        const csrfToken = btnParticiper.getAttribute("data-token");

        const urlParams = new URLSearchParams(window.location.search);
        const passengers = urlParams.get('passengers');

        if (!passengers) {
            alert("Le nombre de passagers est manquant.");
            return;
        }

        fetch("/backend/reserver.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": csrfToken
            },
            body: JSON.stringify({
                ride_id: idCovoiturage,
                passengers: passengers
            })
        })
        .then(response => response.json())
        .then(data => {
            modalConfirmation2.style.display = 'none';

            if (data.success) {
                modalReservationReussie.style.display = 'flex';
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(error => {
            console.error("Erreur :", error);
            alert("Erreur de communication avec le serveur.");
        });
    };

    document.getElementById('modalCancel2').onclick = function () {
        modalConfirmation2.style.display = 'none';
    };

    document.getElementById('modalConfirmReservation').onclick = function () {
        modalReservationReussie.style.display = 'none';
        location.reload();
    };
});