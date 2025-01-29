// vérification des mots de passe pour l'inscription
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("signupForm").addEventListener("submit", function(event) {
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirmPassword").value;
        const errorMessage = document.getElementById("passwordError");
        const passwordMinLength = 6; // longueur minimale du mot de passe

        // Vérifie que les mots de passe correspondent
        if (password !== confirmPassword) {
            event.preventDefault(); // Empêche l'envoi du formulaire
            errorMessage.textContent = "Les mots de passe ne correspondent pas."; // Message d'erreur
            errorMessage.style.display = "block";
        } 
        // Vérifie que le mot de passe est suffisamment long
        else if (password.length < passwordMinLength) {
            event.preventDefault(); // Empêche l'envoi du formulaire
            errorMessage.textContent = `Le mot de passe doit contenir au moins ${passwordMinLength} caractères.`; 
            errorMessage.style.display = "block";
        } else {
            errorMessage.style.display = "none"; // Cache le message d'erreur si tout est ok
        }
    });
});
