document.addEventListener("DOMContentLoaded", function () {
  // Menu burger
  const menuToggle = document.getElementById("menu-toggle");
  const mobileMenu = document.getElementById("mobile-menu");
  if (menuToggle && mobileMenu) {
    menuToggle.addEventListener("click", () =>
      mobileMenu.classList.toggle("active")
    );
    document
      .querySelectorAll("#mobile-menu a")
      .forEach((link) =>
        link.addEventListener("click", () =>
          mobileMenu.classList.remove("active")
        )
      );
  }

  // Remplissage Connexion/Profil si tu utilises les data-logged-in
  const mountAuth = (suffix = "") => {
    const profil = document.getElementById("profilButton" + suffix);
    const auth = document.getElementById("authButton" + suffix);
    if (!profil || !auth) return;
    const logged =
      profil.dataset.loggedIn === "true" || auth.dataset.loggedIn === "true";
    if (logged) {
      profil.innerHTML = '<a href="profil.php">Profil</a>';
      auth.innerHTML = '<a href="deconnexion.php">Déconnexion</a>';
    } else {
      const redirect = encodeURIComponent(
        window.location.pathname + window.location.search
      );
      profil.innerHTML = `<a href="connexion.html?redirect=${redirect}">Connexion</a>`;
      auth.innerHTML = '<a href="register.php">Inscription</a>';
    }
  };
  mountAuth("");
  mountAuth("Mobile");

  // Form
  const form = document.getElementById("signupForm");
  if (!form) return;

  const firstName = document.getElementById("firstName");
  const lastName = document.getElementById("lastName");
  const email = document.getElementById("email");
  const password = document.getElementById("password");
  const confirm = document.getElementById("confirmPassword");
  const errorBox = document.getElementById("passwordError");

  const pwdMin = 8; // cohérent avec le serveur

  form.addEventListener("submit", function (event) {
    event.preventDefault();
    errorBox.style.display = "none";
    errorBox.textContent = "";

    const pwd = (password.value || "").trim();
    const pwd2 = (confirm.value || "").trim();

    if (pwd.length < pwdMin) {
      errorBox.textContent = `Le mot de passe doit contenir au moins ${pwdMin} caractères.`;
      errorBox.style.display = "block";
      return;
    }
    if (pwd !== pwd2) {
      errorBox.textContent = "Les mots de passe ne correspondent pas.";
      errorBox.style.display = "block";
      return;
    }

    // Appel API backend (relatif à <base href=".../frontend/">)
    const apiUrl = new URL(
      "../backend/register.php",
      document.baseURI
    ).toString();

    fetch(apiUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        firstName: firstName.value.trim(),
        lastName: lastName.value.trim(),
        email: email.value.trim(),
        password: pwd,
      }),
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          alert(data.message || "Inscription réussie.");
          // Redirection vers le Profil
          window.location.href = "profil.php";
        } else {
          alert("Erreur : " + (data.message || "Inscription impossible"));
        }
      })
      .catch(() => alert("Une erreur réseau est survenue."));
  });
});
