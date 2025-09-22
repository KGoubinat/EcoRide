document.addEventListener("DOMContentLoaded", function () {
  // Gestion du menu burger
  const menuToggle = document.getElementById("menu-toggle");
  const mobileMenu = document.getElementById("mobile-menu");

  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener("click", function () {
      mobileMenu.classList.toggle("active");
    });

    // Fermer le menu après un clic sur un lien
    document.querySelectorAll("#mobile-menu a").forEach((link) => {
      link.addEventListener("click", function () {
        mobileMenu.classList.remove("active");
      });
    });
  }

  // Gestion de l'authentification utilisateur
  const authButton = document.getElementById("authButton");
  const profilButton = document.getElementById("profilButton");
  const authButtonMobile = document.getElementById("authButtonMobile");
  const profilButtonMobile = document.getElementById("profilButtonMobile");
  const isLoggedIn = authButton.getAttribute("data-logged-in") === "true";

  function setLink(li, href, label) {
    const a = document.createElement("a");
    a.href = href;
    a.textContent = label;
    li.innerHTML = "";
    li.appendChild(a);
  }

  if (isLoggedIn) {
    setLink(authButton, "deconnexion.php", "Déconnexion");
    setLink(profilButton, "profil.php", "Profil");
    if (authButtonMobile && profilButtonMobile) {
      setLink(authButtonMobile, "deconnexion.php", "Déconnexion");
      setLink(profilButtonMobile, "profil.php", "Profil");
    }
  } else {
    setLink(authButton, "connexion.html", "Connexion");
    profilButton.style.display = "none";
    if (authButtonMobile && profilButtonMobile) {
      setLink(authButtonMobile, "connexion.html", "Connexion");
      profilButtonMobile.style.display = "none";
    }
  }

  // Validation formulaire recherche
  const rechercheForm = document.getElementById("rechercheForm");
  if (rechercheForm) {
    rechercheForm.addEventListener("submit", function (event) {
      const start = document.getElementById("start").value.trim();
      const end = document.getElementById("end").value.trim();
      const passengers = parseInt(
        document.getElementById("passengers").value,
        10
      );
      const date = document.getElementById("date").value;

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

      if (isNaN(passengers) || passengers < 1) {
        alert("Veuillez entrer un nombre valide de passagers (minimum 1).");
        event.preventDefault();
        return;
      }

      if (!date) {
        alert("Veuillez sélectionner une date.");
        event.preventDefault();
        return;
      }

      // Optionnel : vérifier que la date n'est pas dans le passé
      const selectedDate = new Date(date);
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      if (selectedDate < today) {
        alert("La date doit être aujourd'hui ou dans le futur.");
        event.preventDefault();
        return;
      }
    });
  }
});
