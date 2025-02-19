document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("signupForm").addEventListener("submit", function (event) {
        event.preventDefault(); // Empêche l'envoi normal du formulaire

        const firstName = document.getElementById("firstName").value.trim();
        const lastName = document.getElementById("lastName").value.trim();
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();
        const confirmPassword = document.getElementById("confirmPassword").value.trim();
        const errorMessage = document.getElementById("passwordError");
        const passwordMinLength = 6; // Longueur minimale du mot de passe

        // Réinitialisation du message d'erreur
        errorMessage.style.display = "none";
        errorMessage.textContent = "";

        // Vérification des champs
        if (password.length < passwordMinLength) {
            errorMessage.textContent = `Le mot de passe doit contenir au moins ${passwordMinLength} caractères.`;
            errorMessage.style.display = "block";
            return;
        }

        if (password !== confirmPassword) {
            errorMessage.textContent = "Les mots de passe ne correspondent pas.";
            errorMessage.style.display = "block";
            return;
        }

        // Objet avec les données utilisateur
        const userData = {
            firstName,
            lastName,
            email,
            password
        };

        console.log("Données envoyées :", userData); // Debugging

        // Envoi des données avec fetch()
        fetch("/frontend/register.php", { // Vérifie bien que cette URL est correcte
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(userData)
        })
        .then(response => response.json())
        .then(data => {
            console.log("Réponse du serveur :", data); // Debugging
            if (data.success) {
                alert(data.message);
                window.location.href = "/frontend/connexion.html"; // Redirige vers connexion
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(error => {
            console.error("Erreur réseau :", error);
            alert("Une erreur réseau est survenue.");
        });
    });
});
