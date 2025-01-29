        // Données utilisateurs simulées
        const users = [
            { email: "user1@example.com", password: "password123" },
            { email: "user2@example.com", password: "securepass456" }
        ];

        // Gestion de la soumission du formulaire
        document.getElementById("loginForm").addEventListener("submit", function(event) {
            event.preventDefault(); // Empêche le rechargement de la page

            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;

            // Vérifie si les identifiants sont valides
            const user = users.find(user => user.email === email && user.password === password);

            if (user) {
                // Connexion réussie
                localStorage.setItem("isLoggedIn", "true");
                localStorage.setItem("userEmail", email);
                alert("Connexion réussie !");
                window.location.href = "accueil.html"; // Redirige vers la page d'accueil
            } else {
                // Affiche un message d'erreur
                const errorMessage = document.getElementById("errorMessage");
                errorMessage.style.display = "block";
            }
        });