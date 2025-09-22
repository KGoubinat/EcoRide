document.addEventListener("DOMContentLoaded", function () {
  // Menu burger
  const menuToggle = document.getElementById("menu-toggle");
  const mobileMenu = document.getElementById("mobile-menu");
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener("click", function () {
      mobileMenu.classList.toggle("active");
    });
    document.querySelectorAll("#mobile-menu a").forEach((link) => {
      link.addEventListener("click", function () {
        mobileMenu.classList.remove("active");
      });
    });
  }

  // Auth header (desktop + mobile)
  const authButton = document.getElementById("authButton");
  const profilButton = document.getElementById("profilButton");
  const authButtonMobile = document.getElementById("authButtonMobile");
  const profilButtonMobile = document.getElementById("profilButtonMobile");

  if (authButton && profilButton) {
    const isLoggedIn = authButton.getAttribute("data-logged-in") === "true";
    if (isLoggedIn) {
      authButton.innerHTML = '<a href="deconnexion.php">Déconnexion</a>';
      profilButton.innerHTML = '<a href="profil.php">Profil</a>';
      if (authButtonMobile && profilButtonMobile) {
        authButtonMobile.innerHTML =
          '<a href="deconnexion.php">Déconnexion</a>';
        profilButtonMobile.innerHTML = '<a href="profil.php">Profil</a>';
      }
    } else {
      authButton.innerHTML = '<a href="connexion.html">Connexion</a>';
      profilButton.style.display = "none";
      if (authButtonMobile && profilButtonMobile) {
        authButtonMobile.innerHTML = '<a href="connexion.html">Connexion</a>';
        profilButtonMobile.style.display = "none";
      }
    }
  }
});
