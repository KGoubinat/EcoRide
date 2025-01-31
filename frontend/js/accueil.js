document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("rechercheForm");
    const resultsContainer = document.getElementById('results');

    // Fonction pour envoyer la recherche au backend et afficher les résultats
    function searchCovoiturages(queryParams) {
        const url = new URL('recherche-covoiturages.php');
        Object.keys(queryParams).forEach(key => {
            if (queryParams[key]) {
                url.searchParams.append(key, queryParams[key]);
            }
        });

        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    resultsContainer.innerHTML = data.map(result => `
                        <div class="voyage-card">
                            <p>ID : ${result.id}</p>
                            <p>Départ : ${result.depart}</p>
                            <p>Destination : ${result.destination}</p>
                            <p>Passagers : ${result.passagers}</p>
                            <p>Prix : ${result.prix}€</p>
                            <p>Durée : ${result.duree}h</p>
                            <p>Note : ${result.note} étoiles</p>
                            <p>Écologique : ${result.ecologique ? 'Oui' : 'Non'}</p>
                        </div>
                    `).join('');
                } else {
                    resultsContainer.innerHTML = "<p>Aucun covoiturage trouvé avec ces critères.</p>";
                }
            })
            .catch(error => {
                console.error('Erreur lors de la recherche :', error);
            });
    }

    // Gestionnaire d'événement "submit" pour le formulaire
    form.addEventListener("submit", function (event) {
        const queryParams = {
            start: document.getElementById('start').value.trim(),
            end: document.getElementById('end').value.trim(),
            passengers: document.getElementById('passengers').value.trim(),
            date: document.getElementById('date').value.trim()
        };

        // Appeler la fonction de recherche avec les paramètres du formulaire
        searchCovoiturages(queryParams);
    });
});


document.addEventListener("DOMContentLoaded", function () {
    const authButton = document.getElementById("authButton");
    
    // Vérifie si l'utilisateur est connecté
    const isLoggedIn = localStorage.getItem("isLoggedIn") === "true";

    if (isLoggedIn) {
        // Si l'utilisateur est connecté, afficher le bouton "Déconnexion"
        authButton.innerHTML = `<a href="#" id="logoutBtn">Déconnexion</a>`;

        // Gestion de la déconnexion
        const logoutBtn = document.getElementById("logoutBtn");
        logoutBtn.addEventListener("click", function () {
            // Supprimer les informations de l'utilisateur (ex : token, état de connexion)
            localStorage.removeItem("isLoggedIn");
            localStorage.removeItem("userToken"); // Si tu utilises un token ou d'autres informations

            // Rediriger l'utilisateur vers la page de connexion
            window.location.href = "connexion.html"; // Ou redirige vers une autre page, si nécessaire
        });
    } else {
        // Si l'utilisateur n'est pas connecté, afficher le bouton "Connexion"
        authButton.innerHTML = `<a href="connexion.html">Connexion</a>`;
    }
});

