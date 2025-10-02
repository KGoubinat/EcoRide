document.addEventListener("DOMContentLoaded", function () {
  const voyageForm = document.getElementById("voyageForm");

  if (voyageForm) {
    voyageForm.addEventListener("submit", function (event) {
      event.preventDefault();
      let isValid = true;

      const depart = document.getElementById("depart").value.trim();
      const destination = document.getElementById("destination").value.trim();
      const placeRestantes = document
        .getElementById("places_restantes")
        .value.trim();
      const date = document.getElementById("date").value;
      const heureDepart = document.getElementById("heure_depart").value.trim();
      const duree = document.getElementById("duree").value.trim();
      const prix = document.getElementById("prix").value.trim();
      const vehiculeId = document.getElementById("vehicule").value;

      const isValidTimeFormat = (v) =>
        /^([01]?[0-9]|2[0-3]):([0-5]?[0-9])$/.test(v);

      if (
        !depart ||
        !destination ||
        !placeRestantes ||
        !date ||
        !heureDepart ||
        !duree ||
        !prix ||
        !vehiculeId
      ) {
        alert("Tous les champs doivent être remplis.");
        isValid = false;
      }
      if (!isValidTimeFormat(heureDepart)) {
        alert("L'heure de départ doit être au format HH:MM.");
        isValid = false;
      }
      if (!isValidTimeFormat(duree)) {
        alert("La durée doit être au format HH:MM.");
        isValid = false;
      }
      if (isNaN(placeRestantes) || placeRestantes <= 0) {
        alert("Le nombre de places restantes doit être positif.");
        isValid = false;
      }
      if (isNaN(prix) || prix <= 0) {
        alert("Le prix doit être un nombre positif.");
        isValid = false;
      }

      if (isValid) {
        showModal(
          "Êtes-vous sûr de vouloir soumettre ce voyage ?\n       2 Credits seront retirés de votre solde",
          function () {
            submitForm(
              depart,
              destination,
              placeRestantes,
              date,
              heureDepart,
              duree,
              prix,
              vehiculeId
            );
          }
        );
      }
    });
  }

  function showModal(message, onConfirm) {
    const modal = document.getElementById("travel-confirmation-modal");
    const modalMessage = document.getElementById("confirmation-message");
    const confirmButton = document.getElementById("modal-travel-confirm");
    const cancelButton = document.getElementById("modal-travel-cancel");

    if (!modal || !modalMessage || !confirmButton || !cancelButton) {
      console.error("Erreur: Élément(s) de la modale introuvable(s).");
      return;
    }

    modalMessage.textContent = message;
    modal.classList.add("show");

    const onOk = () => {
      onConfirm();
      modal.classList.remove("show");
      cleanup();
    };
    const onCancel = () => {
      modal.classList.remove("show");
      cleanup();
    };

    function cleanup() {
      confirmButton.removeEventListener("click", onOk);
      cancelButton.removeEventListener("click", onCancel);
      window.removeEventListener("click", onWindow);
    }
    function onWindow(e) {
      if (e.target === modal) onCancel();
    }

    confirmButton.addEventListener("click", onOk);
    cancelButton.addEventListener("click", onCancel);
    window.addEventListener("click", onWindow);
  }

  function submitForm(
    depart,
    destination,
    placeRestantes,
    date,
    heureDepart,
    duree,
    prix,
    vehiculeId
  ) {
    const formData = new FormData();
    formData.append("depart", depart);
    formData.append("destination", destination);
    formData.append("places_restantes", placeRestantes);
    formData.append("date", date);
    formData.append("heure_depart", heureDepart);
    formData.append("duree", duree);
    formData.append("prix", prix);
    formData.append("vehicule_id", vehiculeId);

    const url = new URL("ajoutCovoiturages.php", document.baseURI).toString();

    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);

    xhr.onload = function () {
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const response = JSON.parse(xhr.responseText);
          if (response.status === "success") {
            showSuccessModal("Votre voyage a été ajouté avec succès !");
          } else {
            alert("Erreur: " + (response.message || "Échec"));
          }
        } catch (error) {
          console.error("Erreur de parsing:", error, xhr.responseText);
          alert("Erreur lors du traitement de la réponse.");
        }
      } else {
        alert("Erreur HTTP: " + xhr.status);
      }
    };

    xhr.send(formData);
  }

  function showSuccessModal(message) {
    const successModal = document.getElementById("travel-success-modal");
    const successMessage = document.getElementById("travel-success-message");

    if (!successModal || !successMessage) {
      console.error(
        "Erreur: L'élément de la modale de succès est introuvable."
      );
      return;
    }

    successMessage.textContent = message;
    successModal.style.display = "flex";

    const onWindow = (event) => {
      if (event.target === successModal) {
        successModal.style.display = "none";
        location.reload();
      }
    };
    window.addEventListener("click", onWindow, { once: true });
  }
});
