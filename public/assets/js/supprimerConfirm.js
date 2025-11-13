// assets/js/supprimerConfirm.js
(function () {
  function setupDeleteModal() {
    const modal = document.getElementById("delete-modal");
    const confirm = document.getElementById("delete-confirm");
    const cancel = document.getElementById("delete-cancel");
    const closeX = document.getElementById("delete-close");
    if (!modal || !confirm || !cancel) return;

    let pendingForm = null;

    function open(form) {
      pendingForm = form;
      modal.classList.add("active");
      modal.style.display = "block"; // assure l'affichage même sans CSS .active
    }
    function close() {
      modal.style.display = "none";
      modal.classList.remove("active");
      pendingForm = null;
    }

    confirm.addEventListener("click", () => {
      const f = pendingForm;
      close();
      if (f) {
        f.__confirmed = true; // <-- essentiel pour éviter la re-capture
        f.submit();
      }
    });
    [cancel, closeX].forEach(
      (btn) => btn && btn.addEventListener("click", close)
    );
    modal.addEventListener("click", (e) => {
      if (e.target === modal) close(); // clic hors contenu => fermer
    });

    // Cible tous les formulaires de SUPPRESSION
    const selectors = [
      'form[action*="supprimer_covoiturage.php"]',
      'form[action*="supprimer_reservation.php"]',
      'form[action*="supprimer_vehicule.php"]',
    ];
    document.querySelectorAll(selectors.join(",")).forEach((form) => {
      form.removeAttribute("data-confirm"); // évite window.confirm natif
      form.addEventListener("submit", (e) => {
        if (form.__confirmed) return; // laisser passer le vrai submit confirmé
        e.preventDefault();
        open(form);
      });
    });
  }

  document.addEventListener("DOMContentLoaded", setupDeleteModal);
})();
