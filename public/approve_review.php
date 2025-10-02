<?php
// approve_review.php — aligné sur ton schéma
declare(strict_types=1);

require __DIR__ . '/init.php'; // doit démarrer la session + définir BASE_URL + getPDO()

// === helpers ===
function redirect(string $path, array $qs = []): void {
    $base = rtrim(BASE_URL, '/') . '/';
    if ($qs) {
        $path .= (strpos($path, '?') === false ? '?' : '&') . http_build_query($qs);
    }
    header('Location: ' . $base . ltrim($path, '/'));
    exit;
}

// === PDO garanti ===
if (!isset($pdo) || !($pdo instanceof PDO)) {
    if (function_exists('getPDO')) {
        $pdo = getPDO();
    } else {
        http_response_code(500);
        exit('PDO non initialisé');
    }
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// === Auth: employe ou admin ===
$role = $_SESSION['user_role'] ?? null;
if (!in_array($role, ['employe', 'administrateur'], true)) {
    redirect('accueil.php');
}

// === CSRF en session (utile pour l’auto-post) ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === Passerelle GET -> POST (compat liens existants) ===
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['id'], $_GET['status'])) {
    $id     = (int)$_GET['id'];
    $status = (string)$_GET['status'];
    $self   = htmlspecialchars($_SERVER['PHP_SELF'] ?? '/approve_review.php', ENT_QUOTES, 'UTF-8');
    $token  = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
    ?>
    <!doctype html>
    <html lang="fr">
    <head><meta charset="utf-8"><title>Validation…</title></head>
    <body>
      <form id="autopost" method="post" action="<?= $self ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="status" value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="csrf_token" value="<?= $token ?>">
        <input type="hidden" name="back" value="employee_reviews.php">
      </form>
      <script>document.getElementById('autopost').submit();</script>
      <noscript><button form="autopost" type="submit">Continuer</button></noscript>
    </body>
    </html>
    <?php
    exit;
}

// === POST + CSRF ===
$back = $_POST['back'] ?? 'employee_reviews.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect($back, ['error' => 'method']);
}
if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
    redirect($back, ['error' => 'csrf']);
}

// === Params ===
$review_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status    = (string)($_POST['status'] ?? '');
$allowed   = ['approved','rejected'];
if (!$review_id || !in_array($status, $allowed, true)) {
    redirect($back, ['error' => 'params']);
}

try {
    $pdo->beginTransaction();

    // Récupère et verrouille l'avis + email utilisateur pour insertion
    $stmt = $pdo->prepare(
        'SELECT r.id, r.user_id, r.driver_id, r.rating, r.comment, r.status, u.email AS user_email
         FROM reviews r
         LEFT JOIN users u ON u.id = r.user_id
         WHERE r.id = ?
         FOR UPDATE'
    );
    $stmt->execute([$review_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$review) {
        throw new RuntimeException('Review not found');
    }

    // Autoriser uniquement depuis "pending"
    if ($review['status'] !== 'pending') {
        $pdo->rollBack();
        redirect($back, ['info' => 'already_moderated']);
    }

    // Met à jour le statut (sans moderated_by/moderated_at)
    $moderatorId = (int)($_SESSION['user_id'] ?? 0);
    $up = $pdo->prepare(
        'UPDATE reviews
        SET status = ?, moderated_by = ?, moderated_at = NOW()
        WHERE id = ?'
    );
    $up->execute([$status, $moderatorId, $review_id]);


    // Si approuvé, insère dans avis_conducteurs avec tes colonnes existantes
    if ($status === 'approved') {
        $ins = $pdo->prepare(
            'INSERT INTO avis_conducteurs
                (conducteur_id, utilisateur_id, note, commentaire, date_avis, utilisateur_email, ride_id)
             VALUES (?, ?, ?, ?, NOW(), ?, ?)'
        );
        $ins->execute([
            (int)$review['driver_id'],           // conducteur_id
            (int)$review['user_id'],             // utilisateur_id
            (int)$review['rating'],              // note
            (string)$review['comment'],          // commentaire
            (string)($review['user_email'] ?? ''), // utilisateur_email ('' si null)
            null                                 // ride_id (on n'a pas l'info ici)
        ]);
        // Si tu veux éviter les doublons, ajoute une contrainte UNIQUE appropriée (voir note ci-dessous).
    }

    $pdo->commit();
    redirect($back, ['success' => 1]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // error_log($e->getMessage());
    redirect($back, ['error' => 1]);
}
