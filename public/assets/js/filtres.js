// --- Filtres résultats covoiturages ---
function applyFilters() {
  const start = document.querySelector('select[name="start"]')?.value || "";
  const end = document.querySelector('select[name="end"]')?.value || "";
  const passengers = document.getElementById("passengers")?.value || "";
  const ecoloInput = document.querySelector('input[name="ecolo"]:checked');
  const ecolo = ecoloInput ? ecoloInput.value : "";
  const prix = document.querySelector('select[name="prix"]')?.value || "";
  const duree = document.querySelector('select[name="duree"]')?.value || "";
  const note = document.querySelector('select[name="note"]')?.value || "";
  const date = document.querySelector('input[name="date"]')?.value || "";

  const basePath = window.location.pathname; // on reste sur la page courante
  const params = new URLSearchParams(window.location.search);

  const setOrDelete = (key, val) => {
    if (val !== "" && val != null) params.set(key, val);
    else params.delete(key);
  };

  setOrDelete("start", start);
  setOrDelete("end", end);
  setOrDelete("passengers", passengers);
  setOrDelete("ecolo", ecolo);
  setOrDelete("prix", prix);
  setOrDelete("duree", duree);
  setOrDelete("note", note);

  const validDate = date && !isNaN(Date.parse(date));
  if (validDate) params.set("date", date);
  else params.delete("date");

  const query = params.toString();
  // on garde le même path, on met à jour les query params
  window.location.href = query ? `${basePath}?${query}` : basePath;
}

document.addEventListener("DOMContentLoaded", () => {
  // Petit helper pour résoudre proprement les chemins avec <base href="...">
  const urlFromBase = (p) => new URL(p, document.baseURI).toString();

  const fill = (suffix = "") => {
    const profil = document.getElementById("profilButton" + suffix);
    const auth = document.getElementById("authButton" + suffix);
    if (!profil || !auth) return;

    const logged =
      profil.dataset.loggedIn === "true" && auth.dataset.loggedIn === "true";

    if (logged) {
      profil.innerHTML = `<a href="${urlFromBase(
        "profil.php"
      )}">Mon profil</a>`;
      auth.innerHTML = `<a href="${urlFromBase("logout.php")}">Déconnexion</a>`;
    } else {
      // lien connexion avec redirect vers la page actuelle
      const redirect = encodeURIComponent(
        window.location.pathname + window.location.search
      );
      profil.innerHTML = `<a href="${urlFromBase(
        "connexion.php"
      )}?redirect=${redirect}">Connexion</a>`;
      auth.innerHTML = `<a href="${urlFromBase(
        "register.php"
      )}">Inscription</a>`;
    }
  };

  fill("");
  fill("Mobile");
});
