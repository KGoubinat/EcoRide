document.addEventListener('DOMContentLoaded', () => {
  function sendTripAction(rideId, action) {
    return fetch('https://ecoride-covoiturage-app-fe35411c6ec7.herokuapp.com/frontend/demarrer_covoiturage.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: rideId, type: 'covoiturage', action })
    })
    .then(response => {
      if (!response.ok) throw new Error(response.statusText);
      return response.json();
    });
  }

  window.startTrip = function(rideId) {
    sendTripAction(rideId, 'start')
      .then(data => {
        if (data.success) {
          document.getElementById('start-trip-' + rideId).style.display = 'none';
          document.getElementById('end-trip-' + rideId).style.display = 'inline-block';
          // window.location.reload(); // optionnel
        } else {
          alert('Erreur: ' + data.message);
        }
      })
      .catch(error => {
        console.error(error);
        alert('Erreur lors de la requête');
      });
  };

  window.endTrip = function(rideId) {
    sendTripAction(rideId, 'end')
      .then(data => {
        if (data.success) {
          document.getElementById('end-trip-' + rideId).style.display = 'none';
          alert('Covoiturage terminé !');
          return fetch('/frontend/arreter_covoiturage.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
          });
        } else {
          alert('Erreur: ' + data.message);
          throw new Error(data.message);
        }
      })
      .then(response => response.json())
      .then(emailData => {
        if (emailData.success) {
          alert('Emails envoyés aux participants.');
        } else {
          alert('Erreur lors de l’envoi des emails.');
        }
      })
      .catch(error => {
        console.error(error);
        alert('Erreur lors de la requête.');
      });
  };

  function checkRideStatus(rideId) {
    fetch('/frontend/check_ride_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ covoiturageId: rideId })
    })
    .then(response => {
      if (!response.ok) throw new Error(response.statusText);
      return response.json();
    })
    .then(data => {
      if (data.success && data.status === 'en cours') {
        document.getElementById('start-trip-' + rideId).style.display = 'none';
        document.getElementById('end-trip-' + rideId).style.display = 'inline-block';
      }
    })
    .catch(console.error);
  }

  // Exemple : appel de checkRideStatus sur tous les covoiturages visibles
  document.querySelectorAll('.ride').forEach(ride => {
    const rideId = ride.dataset.rideId;
    checkRideStatus(rideId);
  });
});
