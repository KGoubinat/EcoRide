<?php
declare(strict_types=1);

require __DIR__ . '/init.php'; // session_start + BASE_URL + getPDO()

header('X-Robots-Tag: noindex, nofollow', true);


// Autorisation : admin uniquement
if (($_SESSION['user_role'] ?? null) !== 'administrateur') {
    header('Location: ' . BASE_URL . 'admin_dashboard.php?error=forbidden');
    exit;
}

// Méthode requise
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . BASE_URL . 'add_employee.html?error=method');
    exit;
}

// Récupération / validation
$lastName  = trim($_POST['lastName']  ?? '');
$firstName = trim($_POST['firstName'] ?? '');
$email     = trim($_POST['email']     ?? '');
$pwdPlain  = (string)($_POST['password'] ?? '');

$errs = [];
if ($lastName === '')  $errs[] = 'nom';
if ($firstName === '') $errs[] = 'prenom';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'email';
if ($pwdPlain === '' || strlen($pwdPlain) < 6) $errs[] = 'password';

if ($errs) {
    header('Location: ' . BASE_URL . 'add_employee.html?error=invalid&fields=' . urlencode(implode(',', $errs)));
    exit;
}

// Hash du mot de passe
$pwdHash = password_hash($pwdPlain, PASSWORD_DEFAULT);

// Insertion
$pdo = function_exists('getPDO') ? getPDO() : ($pdo ?? null);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "INSERT INTO users (lastName, firstName, email, password, role)
        VALUES (:lastName, :firstName, :email, :password, :role)";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':lastName'  => $lastName,
        ':firstName' => $firstName,
        ':email'     => $email,
        ':password'  => $pwdHash,
        ':role'      => 'employe',
    ]);

    // Succès → retour à la gestion des employés (ou dashboard)
    header('Location: ' . BASE_URL . 'manage_employees.php?success=1');
    exit;

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        // Conflit (email déjà utilisé)
        header('Location: ' . BASE_URL . 'add_employee.html?error=email');
    } else {
        header('Location: ' . BASE_URL . 'add_employee.html?error=server');
        // error_log($e->getMessage());
    }
    exit;
}
