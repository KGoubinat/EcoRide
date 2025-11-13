document.addEventListener("DOMContentLoaded", function () {
  const statusForm = document.getElementById("status-form");
  if (!statusForm) return;

  statusForm.addEventListener("submit", function (e) {
    e.preventDefault();

    const status = document.getElementById("status")?.value || "";
    const csrf =
      statusForm.querySelector('input[name="csrf_token"]')?.value || "";
    const modal = document.getElementById("status-modal");

    if (!modal) {
      statusForm.submit(); // fallback
      return;
    }

    showModal("Êtes-vous sûr de vouloir mettre à jour votre statut ?", () =>
      doUpdate(status, csrf)
    );
  });

  function apiUrl(path) {

    return new URL(path, document.baseURI).toString();
  }

  async function doUpdate(newStatus, csrf) {
    const formData = new FormData();
    formData.append("status", newStatus);
    formData.append("csrf_token", csrf);

    // Important : PAS de ../ ici si ton <base href> pointe déjà vers /public/
    const url = apiUrl("../backend/handlers/update_status.php");

    try {
      const resp = await fetch(url, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      });

      const text = await resp.text();
      let data = {};
      try {
        data = text ? JSON.parse(text) : {};
      } catch {}

      if (resp.status === 401) {
        // Non connecté → rediriger vers la page de connexion avec retour
        const next = encodeURIComponent(location.pathname + location.search);
        location.href = apiUrl(`connexion.php?redirect=${next}`);
        return;
      }

      if (!resp.ok || data.status !== "success") {
        alert(data.message || `Erreur HTTP: ${resp.status}`);
        return;
      }

      const newS = data.newStatus || newStatus;

      showSuccessModal("Votre statut a été mis à jour avec succès !");
      // Met à jour le libellé affiché : choisis une cible fiable
      // 1) si tu as <h2>Statut : ...</h2> juste avant le formulaire :
      const statusTitle = statusForm.previousElementSibling;
      if (statusTitle && statusTitle.tagName === "H2") {
        statusTitle.textContent = "Statut : " + newS;
      }
      // 2) ou donne un id à ce H2 dans le HTML (ex: id="status-title") et fais :
      // document.getElementById("status-title").textContent = "Statut : " + newS;

      toggleSectionsBasedOnStatus(newS);
    } catch (err) {
      console.error(err);
      alert("Erreur réseau");
    }
  }

  function toggleSectionsBasedOnStatus(status) {
    const vehicleInfoSection = document.getElementById("vehicleForm");
    const travelFormSection = document.getElementById("voyageForm");
    const show = status !== "passager";

    if (vehicleInfoSection)
      vehicleInfoSection.style.display = show ? "block" : "none";
    if (travelFormSection)
      travelFormSection.style.display = show ? "block" : "none";
  }

  function showModal(message, onConfirm) {
    const modal = document.getElementById("status-modal");
    const modalMessage = document.getElementById("modal-message");
    const confirmButton = document.getElementById("modal-confirm");
    const cancelButton = document.getElementById("modal-cancel");

    if (!modal || !modalMessage || !confirmButton || !cancelButton) return;

    modalMessage.textContent = message;
    modal.classList.add("show");

    const onOk = () => {
      onConfirm();
      close();
    };
    const onCancel = () => close();

    function close() {
      modal.classList.remove("show");
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

  function showSuccessModal(message) {
    const successModal = document.getElementById("status-success-modal");
    const successMessage = document.getElementById("success-modal-message");
    if (!successModal || !successMessage) return;

    successMessage.textContent = message;
    successModal.style.display = "flex";

    const onWin = (event) => {
      if (event.target === successModal) {
        successModal.style.display = "none";
        window.removeEventListener("click", onWin);
        location.reload(); // si tu préfères éviter le reload, enlève cette ligne
      }
    };
    window.addEventListener("click", onWin);
  }
});
