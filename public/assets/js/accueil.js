document.addEventListener("DOMContentLoaded", function () {
  // --- Menu burger ---
  const menuToggle = document.getElementById("menu-toggle");
  const mobileMenu = document.getElementById("mobile-menu");

  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener("click", () => {
      mobileMenu.classList.toggle("active");
    });
    mobileMenu
      .querySelectorAll("a")
      .forEach((link) =>
        link.addEventListener("click", () =>
          mobileMenu.classList.remove("active")
        )
      );
  }

  // --- Auth / Profil (desktop + mobile) ---
  const authButton = document.getElementById("authButton");
  const profilButton = document.getElementById("profilButton");
  const authButtonMobile = document.getElementById("authButtonMobile");
  const profilButtonMobile = document.getElementById("profilButtonMobile");

  // déduire l'état connecté depuis n'importe quel bouton présent
  const loggedAttr =
    (authButton && authButton.getAttribute("data-logged-in")) ??
    (profilButton && profilButton.getAttribute("data-logged-in")) ??
    (authButtonMobile && authButtonMobile.getAttribute("data-logged-in")) ??
    (profilButtonMobile && profilButtonMobile.getAttribute("data-logged-in"));
  const isLoggedIn = (loggedAttr || "").toString() === "true";

  function setLink(li, href, label) {
    if (!li) return;
    // évite les accumulations
    li.textContent = "";
    const a = document.createElement("a");
    a.setAttribute("href", href);
    a.textContent = label;
    li.appendChild(a);
    // s'assurer qu'il est visible
    li.style.display = "";
  }

  if (isLoggedIn) {
    setLink(authButton, "logout.php", "Déconnexion");
    setLink(profilButton, "profil.php", "Profil");
    setLink(authButtonMobile, "logout.php", "Déconnexion");
    setLink(profilButtonMobile, "profil.php", "Profil");
  } else {
    setLink(authButton, "connexion.php", "Connexion");
    if (profilButton) profilButton.style.display = "none";
    setLink(authButtonMobile, "connexion.php", "Connexion");
    if (profilButtonMobile) profilButtonMobile.style.display = "none";
  }

  // --- Validation du formulaire de recherche (si présent) ---
  const rechercheForm = document.getElementById("rechercheForm");
  if (rechercheForm) {
    rechercheForm.addEventListener("submit", function (event) {
      const startEl = document.getElementById("start");
      const endEl = document.getElementById("end");
      const passengersEl = document.getElementById("passengers");
      const dateEl = document.getElementById("date");

      const start = (startEl?.value || "").trim();
      const end = (endEl?.value || "").trim();
      const passengers = parseInt(passengersEl?.value || "0", 10);
      const date = (dateEl?.value || "").trim();

      if (!start || !end) {
        alert("Veuillez remplir les champs Départ et Destination.");
        event.preventDefault();
        return;
      }
      if (start === end) {
        alert("Le départ et la destination doivent être différents.");
        event.preventDefault();
        return;
      }
      if (!Number.isFinite(passengers) || passengers < 1) {
        alert("Veuillez entrer un nombre valide de passagers (minimum 1).");
        event.preventDefault();
        return;
      }
      if (!date) {
        alert("Veuillez sélectionner une date.");
        event.preventDefault();
        return;
      }

      // date non passée
      const selectedDate = new Date(date + "T00:00:00");
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (selectedDate < today) {
        alert("La date doit être aujourd'hui ou dans le futur.");
        event.preventDefault();
      }
    });
  }
});
