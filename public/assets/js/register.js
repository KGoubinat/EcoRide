document.addEventListener("DOMContentLoaded", function () {
  // --- Menu burger
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

  // --- Helpers
  const urlFromBase = (p) => new URL(p, document.baseURI).toString();
  const setLink = (li, href, label) => {
    if (!li) return;
    li.textContent = "";
    const a = document.createElement("a");
    a.href = href;
    a.textContent = label;
    li.appendChild(a);
    li.style.display = ""; // au cas où il aurait été "none"
  };

  // --- Remplissage Connexion/Profil (desktop + mobile)
  function mountAuth(suffix = "") {
    const profil = document.getElementById("profilButton" + suffix);
    const auth = document.getElementById("authButton" + suffix);
    if (!profil || !auth) return;

    // Les deux flags doivent être "true" (cohérent avec tes autres pages)
    const logged =
      profil.dataset.loggedIn === "true" && auth.dataset.loggedIn === "true";

    if (logged) {
      setLink(profil, urlFromBase("profile.php"), "Profil");
      setLink(auth, urlFromBase("logout.php"), "Déconnexion");
    } else {
      const redirect = encodeURIComponent(
        window.location.pathname + window.location.search
      );
      setLink(
        profil,
        urlFromBase(`login.php?redirect=${redirect}`),
        "Connexion"
      );
      setLink(auth, urlFromBase("register.php"), "Inscription");
    }
  }
  mountAuth(""); // desktop
  mountAuth("Mobile"); // mobile

  // --- Formulaire d'inscription
  const form = document.getElementById("signupForm");
  if (!form) return;

  const firstName = document.getElementById("firstName");
  const lastName = document.getElementById("lastName");
  const email = document.getElementById("email");
  const password = document.getElementById("password");
  const confirm = document.getElementById("confirmPassword");
  const errorBox = document.getElementById("passwordError");
  const pwdMin = 8;

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

    const apiUrl = urlFromBase("../backend/register.php"); // ajuste si ton endpoint diffère

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
      credentials: "same-origin",
    })
      .then(async (r) => {
        const text = await r.text();
        let data = {};
        try {
          data = text ? JSON.parse(text) : {};
        } catch {}
        return { ok: r.ok, status: r.status, data, raw: text };
      })
      .then(({ ok, status, data, raw }) => {
        if (ok && (data.success || data.status === "success")) {
          // Redirection respectant <base>
          window.location.href = urlFromBase("profile.php");
        } else {
          console.error("Réponse brute:", raw);
          alert(data?.message || `Erreur HTTP: ${status}`);
        }
      })
      .catch(() => alert("Une erreur réseau est survenue."));
  });
});
