document.addEventListener("DOMContentLoaded", function () {
  const authButton = document.getElementById("authButton");
  const profilButton = document.getElementById("profilButton");

  if (authButton && profilButton) {
    const isLoggedIn = authButton.getAttribute("data-logged-in") === "true";
    if (isLoggedIn) {
      authButton.innerHTML = '<a href="deconnexion.php">Déconnexion</a>';
      profilButton.innerHTML = '<a href="profil.php">Profil</a>';
    } else {
      authButton.innerHTML = '<a href="connexion.html">Connexion</a>';
      profilButton.style.display = "none";
    }
  }

  const vehicleForm = document.getElementById("vehicleForm");
  const successModal = document.getElementById("successModal");
  const closeSuccessModal = document.getElementById("closeSuccessModal");

  if (vehicleForm) {
    vehicleForm.addEventListener("submit", function (event) {
      event.preventDefault();

      const marque = document.getElementById("marque").value.trim();
      const modele = document.getElementById("modele").value.trim();
      const plaque = document
        .getElementById("plaque_immatriculation")
        .value.trim();
      const dateImmat = document.getElementById("date_1ere_immat").value.trim();
      const energie = document.getElementById("energie").value.trim();
      const nbPlaces = document
        .getElementById("nb_places_disponibles")
        .value.trim();

      const preferences = document.getElementById("preferences")
        ? document.getElementById("preferences").value.trim()
        : "";
      const fumeur = document.getElementById("fumeur").checked ? 1 : 0;
      const animal = document.getElementById("animal").checked ? 1 : 0;

      if (
        !marque ||
        !modele ||
        !plaque ||
        !dateImmat ||
        !energie ||
        !nbPlaces
      ) {
        alert("Tous les champs obligatoires doivent être remplis !");
        return;
      }

      const formData = new FormData(vehicleForm);
      formData.set("fumeur", fumeur);
      formData.set("animal", animal);
      formData.set("preferences", preferences);

      const url = new URL("ajouter_vehicule.php", document.baseURI).toString();

      const xhr = new XMLHttpRequest();
      xhr.open("POST", url, true);

      xhr.onload = function () {
        if (xhr.status === 200 || xhr.status === 201) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.status === "success") {
              if (successModal) successModal.style.display = "flex";
              vehicleForm.reset();
            } else {
              alert(response.message || "Erreur lors de l'ajout du véhicule.");
            }
          } catch {
            console.error("Réponse non JSON:", xhr.responseText);
            alert("Erreur lors du traitement de la réponse.");
          }
        } else {
          alert("Erreur lors de l'ajout du véhicule.");
        }
      };

      xhr.send(formData);
    });
  }

  if (closeSuccessModal && successModal) {
    closeSuccessModal.addEventListener("click", function () {
      successModal.style.display = "none";
      location.reload();
    });
    window.addEventListener("click", function (event) {
      if (event.target === successModal) {
        successModal.style.display = "none";
        location.reload();
      }
    });
  }
});
