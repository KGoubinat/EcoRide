<?php
// Connexion à la base de données avec PDO
$dsn = 'mysql:host=localhost;dbname=ecoride';
$username = 'root';
$password = 'nouveau_mot_de_passe';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Impossible de se connecter à la base de données : " . $e->getMessage());
}

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifier si les données existent dans le tableau $_POST
    if (!isset($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['password'], $_POST['confirmPassword'])) {
        die("Tous les champs doivent être remplis.");
    }

    // Récupérer les données du formulaire
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirmPassword']);

    // Validation des données
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
        die("Tous les champs doivent être remplis.");
    }

    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("L'adresse email n'est pas valide.");
    }

    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die("Cet email est déjà utilisé.");
    }

    // Vérifier si les mots de passe correspondent
    if ($password !== $confirmPassword) {
        die("Les mots de passe ne correspondent pas.");
    }

    // Hachage du mot de passe
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insérer l'utilisateur dans la base de données
    $stmt = $pdo->prepare("INSERT INTO users (firstName, lastName, email, password) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$firstName, $lastName, $email, $hashed_password])) {
        echo "Inscription réussie ! Vous pouvez vous connecter.";
        // Rediriger vers la page de connnexion ou autre page protégée
        header("Location: connexion.html");
    } else {
        echo "Erreur lors de l'inscription.";
    }
}
?>