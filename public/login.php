<?php
// public/login.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// -- simple throttling config (in memory, session-based) --
$MAX_ATTEMPTS = 6;
$LOCK_SECONDS = 300; // 5 minutes

// Helper: generate or get CSRF token
/*function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}*/

// init
$error = '';
$info = '';

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // basic throttling per session
    $_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
    $_SESSION['first_attempt_at'] = $_SESSION['first_attempt_at'] ?? time();

    // apply lock if exceeded
    if ($_SESSION['login_attempts'] >= $MAX_ATTEMPTS && (time() - $_SESSION['first_attempt_at']) < $LOCK_SECONDS) {
        $error = 'Trop de tentatives. Réessayez dans quelques minutes.';
    } else {
        // CSRF check
        $posted_csrf = $_POST['csrf_token'] ?? '';
        if (!hash_equals((string)csrf_token(), (string)$posted_csrf)) {
            $error = 'Requête invalide (CSRF). Rafraîchissez la page et réessayez.';
        } else {
            // sanitize inputs
            $email = trim((string)($_POST['email'] ?? ''));
            $pass  = (string)($_POST['password'] ?? '');
            $remember = !empty($_POST['remember']);

            if ($email === '' || $pass === '') {
                $error = 'Veuillez renseigner email et mot de passe.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email invalide.';
            } else {
                try {
                    $stmt = $pdo->prepare('SELECT id, password, active, email_verified FROM users WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $u = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($u && password_verify($pass, (string)$u['password'])) {
                        // optional: check active flag
                        if (isset($u['active']) && (int)$u['active'] === 0) {
                            $error = 'Compte désactivé. Contactez l\'administration.';
                        } else {
                            // successful login: reset attempts, set session
                            $_SESSION['login_attempts'] = 0;
                            $_SESSION['first_attempt_at'] = time();
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $u['id'];

                            // "remember me" cookie (simple): create token in DB recommended (here minimal)
                            if ($remember) {
                                $token = bin2hex(random_bytes(32));
                                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                                // TODO: persist token hash in DB and associate with user (secure implementation)
                            }

                            header('Location: ../public/dashboard.php');
                            exit;
                        }
                    } else {
                        // failed login: increment attempts
                        $_SESSION['login_attempts']++;
                        if ($_SESSION['login_attempts'] === 1) $_SESSION['first_attempt_at'] = time();
                        $error = 'Identifiants incorrects.';
                    }
                } catch (Throwable $e) {
                    error_log('login.php DB error: ' . $e->getMessage());
                    $error = 'Erreur serveur. Voir logs.';
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Connexion — Tryxee 3D</title>

  <!-- ton CSS global -->
  <link rel="stylesheet" href="../assets/css/styles.css">
  <!-- login-specific styles -->
  <link rel="stylesheet" href="../assets/css/login.css">
  <!-- Google font option (facultatif) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
</head>
<body>
<?php include __DIR__.'/_navbar.php'; ?>

<main class="container">
  <section class="login-wrap">
    <div class="auth-visual" aria-hidden="true">
      <!-- decorative animated SVG mockup -->
      <svg class="auth-svg" viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice">
        <defs>
          <linearGradient id="g1" x1="0" x2="1">
            <stop offset="0" stop-color="#4dd0e1" stop-opacity="0.18"/>
            <stop offset="1" stop-color="#7ce7ff" stop-opacity="0.06"/>
          </linearGradient>
          <filter id="f1" x="-20%" y="-20%" width="140%" height="140%">
            <feGaussianBlur stdDeviation="24" result="b"/>
            <feComposite in="SourceGraphic" in2="b" operator="over"/>
          </filter>
        </defs>
        <g filter="url(#f1)">
          <rect x="40" y="30" rx="40" ry="40" width="720" height="520" fill="url(#g1)"></rect>
          <g class="ribbon" transform="translate(80,120)">
            <path class="ribbon-path" d="M0 60 C120 0, 320 120, 400 100 C520 80, 640 200, 720 40" stroke="#7ce7ff" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
          </g>
        </g>
      </svg>
    </div>

    <div class="auth-card card">
      <div class="auth-header">
        <div class="brand">
          <span class="logo-badge" aria-hidden="true">🖨️</span>
          <strong>Mon<span class="lastname">FabLab</span></strong>
        </div>
        <p class="muted">Connectez-vous pour accéder à votre espace — gestion des fichiers & commandes.</p>
      </div>

      <?php if(!empty($error)): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES|ENT_SUBSTITUTE) ?></div>
      <?php endif; ?>

      <?php if(!empty($info)): ?>
        <div class="alert alert-info" role="status"><?= htmlspecialchars($info, ENT_QUOTES|ENT_SUBSTITUTE) ?></div>
      <?php endif; ?>

      <form class="auth-form" method="post" novalidate autocomplete="on" id="loginForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES|ENT_SUBSTITUTE) ?>">

        <label class="form-label">Adresse email
          <input name="email" type="email" inputmode="email" required
                 placeholder="nom@exemple.com" value="<?= isset($email) ? htmlspecialchars($email, ENT_QUOTES|ENT_SUBSTITUTE) : '' ?>">
        </label>

        <label class="form-label">Mot de passe
          <div class="password-field">
            <input name="password" id="passwordInput" type="password" required placeholder="••••••••">
            <button type="button" class="btn-ghost icon-btn" aria-label="Afficher le mot de passe" id="togglePassword">👁️</button>
          </div>
        </label>

        <div class="form-row form-actions">
          <label class="checkbox">
            <input type="checkbox" name="remember" <?= !empty($_POST['remember']) ? 'checked' : '' ?>>
            <span>Rester connecté</span>
          </label>
          <a class="muted small" href="/public/forgot_password.php">Mot de passe oublié ?</a>
        </div>

        <div class="form-row">
          <button class="btn btn-primary" id="submitBtn" type="submit">
            <span class="btn-spin" aria-hidden="true"></span> Se connecter
          </button>
          <a class="btn btn-ghost" href="../public/register.php">Créer un compte</a>
        </div>

        <div class="divider"><span>ou</span></div>

        <div class="socials">
          <button type="button" class="btn btn-ghost" onclick="window.location.href='../auth/oauth/google'">Se connecter avec Google</button>
          <button type="button" class="btn btn-ghost" onclick="window.location.href='../auth/oauth/github'">Se connecter avec GitHub</button>
        </div>
      </form>

      <footer class="auth-foot muted">En vous connectant, vous acceptez nos <a href="../terms.php">conditions</a>.</footer>
    </div>
  </section>
</main>

<script src="../assets/js/login.js" defer></script>
</body>
</html>
