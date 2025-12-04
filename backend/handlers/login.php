<?php
// login.php
declare(strict_types=1);
require __DIR__ . '/../../public/init.php'; // charge .env + BASE_URL + getPDO()

// Autoriser seulement des redirections sûres (internes)
function is_safe_redirect(string $url): bool {
    if (str_starts_with($url, '/')) return true;                // /profile.php
    if (preg_match('#^https?://#i', $url)) return false;        // bloque externes
    return !preg_match('#^[a-z]+://#i', $url);                  // bloque schémas custom
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email    = strtolower(trim((string)($_POST['email'] ?? '')));
    $pwd      = (string)($_POST['password'] ?? '');
    $redirect = urldecode((string)($_POST['redirect'] ?? ''));

    if ($email === '' || $pwd === '') {
        // message neutre pour éviter l'énumération
        header('Location: ' . BASE_URL . 'login.php?error=missing');
        exit;
    }

    try {
        $pdo = getPDO();
        $st = $pdo->prepare('
            SELECT id, firstName, lastName, email, password, role, etat
            FROM users
            WHERE email = ?
            LIMIT 1
        ');
        $st->execute([$email]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        // Réponse neutre (pas d’info si l’email existe)
        if (!$user) {
            header('Location: ' . BASE_URL . 'login.php?error=credentials');
            exit;
        }

        $hash = (string)$user['password'];
        $ok   = password_verify($pwd, $hash);

        // Fallback 1 : mot de passe stocké en clair (ancien schéma)
        if (!$ok) {
            $info = password_get_info($hash);
            if ($info['algo'] === 0 && hash_equals($hash, $pwd)) {
                $ok = true;
                // upgrade → hash moderne
                $newHash = password_hash($pwd, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, (int)$user['id']]);
            }
        }

        // Fallback 2 : ancien hash MD5
        if (!$ok && preg_match('/^[a-f0-9]{32}$/i', $hash) && hash_equals($hash, md5($pwd))) {
            $ok = true;
            $newHash = password_hash($pwd, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, (int)$user['id']]);
        }

        if (!$ok) {
            header('Location: ' . BASE_URL . 'login.php?error=credentials');
            exit;
        }

        //  Blocage si compte non actif
        // Normalise au cas où (ex: 'Suspendu'/'suspended'/'inactive')
        $etat = strtolower(trim((string)$user['etat']));
        $aliases = [
            'actif' => 'active', 'activé' => 'active', 'active' => 'active',
            'suspendu' => 'suspended', 'suspendre' => 'suspended', 'suspended' => 'suspended',
            'inactive' => 'suspended', 'inactif' => 'suspended',
        ];
        $etat = $aliases[$etat] ?? $etat;

        if ($etat !== 'active') {
            // Message neutre côté UI
            header('Location: ' . BASE_URL . 'login.php?error=inactive');
            exit;
        }

        // Rehash si l’algo par défaut a évolué
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($pwd, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, (int)$user['id']]);
        }

        // Session OK
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'] ?? 'utilisateur';
        $_SESSION['firstName']  = $user['firstName'] ?? '';
        $_SESSION['lastName']   = $user['lastName'] ?? '';
        $_SESSION['user_etat']  = 'active';

        // Redirections selon rôle / redirect demandé
        if (($user['role'] ?? '') === 'administrateur') {
            header('Location: ' . BASE_URL . 'admin_dashboard.php'); exit;
        }
        if (($user['role'] ?? '') === 'employe') {
            header('Location: ' . BASE_URL . 'employee_dashboard.php'); exit;
        }
        if ($redirect !== '' && is_safe_redirect($redirect)) {
            if (!str_starts_with($redirect, '/')) {
                header('Location: ' . BASE_URL . ltrim($redirect, '/'));
            } else {
                header('Location: ' . $redirect);
            }
            exit;
        }

        header('Location: ' . BASE_URL . 'profile.php');
        exit;

    } catch (Throwable $e) {
        http_response_code(500);
        header('Location: ' . BASE_URL . 'login.php?error=internal');
        exit;
    }
}


// si on arrive ici sans POST, renvoyer vers le formulaire
header('Location: ' . BASE_URL . 'login.php');
exit;
