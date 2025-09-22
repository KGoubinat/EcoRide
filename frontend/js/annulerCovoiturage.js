// --- Annuler un covoiturage (avec modale custom) ---
(function () {
  const cancelButtons = document.querySelectorAll(".cancel-ride-button");
  const cancelModal = document.getElementById("cancel-modal");
  const modalCancelConfirm =
    cancelModal?.querySelector('[data-role="confirm"]') ||
    document.getElementById("modal-cancel-confirm");
  const modalCancelCancel =
    cancelModal?.querySelector('[data-role="cancel"]') ||
    document.getElementById("modal-cancel-cancel");
  const closeModalButton = cancelModal?.querySelector(".close-btn");

  let pendingForm = null;

  if (
    !cancelButtons.length ||
    !cancelModal ||
    !modalCancelConfirm ||
    !modalCancelCancel
  )
    return;

  cancelButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      e.preventDefault();
      const form = button.closest("form");
      if (!form) return;

      const hiddenId = form.querySelector('input[name="covoiturage_id"]');
      if (!hiddenId || !hiddenId.value) {
        console.error("covoiturage_id manquant dans le formulaire.");
        return;
      }
      const csrf = form.querySelector('input[name="csrf_token"]');
      if (!csrf || !csrf.value) {
        console.error("csrf_token manquant");
        return;
      }

      pendingForm = form;
      cancelModal.style.display = "flex";
    });
  });

  modalCancelConfirm.addEventListener("click", () => {
    if (pendingForm) {
      cancelModal.style.display = "none";
      pendingForm.submit(); // POST + CSRF
      pendingForm = null;
    }
  });

  const closeModal = () => {
    cancelModal.style.display = "none";
    pendingForm = null;
  };
  modalCancelCancel.addEventListener("click", closeModal);
  closeModalButton?.addEventListener("click", closeModal);
  window.addEventListener("click", (e) => {
    if (e.target === cancelModal) closeModal();
  });
})();
