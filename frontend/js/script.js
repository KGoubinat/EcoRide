$(document).ready(function() {
    $('#employeeForm').on('submit', function(event) {
        event.preventDefault(); // Empêche le rechargement de la page

        const name = $('#name').val();
        const email = $('#email').val();
        const password = $('#password').val();
        const confirm_password = $('#confirm_password').val();

        // Vérification que les mots de passe correspondent
        if (password !== confirm_password) {
            $('#message').text('Les mots de passe ne correspondent pas').css('color', 'red');
            return;
        }

        // Envoi de la requête POST à l'API pour ajouter l'employé
        $.ajax({
            url: 'http://localhost:3000/create-employee', // L'URL de votre API
            method: 'POST',
            data: JSON.stringify({ name, email, password }),
            contentType: 'application/json',
            success: function(response) {
                $('#message').text(response.message).css('color', 'green');
                $('#employeeForm')[0].reset(); // Réinitialiser le formulaire
            },
            error: function(xhr) {
                $('#message').text(xhr.responseJSON.message).css('color', 'red');
            }
        });
    });
});
