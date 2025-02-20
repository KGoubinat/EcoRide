document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("signupForm").addEventListener("submit", function (event) {
        event.preventDefault(); // Empêche l'envoi normal du formulaire

        const firstName = document.getElementById("firstName").value.trim();
        const lastName = document.getElementById("lastName").value.trim();
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();
        const confirmPassword = document.getElementById("confirmPassword").value.trim();
        const photo = document.getElementById("photo").files[0]; // Récupère la photo

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

        // Créer un FormData pour envoyer aussi l'image, seulement si elle est sélectionnée
        const formData = new FormData();
        formData.append("firstName", firstName);
        formData.append("lastName", lastName);
        formData.append("email", email);
        formData.append("password", password);
        
        if (photo) {
            formData.append("photo", photo); // Ajoute la photo si elle est sélectionnée
        }

        // Envoi des données avec fetch()
        fetch("/frontend/register.php", { 
            method: "POST",
            body: formData // Utilisation de FormData pour l'image
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
