document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("btnParticiper");
  if (!btn) return;

  // Modale
  const modal = document.getElementById("participation-modal");
  const closeX = document.getElementById("participation-close");
  const okBtn = document.getElementById("participation-confirm");
  const koBtn = document.getElementById("participation-cancel");
  const textEl = document.getElementById("participation-text");

  // Sécurité: si pas de modale, fallback confirm() (mais on veut éviter ça)
  const hasModal = modal && okBtn && koBtn && textEl;

  // Données du bouton
  const id = Number(btn.dataset.id || 0);
  const csrf = btn.dataset.token || "";
  const passengers =
    Number(new URLSearchParams(location.search).get("passengers")) || 1;

  // Ouvre/ferme la modale
  const openModal = () => {
    if (!hasModal) return true; // fallback
    textEl.textContent = `Confirmer la réservation pour ${passengers} passager(s) ?`;
    modal.classList.add("active");
    return false;
  };
  const closeModal = () => {
    if (hasModal) modal.classList.remove("active");
  };

  if (hasModal) {
    [closeX, koBtn].forEach(
      (el) => el && el.addEventListener("click", closeModal)
    );
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
  }

  // Click sur Participer -> ouvrir modale
  btn.addEventListener("click", () => {
    if (!id || !csrf) {
      alert("Données manquantes (id/csrf).");
      return;
    }
    // si openModal renvoie true => pas de modale HTML trouvée, on fallback confirm
    if (openModal()) {
      if (
        confirm(`Confirmer la réservation pour ${passengers} passager(s) ?`)
      ) {
        submitParticipation();
      }
    }
  });

  // Click sur "Confirmer" de la modale -> submit
  if (hasModal) {
    okBtn.addEventListener("click", () => {
      closeModal();
      submitParticipation();
    });
  }

  function submitParticipation() {
    const oldTxt = btn.textContent;
    btn.disabled = true;
    btn.textContent = "Veuillez patienter…";

    // ⚠️ La plupart de tes handlers PHP attendent du FormData (POST), pas du JSON
    const fd = new FormData();
    fd.append("covoiturage_id", String(id));
    fd.append("csrf_token", csrf);
    fd.append("passengers", String(passengers));

    const apiUrl = new URL(
      "api/join_ride.php",
      document.baseURI
    ).toString();

    fetch(apiUrl, {
      method: "POST",
      body: fd,
      credentials: "same-origin",
      headers: { Accept: "application/json" }, // pas de Content-Type: fetch le met pour FormData
    })
      .then((r) =>
        r.text().then((t) => {
          let d = {};
          try {
            d = t ? JSON.parse(t) : {};
          } catch {}
          return { ok: r.ok, status: r.status, data: d };
        })
      )
      .then(({ ok, status, data }) => {
        if (ok && (data.success || data.status === "success")) {
          const target = new URL("profile.php", document.baseURI);
          target.searchParams.set("message", "reservation_ok");
          window.location.href = target.toString();
        } else if (status === 401) {
          const next = encodeURIComponent(location.pathname + location.search);
          window.location.href = `login.php?redirect=${next}`;
        } else {
          alert(data?.message || `Erreur HTTP: ${status}`);
          btn.disabled = false;
          btn.textContent = oldTxt;
          console.error("Réponse:", data);
        }
      })
      .catch((err) => {
        alert("Erreur réseau.");
        btn.disabled = false;
        btn.textContent = oldTxt;
        console.error(err);
      });
  }
});
