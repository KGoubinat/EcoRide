document.addEventListener('DOMContentLoaded', function () {

    // Fonction pour démarrer le covoiturage
    window.startTrip = function (rideId) {
        console.log('ID du covoiturage :', rideId);
        console.log('Bouton start:', document.getElementById('start-trip-' + rideId));
        console.log('Bouton end:', document.getElementById('end-trip-' + rideId));

        console.log("Données envoyées :", { id: rideId, type: 'covoiturage', action: 'start' });

        // Envoi de la requête pour démarrer le covoiturage
        fetch('/ecoride/frontend/demarrer_covoiturage.php', {
            method: 'POST',
            body: JSON.stringify({ id: rideId, type: 'covoiturage', action: 'start' }),           
            headers: { 'Content-Type': 'application/json' }
        })


        // Récupérer la réponse brute
        .then(response => response.json())  // Récupère la réponse en JSON directement
        .then(data => {
            console.log('Données JSON reçues:', data);
            if (data.success) {
                const startBtn = document.getElementById('start-trip-' + rideId);
                const endBtn = document.getElementById('end-trip-' + rideId);
                
                if (startBtn && endBtn) {
                    startBtn.style.display = 'none';
                    endBtn.style.display = 'inline-block';
                    window.location.reload();  // Rafraîchissement de la page
                } else {
                    console.error('Les boutons ne sont pas trouvés !');
                }
            } else {
                alert('Erreur lors du démarrage du covoiturage: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la requête:', error);
            alert('Erreur lors de la requête');
        });
    }

    // Fonction pour terminer le covoiturage
window.endTrip = function (rideId) {
    fetch('/ecoride/frontend/demarrer_covoiturage.php', {
        method: 'POST',
        body: JSON.stringify({ id: rideId, type: 'covoiturage', action: 'end' }),
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => {
        // Vérifier si la réponse est correcte
        if (!response.ok) {
            throw new Error('Erreur du serveur : ' + response.statusText);
        }
        return response.json();  // Tenter de parser la réponse en JSON
    })
    .then(data => {
        console.log('Réponse brute du serveur :', data);
        
        if (data.success) {
            const endBtn = document.getElementById('end-trip-' + rideId);
            if (endBtn) {
                endBtn.style.display = 'none';
            }
            alert('Covoiturage terminé !');

            fetch('/ecoride/frontend/arreter_covoiturage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(emailResponse => emailResponse.json())  // Récupérer la réponse en JSON de l'envoi d'email
            .then(emailData => {
                console.log('Réponse de l\'envoi des emails :', emailData);
                if (emailData.success) {
                    alert('Les e-mails de validation ont été envoyés aux participants.');
                } else {
                    alert('Il y a eu une erreur lors de l\'envoi des e-mails.');
                }
                // window.location.reload()
            })
            .catch(emailError => {
                console.error('Erreur lors de l\'envoi des e-mails :', emailError);
                alert('Erreur lors de l\'envoi des e-mails.');
            });
        } else {
            alert('Erreur lors de la clôture du covoiturage: ' + data.message);
        }
    })
    .catch(error => {
        // Affichage des erreurs pour déboguer
        console.error('Erreur lors de la requête:', error);
        alert('Une erreur est survenue lors de la requête.');
    });
};


    // Fonction pour vérifier le statut du covoiturage
    function checkRideStatus(rideId) {
        fetch('/ecoride/frontend/check_ride_status.php', {
            method: 'POST',
            body: JSON.stringify({ covoiturageId: rideId }),
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())  // Récupère la réponse en JSON
        .then(data => {
            console.log('Réponse brute du serveur:', data);
            
            if (data.success && data.status === 'en cours') {
                const startBtn = document.getElementById('start-trip-' + rideId);
                const endBtn = document.getElementById('end-trip-' + rideId);
                if (startBtn && endBtn) {
                    startBtn.style.display = 'none';
                    endBtn.style.display = 'inline-block';
                }
            } else if (data.success) {
                console.log('Le covoiturage n\'est pas encore en cours.');
            } else {
                console.error('Erreur lors de la vérification du statut:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la requête:', error);
        });
    }

});
