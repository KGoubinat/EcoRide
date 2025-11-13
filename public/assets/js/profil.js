document.addEventListener("DOMContentLoaded", function () {
  // Menu burger
  const menuToggle = document.getElementById("menu-toggle");
  const mobileMenu = document.getElementById("mobile-menu");
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener("click", () =>
      mobileMenu.classList.toggle("active")
    );
    document.querySelectorAll("#mobile-menu a").forEach((link) => {
      link.addEventListener("click", () =>
        mobileMenu.classList.remove("active")
      );
    });
  }

  // Helpers
  const urlFromBase = (p) => new URL(p, document.baseURI).toString();
  function setLink(li, href, label) {
    if (!li) return;
    li.textContent = ""; // clear
    const a = document.createElement("a");
    a.href = href;
    a.textContent = label;
    li.appendChild(a);
    li.style.display = ""; // reset if hidden
  }

  // Auth header (desktop + mobile)
  const authButton = document.getElementById("authButton");
  const profilButton = document.getElementById("profilButton");
  const authButtonMobile = document.getElementById("authButtonMobile");
  const profilButtonMobile = document.getElementById("profilButtonMobile");

  if (authButton && profilButton) {
    const isLoggedIn = authButton.getAttribute("data-logged-in") === "true";

    if (isLoggedIn) {
      setLink(authButton, urlFromBase("logout.php"), "Déconnexion");
      setLink(profilButton, urlFromBase("profile.php"), "Profil");

      if (authButtonMobile && profilButtonMobile) {
        setLink(authButtonMobile, urlFromBase("logout.php"), "Déconnexion");
        setLink(profilButtonMobile, urlFromBase("profile.php"), "Profil");
      }
    } else {
      setLink(authButton, urlFromBase("login.php"), "Connexion");
      if (profilButton) profilButton.style.display = "none";

      if (authButtonMobile && profilButtonMobile) {
        setLink(authButtonMobile, urlFromBase("login.php"), "Connexion");
        profilButtonMobile.style.display = "none";
      }
    }
  }
});
