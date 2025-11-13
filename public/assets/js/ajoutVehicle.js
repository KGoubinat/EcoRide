document.addEventListener("DOMContentLoaded", function () {
  // Liens Connexion/Profil (CSP safe : pas d'inline handler)
  const authButton = document.getElementById("authButton");
  const profilButton = document.getElementById("profilButton");
  if (authButton && profilButton) {
    const isLoggedIn = authButton.getAttribute("data-logged-in") === "true";
    const setLink = (li, href, label) => {
      const a = document.createElement("a");
      a.href = href;
      a.textContent = label;
      li.innerHTML = "";
      li.appendChild(a);
    };
    if (isLoggedIn) {
      setLink(authButton, "logout.php", "Déconnexion");
      setLink(profilButton, "profil.php", "Profil");
    } else {
      setLink(authButton, "connexion.php", "Connexion");
      profilButton.style.display = "none";
    }
  }

  const vehicleForm = document.getElementById("vehicleForm");
  const successModal = document.getElementById("successModal");
  const closeSuccessModal = document.getElementById("closeSuccessModal");
  if (!vehicleForm) return;

  vehicleForm.addEventListener("submit", function (event) {
    event.preventDefault();

    // Champs
    const marque = (document.getElementById("marque")?.value || "").trim();
    const modele = (document.getElementById("modele")?.value || "").trim();
    const plaque = (
      document.getElementById("plaque_immatriculation")?.value || ""
    ).trim();
    const dateImm = (
      document.getElementById("date_1ere_immat")?.value || ""
    ).trim();
    const energie = (document.getElementById("energie")?.value || "").trim();
    const nbPlacesStr = (
      document.getElementById("nb_places_disponibles")?.value || ""
    ).trim();

    const preferences = (
      document.getElementById("preferences")?.value || ""
    ).trim();
    const fumeur = document.getElementById("fumeur")?.checked ? 1 : 0;
    const animal = document.getElementById("animal")?.checked ? 1 : 0;

    // CSRF
    const csrf =
      vehicleForm.querySelector('input[name="csrf_token"]')?.value || "";

    // Validations client (cohérentes avec le serveur)
    const errors = [];
    if (!marque || !modele || !plaque || !dateImm || !energie || !nbPlacesStr) {
      errors.push("Tous les champs obligatoires doivent être remplis.");
    }
    const rePlaque = /^[A-Z]{2}\s?\d{3}\s?[A-Z]{2}$/;
    if (plaque && !rePlaque.test(plaque)) {
      errors.push("Plaque invalide (format attendu : AB 123 CD).");
    }
    const nbPlaces = Number.parseInt(nbPlacesStr, 10);
    if (!Number.isFinite(nbPlaces) || nbPlaces <= 0) {
      errors.push("Le nombre de places doit être un entier positif.");
    }

    if (errors.length) {
      alert(errors.join("\n"));
      return;
    }

    // Payload
    const formData = new FormData(vehicleForm);
    formData.set("fumeur", String(fumeur));
    formData.set("animal", String(animal));
    formData.set("preferences", preferences);
    if (csrf) formData.set("csrf_token", csrf);

    // URL : on respecte l'attribut action du form (fallback si absent)
    const actionAttr =
      vehicleForm.getAttribute("action") ||
      "../backend/handlers/ajouter_vehicule.php";
    const url = new URL(actionAttr, document.baseURI).toString();

    const xhr = new XMLHttpRequest();
    xhr.open("POST", url, true);

    xhr.onload = function () {
      let data = {};
      try {
        data = JSON.parse(xhr.responseText || "{}");
      } catch {}

      if (xhr.status === 200 || xhr.status === 201) {
        if (data.status === "success") {
          if (successModal) successModal.style.display = "flex";
          vehicleForm.reset();
        } else {
          alert(data.message || "Erreur lors de l'ajout du véhicule.");
        }
        return;
      }

      // Erreurs côté serveur
      if (xhr.status === 422 && data?.fields) {
        const fieldMsgs = Object.entries(data.fields).map(
          ([k, v]) => `- ${k} : ${v}`
        );
        alert(
          (data.message || "Validation échouée.") + "\n" + fieldMsgs.join("\n")
        );
      } else if (xhr.status === 401) {
        alert("Vous devez être connecté.");
        // Optionnel : window.location.href = "connexion.php?redirect=" + encodeURIComponent(location.pathname);
      } else if (xhr.status === 403) {
        alert(data.message || "Action non autorisée.");
      } else if (xhr.status === 404) {
        alert(data.message || "Ressource introuvable.");
      } else {
        alert(data.message || "Erreur HTTP: " + xhr.status);
      }
    };

    xhr.onerror = function () {
      alert("Erreur réseau.");
    };

    xhr.send(formData);
  });

  if (closeSuccessModal && successModal) {
    const hide = () => {
      successModal.style.display = "none";
      location.reload();
    };
    closeSuccessModal.addEventListener("click", hide);
    window.addEventListener("click", (e) => {
      if (e.target === successModal) hide();
    });
  }
});
