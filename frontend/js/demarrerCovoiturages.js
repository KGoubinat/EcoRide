(function () {
  // --- Annuler un covoiturage ---
  const cancelButtons = document.querySelectorAll(".cancel-ride-button");
  const modal = document.getElementById("cancel-modal");
  const okBtn = document.getElementById("ride-cancel-confirm");
  const noBtn = document.getElementById("ride-cancel-cancel");
  const xBtn = document.getElementById("ride-cancel-close");
  let pendingForm = null;

  cancelButtons.forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      pendingForm = btn.closest("form");
      if (!modal || !okBtn) {
        pendingForm?.submit();
        return;
      }
      modal.classList.add("show");
    });
  });

  function hideRideModal() {
    modal?.classList.remove("show");
  }

  okBtn?.addEventListener("click", () => {
    pendingForm?.submit();
    hideRideModal();
  });
  noBtn?.addEventListener("click", hideRideModal);
  xBtn?.addEventListener("click", hideRideModal);
  window.addEventListener("click", (e) => {
    if (e.target === modal) hideRideModal();
  });

  // --- startTrip / endTrip (inchangé, juste là pour centraliser) ---
  window.startTrip = function startTrip(rideId) {
    const fd = new FormData();
    fd.append("covoiturage_id", rideId);
    const csrf =
      document.querySelector('input[name="csrf_token"]')?.value || "";
    if (csrf) fd.append("csrf_token", csrf);

    fetch("demarrer_covoiturage.php", { method: "POST", body: fd })
      .then((r) =>
        r
          .json()
          .catch(() => ({}))
          .then((d) => ({ ok: r.ok, status: r.status, data: d }))
      )
      .then(({ ok, status, data }) => {
        if (
          ok &&
          (data.status === "success" || (status >= 200 && status < 300))
        ) {
          document
            .getElementById(`start-trip-${rideId}`)
            ?.style.setProperty("display", "none");
          document
            .getElementById(`end-trip-${rideId}`)
            ?.style.setProperty("display", "inline-block");
          // Masquer le bouton "Annuler" dès que ça démarre
          document
            .querySelector(`#cancel-ride-form-${rideId}`)
            ?.classList.add("hidden");
          location.reload();
        } else {
          alert(data?.message || `Erreur HTTP: ${status}`);
          console.error("Réponse:", data);
        }
      })
      .catch((err) => {
        alert("Erreur réseau");
        console.error(err);
      });
  };

  window.endTrip = function endTrip(rideId) {
    const fd = new FormData();
    fd.append("covoiturage_id", rideId);
    const csrf =
      document.querySelector('input[name="csrf_token"]')?.value || "";
    if (csrf) fd.append("csrf_token", csrf);

    fetch("terminer_covoiturage.php", { method: "POST", body: fd })
      .then((r) =>
        r
          .json()
          .catch(() => ({}))
          .then((d) => ({ ok: r.ok, status: r.status, data: d }))
      )
      .then(({ ok, status, data }) => {
        if (
          ok &&
          (data.status === "success" || (status >= 200 && status < 300))
        ) {
          document
            .getElementById(`end-trip-${rideId}`)
            ?.style.setProperty("display", "none");
          location.reload();
        } else {
          alert(data?.message || `Erreur HTTP: ${status}`);
          console.error("Réponse:", data);
        }
      })
      .catch((err) => {
        alert("Erreur réseau");
        console.error(err);
      });
  };
})();
