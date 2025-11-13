document.addEventListener("DOMContentLoaded", function () {
  const voyageForm = document.getElementById("voyageForm");
  if (!voyageForm) return;

  voyageForm.addEventListener("submit", function (event) {
    event.preventDefault();

    // Récup champs
    const depart = (document.getElementById("depart")?.value || "").trim();
    const destination = (
      document.getElementById("destination")?.value || ""
    ).trim();
    const placesRestStr = (
      document.getElementById("places_restantes")?.value || ""
    ).trim();
    const date = (document.getElementById("date")?.value || "").trim();
    const heureDepart = (
      document.getElementById("heure_depart")?.value || ""
    ).trim();
    const duree = (document.getElementById("duree")?.value || "").trim();
    const prixStr = (document.getElementById("prix")?.value || "").trim();
    const vehiculeId = (
      document.getElementById("vehicule")?.value || ""
    ).trim();
    const csrf =
      voyageForm.querySelector('input[name="csrf_token"]')?.value || "";

    let isValid = true;
    const errors = [];

    const isValidTime = (v) => /^([01]?[0-9]|2[0-3]):([0-5]?[0-9])$/.test(v);

    if (
      !depart ||
      !destination ||
      !date ||
      !heureDepart ||
      !duree ||
      !vehiculeId
    ) {
      errors.push("Tous les champs obligatoires doivent être remplis.");
      isValid = false;
    }
    if (!isValidTime(heureDepart)) {
      errors.push("L'heure de départ doit être au format HH:MM.");
      isValid = false;
    }
    if (!isValidTime(duree)) {
      errors.push("La durée doit être au format HH:MM.");
      isValid = false;
    }

    const placesRestantes = Number.parseInt(placesRestStr, 10);
    if (!Number.isFinite(placesRestantes) || placesRestantes <= 0) {
      errors.push("Le nombre de places restantes doit être un entier positif.");
      isValid = false;
    }

    const prix = Number.parseFloat(prixStr);
    if (!Number.isFinite(prix) || prix < 0) {
      // Le serveur accepte >= 0 (il facture en base prix-2)
      errors.push("Le prix doit être un nombre supérieur ou égal à 0.");
      isValid = false;
    }

    if (!isValid) {
      alert(errors.join("\n"));
      return;
    }

    showModal(
      "Êtes-vous sûr de vouloir soumettre ce voyage ?\n2 crédits seront retirés de votre solde.",
      function onConfirm() {
        submitForm({
          depart,
          destination,
          placesRestantes,
          date,
          heureDepart,
          duree,
          prix,
          vehiculeId,
          csrf,
        });
      }
    );
  });

  function showModal(message, onConfirm) {
    const modal = document.getElementById("travel-confirmation-modal");
    const modalMessage = document.getElementById("confirmation-message");
    const confirmButton = document.getElementById("modal-travel-confirm");
    const cancelButton = document.getElementById("modal-travel-cancel");

    if (!modal || !modalMessage || !confirmButton || !cancelButton) {
      // Pas de modale ? fallback direct
      if (typeof onConfirm === "function") onConfirm();
      return;
    }

    modalMessage.textContent = message;
    modal.classList.add("show");

    const onOk = () => {
      onConfirm?.();
      cleanup();
    };
    const onCancel = () => {
      cleanup();
    };
    const onWindow = (e) => {
      if (e.target === modal) onCancel();
    };
    function cleanup() {
      modal.classList.remove("show");
      confirmButton.removeEventListener("click", onOk);
      cancelButton.removeEventListener("click", onCancel);
      window.removeEventListener("click", onWindow);
    }

    confirmButton.addEventListener("click", onOk);
    cancelButton.addEventListener("click", onCancel);
    window.addEventListener("click", onWindow);
  }

  function submitForm(payload) {
    const {
      depart,
      destination,
      placesRestantes,
      date,
      heureDepart,
      duree,
      prix,
      vehiculeId,
      csrf,
    } = payload;

    const formData = new FormData();
    formData.append("depart", depart);
    formData.append("destination", destination);
    formData.append("places_restantes", String(placesRestantes));
    formData.append("date", date);
    formData.append("heure_depart", heureDepart);
    formData.append("duree", duree);
    formData.append("prix", String(prix));
    formData.append("vehicule_id", vehiculeId);
    if (csrf) formData.append("csrf_token", csrf);

    // Utiliser l’URL du formulaire si définie, sinon fallback
    const actionAttr =
      voyageForm.getAttribute("action") ||
      "api/create_ride.php";
    const url = new URL(actionAttr, document.baseURI).toString();

    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);
    // Les cookies de session same-origin seront envoyés automatiquement par XHR

    xhr.onload = function () {
      let data = {};
      try {
        data = JSON.parse(xhr.responseText || "{}");
      } catch (e) {
        // pas JSON
      }

      if (xhr.status >= 200 && xhr.status < 300) {
        if (data.status === "success") {
          showSuccessModal("Votre voyage a été ajouté avec succès !");
        } else {
          alert("Erreur: " + (data.message || "Échec de l’ajout."));
        }
        return;
      }

      // Erreurs côté serveur
      if (xhr.status === 422 && data?.fields) {
        const fieldMsgs = Object.entries(data.fields).map(
          ([k, v]) => `- ${k} : ${v}`
        );
        alert(
          (data.message || "Validation échouée.") + "\n" + fieldMsgs.join("\n")
        );
      } else if (xhr.status === 401) {
        alert("Vous devez être connecté pour saisir un voyage.");
        // Optionnel : window.location.href = "login.php?redirect=" + encodeURIComponent(window.location.pathname);
      } else if (xhr.status === 403) {
        alert(data.message || "Action non autorisée.");
      } else if (xhr.status === 404) {
        alert(data.message || "Ressource introuvable.");
      } else if (xhr.status === 405) {
        alert("Méthode non supportée.");
      } else {
        alert(data.message || "Erreur HTTP: " + xhr.status);
      }
    };

    xhr.onerror = function () {
      alert("Erreur réseau.");
    };

    xhr.send(formData);
  }

  function showSuccessModal(message) {
    const successModal = document.getElementById("travel-success-modal");
    const successMessage = document.getElementById("travel-success-message");
    if (!successModal || !successMessage) {
      alert(message);
      window.location.reload();
      return;
    }

    successMessage.textContent = message;
    successModal.style.display = "flex";

    const onWindow = (event) => {
      if (event.target === successModal) {
        successModal.style.display = "none";
        window.location.reload();
      }
    };
    window.addEventListener("click", onWindow, { once: true });
  }
});
