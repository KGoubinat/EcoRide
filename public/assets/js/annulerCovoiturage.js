// --- Annuler un covoiturage (avec modale custom + fallback) ---
(function () {
  const cancelButtons = document.querySelectorAll(".cancel-ride-button");

  // Modale (IDs conformes à profil.php)
  const cancelModal = document.getElementById("cancel-modal");
  const btnConfirm = document.getElementById("ride-cancel-confirm");
  const btnCancel = document.getElementById("ride-cancel-cancel");
  const btnClose = document.getElementById("ride-cancel-close");

  let pendingForm = null;

  // Fallback simple si la modale n'existe pas: confirm() + submit
  const openWithFallback = (form) => {
    if (cancelModal && btnConfirm && btnCancel) {
      pendingForm = form;
      cancelModal.classList.add("show"); // utilise .show si tu as du CSS, sinon display:flex
      cancelModal.style.display = "flex";
      return;
    }
    if (window.confirm("Êtes-vous sûr de vouloir annuler ce covoiturage ?")) {
      form.submit();
    }
  };

  cancelButtons.forEach((button) => {
    button.addEventListener("click", (e) => {
      e.preventDefault();

      // On soumet le <form> le plus proche du bouton
      const form = button.closest("form");
      if (!form) return;

      // Sanity checks (CSRF + id)
      const idInput = form.querySelector('input[name="covoiturage_id"]');
      const csrf = form.querySelector('input[name="csrf_token"]');
      if (!idInput?.value) {
        console.error("covoiturage_id manquant dans le formulaire.");
        return;
      }
      if (!csrf?.value) {
        console.error("csrf_token manquant dans le formulaire.");
        return;
      }

      openWithFallback(form);
    });
  });

  // Fermer la modale
  const hideModal = () => {
    if (!cancelModal) return;
    cancelModal.classList.remove("show");
    cancelModal.style.display = "none";
    pendingForm = null;
  };

  btnConfirm?.addEventListener("click", () => {
    if (pendingForm) {
      // Post + CSRF via le formulaire natif
      pendingForm.submit();
      hideModal();
    }
  });

  btnCancel?.addEventListener("click", hideModal);
  btnClose?.addEventListener("click", hideModal);

  window.addEventListener("click", (e) => {
    if (e.target === cancelModal) hideModal();
  });
})();
