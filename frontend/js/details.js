document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("btnParticiper");
  if (!btn) return;

  btn.addEventListener("click", async () => {
    const id = Number(btn.dataset.id || 0);
    const csrf = btn.dataset.token || "";
    const passengers =
      Number(new URLSearchParams(location.search).get("passengers")) || 1;

    if (!id || !csrf) {
      alert("Données manquantes (id/csrf).");
      return;
    }

    if (!confirm(`Confirmer la réservation pour ${passengers} passager(s) ?`)) {
      return;
    }

    const oldTxt = btn.textContent;
    btn.disabled = true;
    btn.textContent = "Veuillez patienter…";

    // URL robuste par rapport à <base href=".../frontend/">
    const apiUrl = new URL(
      "participer_covoiturage.php",
      document.baseURI
    ).toString();

    try {
      const resp = await fetch(apiUrl, {
        method: "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          "X-CSRF-Token": csrf, // + dans l’en-tête
        },
        body: JSON.stringify({ id, passengers, csrf_token: csrf }), // et dans le body
      });

      const raw = await resp.text();
      let data = null;
      try {
        data = JSON.parse(raw);
      } catch {}

      if (resp.ok && data?.success) {
        // Redirige proprement en respectant <base href="...">
        const target = new URL("profil.php", document.baseURI);
        // (optionnel) passer un message flash
        target.searchParams.set("message", "reservation_ok");
        window.location.href = target.toString();
        return;
      } else {
        console.error("Réponse brute:", raw);
        alert(data?.message || `Erreur HTTP: ${resp.status}`);
        btn.disabled = false;
        btn.textContent = oldTxt;
      }
    } catch (e) {
      console.error(e);
      alert("Erreur réseau.");
      btn.disabled = false;
      btn.textContent = oldTxt;
    }
  });
});
