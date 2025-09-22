document.addEventListener("DOMContentLoaded", function () {
  const statusForm = document.getElementById("status-form");
  if (statusForm) {
    statusForm.addEventListener("submit", function (e) {
      e.preventDefault();

      const status = document.getElementById("status")?.value;
      const csrfInput = statusForm.querySelector('input[name="csrf_token"]');
      const csrf = csrfInput ? csrfInput.value : "";

      const modal = document.getElementById("status-modal");
      if (!modal) {
        // Pas de modale ? fallback: submit traditionnel
        statusForm.submit();
        return;
      }

      showModal(
        "Êtes-vous sûr de vouloir mettre à jour votre statut ?",
        function () {
          const formData = new FormData();
          formData.append("status", status);
          formData.append("csrf_token", csrf);

          const url = new URL("update_status.php", document.baseURI).toString();

          const xhr = new XMLHttpRequest();
          xhr.open("POST", url, true);

          xhr.onload = function () {
            if (xhr.status === 200) {
              try {
                const response = JSON.parse(xhr.responseText);
                if (response.status === "success") {
                  const newS = response.newStatus || status; // fallback
                  showSuccessModal(
                    "Votre statut a été mis à jour avec succès !"
                  );
                  const h2 = document.querySelector(".user-status h2");
                  if (h2) h2.textContent = "Statut : " + newS;
                  toggleSectionsBasedOnStatus(newS);
                } else {
                  alert(response.message || "Erreur lors de la mise à jour.");
                }
              } catch (error) {
                console.error("Erreur JSON", error, xhr.responseText);
                alert("Erreur lors de la mise à jour.");
              }
            } else {
              alert("Erreur lors de la mise à jour.");
            }
          };

          xhr.send(formData);
        }
      );
    });
  }

  function toggleSectionsBasedOnStatus(status) {
    const vehicleInfoSection = document.getElementById("vehicleForm");
    const travelFormSection = document.getElementById("voyageForm");

    if (status === "passager") {
      if (vehicleInfoSection) vehicleInfoSection.style.display = "none";
      if (travelFormSection) travelFormSection.style.display = "none";
    } else {
      if (vehicleInfoSection) vehicleInfoSection.style.display = "block";
      if (travelFormSection) travelFormSection.style.display = "block";
    }
  }

  function showModal(message, onConfirm) {
    const modal = document.getElementById("status-modal");
    const modalMessage = document.getElementById("modal-message");
    const confirmButton = document.getElementById("modal-confirm");
    const cancelButton = document.getElementById("modal-cancel");

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

  function showSuccessModal(message) {
    const successModal = document.getElementById("status-success-modal");
    const successMessage = document.getElementById("success-modal-message");

    if (!successModal || !successMessage) {
      console.error(
        "Erreur: L'élément de la modale de succès est introuvable."
      );
      return;
    }

    successMessage.textContent = message;
    successModal.style.display = "flex";

    window.addEventListener("click", function onWin(event) {
      if (event.target === successModal) {
        successModal.style.display = "none";
        window.removeEventListener("click", onWin);
        location.reload();
      }
    });
  }

  // Optionnel: forcer une colonne
  function forceGridUpdate() {
    const grid = document.querySelector(".adaptation");
    if (grid) {
      grid.style.gridTemplateColumns = "1fr";
      grid.style.display = "none";
      grid.offsetHeight;
      grid.style.display = "grid";
    }
  }
});
