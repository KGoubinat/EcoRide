// assets/js/demarrerCovoiturages.js
(function () {
  function apiUrl(path) {
    return new URL(path, document.baseURI).toString();
  }
  function getCsrf() {
    return document.querySelector('input[name="csrf_token"]')?.value || "";
  }
  function handleResponse(r) {
    return r.text().then((t) => {
      let d = {};
      try {
        d = t ? JSON.parse(t) : {};
      } catch {}
      return { ok: r.ok, status: r.status, data: d };
    });
  }
  function handleAuthRedirect(status) {
    if (status === 401) {
      const next = encodeURIComponent(location.pathname + location.search);
      location.href = apiUrl(`login.php?redirect=${next}`);
      return true;
    }
    return false;
  }

  // <<< IMPORTANT : on expose ces fonctions globalement, car profile.php les appelle >>>
  window.startTrip = function startTrip(rideId) {
    const fd = new FormData();
    fd.append("covoiturage_id", rideId);
    const csrf = getCsrf();
    if (csrf) fd.append("csrf_token", csrf);

    fetch(apiUrl("api/demarrer_covoiturage.php"), {
      method: "POST",
      body: fd,
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })
      .then(handleResponse)
      .then(({ ok, status, data }) => {
        if (handleAuthRedirect(status)) return;
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
    const csrf = getCsrf();
    if (csrf) fd.append("csrf_token", csrf);

    fetch(apiUrl("api/terminer_covoiturage.php"), {
      method: "POST",
      body: fd,
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })
      .then(handleResponse)
      .then(({ ok, status, data }) => {
        if (handleAuthRedirect(status)) return;
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
