document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("employeeForm");
  const message = document.getElementById("message");

  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirm_password").value;

    // Vérif basique
    if (!name || !email || !password || !confirm) {
      message.textContent = "Tous les champs doivent être remplis.";
      message.style.color = "red";
      return;
    }

    if (password !== confirm) {
      message.textContent = "Les mots de passe ne correspondent pas.";
      message.style.color = "red";
      return;
    }

    // URL dynamique basée sur <base href="...">
    const apiUrl = new URL(
      "../backend/handlers/create_employee.php",
      document.baseURI
    ).toString();

    try {
      const response = await fetch(apiUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ name, email, password }),
      });

      const text = await response.text();
      let data = {};
      try {
        data = JSON.parse(text);
      } catch {
        console.error("Réponse non JSON:", text);
      }

      if (response.ok && (data.status === "success" || data.success)) {
        message.textContent = data.message || "Employé ajouté avec succès.";
        message.style.color = "green";
        form.reset();
      } else {
        message.textContent = data.message || `Erreur (${response.status})`;
        message.style.color = "red";
      }
    } catch (err) {
      console.error("Erreur réseau:", err);
      message.textContent = "Erreur réseau, veuillez réessayer.";
      message.style.color = "red";
    }
  });
});
