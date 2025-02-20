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

        // Création d'un objet contenant les données à envoyer
        const formData = new URLSearchParams();
        formData.append("firstName", firstName);
        formData.append("lastName", lastName);
        formData.append("email", email);
        formData.append("password", password);

        // Envoi des données avec fetch()
        fetch("/frontend/register.php", { 
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: formData.toString() // Convertir les données en format URL Encoded
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.href = "/frontend/connexion.html"; // Redirige vers connexion
            } else {
                alert("Erreur : " + data.message);
            }
        })
        .catch(error => {
            alert("Une erreur réseau est survenue.");
        });
    });
});
