// annulerReservation.js — annulation de réservation avec modale + fallback
(function () {
  // Cible uniquement les boutons d'annulation (pas les "Supprimer")
  const buttons = document.querySelectorAll(".btn-danger[data-reservation-id]");

  // Modale (mêmes IDs que dans profile.php)
  const modal = document.getElementById("cancel-reservation-modal");
  const okBtn = document.getElementById("resv-cancel-confirm");
  const noBtn = document.getElementById("resv-cancel-cancel");
  const xBtn = document.getElementById("resv-cancel-close");

  let pendingForm = null;

  // Fallback si la modale n'existe pas (ou boutons manquants)
  const openWithFallback = (form) => {
    if (modal && okBtn && noBtn) {
      pendingForm = form;
      // utilise ton CSS .modal/.show ; sinon force display:flex
      modal.classList.add("show");
      modal.style.display = "flex";
      return;
    }
    if (
      window.confirm("Êtes-vous sûr de vouloir annuler cette réservation ?")
    ) {
      form.submit();
    }
  };

  buttons.forEach((btn) => {
    const form = btn.closest("form");
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      if (!form) return;

      // Sanity checks utiles (évite un submit vide si champs manquants)
      const idInput = form.querySelector('input[name="reservation_id"]');
      const csrf = form.querySelector('input[name="csrf_token"]');
      if (!idInput?.value) {
        console.error("reservation_id manquant.");
        return;
      }
      if (!csrf?.value) {
        console.error("csrf_token manquant.");
        return;
      }

      openWithFallback(form);
    });
  });

  const hide = () => {
    if (!modal) return;
    modal.classList.remove("show");
    modal.style.display = "none";
    pendingForm = null;
  };

  okBtn?.addEventListener("click", () => {
    pendingForm?.submit(); // POST + CSRF natif
    hide();
  });
  noBtn?.addEventListener("click", hide);
  xBtn?.addEventListener("click", hide);
  window.addEventListener("click", (e) => {
    if (e.target === modal) hide();
  });
})();
