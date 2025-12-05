<?php
// public/register.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php'; // doit définir $pdo et csrf_token()
/* config.php doit aussi démarrer la session (session_start()) */

$error = '';
$success = '';
$posted = [];

// minimal server-side settings
$MIN_PASSWORD_LENGTH = 8; // tu peux réduire à 6 si tu préfères

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // sanitize posted values
        $posted['email']     = trim((string)($_POST['email'] ?? ''));
        $posted['firstname'] = trim((string)($_POST['firstname'] ?? ''));
        $posted['lastname']  = trim((string)($_POST['lastname'] ?? ''));
        $posted['password']  = (string)($_POST['password'] ?? '');
        $posted['csrf_token'] = $_POST['csrf_token'] ?? '';

        // CSRF check
        if (!hash_equals((string)csrf_token(), (string)$posted['csrf_token'])) {
            $error = 'Requête invalide (CSRF). Rafraîchissez la page et réessayez.';
        } else {
            // basic validation
            if ($posted['email'] === '' || !filter_var($posted['email'], FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse email invalide.';
            } elseif (strlen($posted['password']) < $MIN_PASSWORD_LENGTH) {
                $error = "Le mot de passe doit contenir au moins {$MIN_PASSWORD_LENGTH} caractères.";
            } else {
                // Check if email already exists
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$posted['email']]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($exists) {
                    $error = 'Cet email est déjà utilisé. Si c\'est votre compte, utilisez la récupération de mot de passe.';
                } else {
                    // create user
                    $hash = password_hash($posted['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users (email, password, firstname, lastname, created_at) VALUES (?, ?, ?, ?, NOW())');
                    $stmt->execute([
                        $posted['email'],
                        $hash,
                        $posted['firstname'] !== '' ? $posted['firstname'] : null,
                        $posted['lastname'] !== '' ? $posted['lastname'] : null
                    ]);

                    // Option: auto-login user after registration (commented out)
                    // $userId = (int)$pdo->lastInsertId();
                    // session_regenerate_id(true);
                    // $_SESSION['user_id'] = $userId;

                    // Redirect to login with a success flag (or render success message below)
                    header('Location: ../public/login.php?registered=1');
                    exit;
                }
            }
        }
    }
} catch (Throwable $e) {
    // safe logging for dev / prod
    $post_copy = $_POST;
    if (isset($post_copy['password'])) $post_copy['password'] = '[REDACTED]';
    if (isset($post_copy['csrf_token'])) $post_copy['csrf_token'] = '[REDACTED]';
    $msg = sprintf(
        "[%s] register.php ERROR: %s in %s:%d\nPOST: %s\nTrace:\n%s\n\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        json_encode($post_copy, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
        $e->getTraceAsString()
    );
    error_log($msg);
    @mkdir(__DIR__ . '/../logs', 0755, true);
    @file_put_contents(__DIR__ . '/../logs/register_error.log', $msg, FILE_APPEND | LOCK_EX);

    $error = 'Erreur serveur. Voir logs.';
}

// helper to repopulate inputs safely
function old(string $key, array $posted): string {
    return isset($posted[$key]) ? htmlspecialchars($posted[$key], ENT_QUOTES | ENT_SUBSTITUTE) : '';
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Inscription — MonSite</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>
<?php include __DIR__.'/_navbar.php'; ?>

<main class="container">
  <section class="register-wrap">
    <div class="register-card card">
      <header class="register-header">
        <div>
          <h1>Créer un compte</h1>
          <p class="muted">Rejoignez MonFabLab — gérez vos fichiers, commandes et impressions.</p>
        </div>
        <div class="logo-badge">🖨️</div>
      </header>

      <?php if(!empty($error)): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES|ENT_SUBSTITUTE) ?></div>
      <?php endif; ?>

      <?php if(!empty($success)): ?>
        <div class="alert alert-info" role="status"><?= htmlspecialchars($success, ENT_QUOTES|ENT_SUBSTITUTE) ?></div>
      <?php endif; ?>

      <form id="registerForm" class="register-form" method="post" novalidate autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES|ENT_SUBSTITUTE) ?>">

        <div class="grid-two-col">
          <label class="form-label">Prénom
            <input name="firstname" type="text" value="<?= old('firstname', $posted) ?>">
          </label>

          <label class="form-label">Nom
            <input name="lastname" type="text" value="<?= old('lastname', $posted) ?>">
          </label>
        </div>

        <label class="form-label">Adresse email
          <input name="email" type="email" required placeholder="nom@exemple.com" value="<?= old('email', $posted) ?>">
        </label>

        <label class="form-label">Mot de passe
          <div class="password-field">
            <input name="password" id="passwordInput" type="password" required placeholder="8 caractères minimum">
            <button type="button" class="btn-ghost icon-btn" id="togglePassword" aria-label="Afficher le mot de passe">👁️</button>
          </div>
          <div id="pwStrength" class="pw-strength">
            <div class="bar"></div><div class="label muted">Force : <span id="pwLabel">—</span></div>
          </div>
        </label>

        <div class="form-row">
          <button class="btn btn-primary" id="submitBtn" type="submit">
            <span class="btn-spin" aria-hidden="true"></span> Créer mon compte
          </button>
          <a class="btn btn-ghost" href="login.php">J'ai déjà un compte</a>
        </div>
      </form>

      <footer class="auth-foot muted">En créant un compte, vous acceptez nos <a href="../terms.php">conditions</a>.</footer>
    </div>
  </section>
</main>

<script src="../assets/js/register.js" defer></script>
</body>
</html>
