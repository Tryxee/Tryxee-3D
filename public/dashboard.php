<?php
// public/dashboard.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../public/login.php');
    exit;
}

$error = '';
$user = null;
$orders = [];
try {
    $stmt = $pdo->prepare('SELECT id, email, firstname, lastname, phone, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ../public/logout.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, status, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $msg = sprintf("[%s] dashboard.php ERROR: %s in %s:%d\nTrace:\n%s\n\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    error_log($msg);
    @mkdir(__DIR__ . '/../logs', 0755, true);
    @file_put_contents(__DIR__ . '/../logs/dashboard_error.log', $msg, FILE_APPEND | LOCK_EX);
    $error = 'Erreur lors du chargement du tableau de bord. Voir logs.';
}

$order_count = count($orders);
$total_spent = 0.0;
foreach ($orders as $o) $total_spent += floatval($o['total'] ?? 0.0);

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Mon Tableau de bord — MonFabLab</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/dashboard.css">
  <style>
    /* take into account fixed navbar height (from styles.css .navbar height:64px + top 12px in your original) */
    main.container { padding-top: 96px; } 
    @media (max-width:980px){ main.container { padding-top: 80px; } }
  </style>
</head>
<body>
<?php include __DIR__.'/_navbar.php'; ?>

<main class="container">
  <section class="dashboard-hero" style="align-items:flex-start; gap:1.2rem;">
    <div style="flex:1">
      <h1>Bonjour, <span class="username"><?= e($user['firstname'] ?: $user['email']) ?></span></h1>
      <p class="muted">Bienvenue — voici le résumé de votre activité.</p>

      <div style="margin-top:14px" class="summary-grid">
        <div class="stat-card"><div class="label">Commandes</div><div class="value"><?= e($order_count) ?></div></div>
        <div class="stat-card"><div class="label">Dépensé</div><div class="value"><?= number_format($total_spent, 2) ?> €</div></div>
      </div>
    </div>

    <aside style="width:320px;max-width:40%;min-width:260px;">
      <div class="card about-card" style="padding:14px;">
        <h3 style="margin:0 0 8px 0">Mon compte</h3>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px">
          <div style="width:56px;height:56px;border-radius:10px;background:linear-gradient(90deg,var(--accent),var(--accent-2));display:flex;align-items:center;justify-content:center;font-weight:800;color:#022;font-size:18px">
            <?= e(mb_substr(trim($user['firstname'].' '.$user['lastname']),0,1) ?: 'U') ?>
          </div>
          <div>
            <div style="font-weight:800"><?= e(trim($user['firstname'].' '.$user['lastname']) ?: $user['email']) ?></div>
            <div class="muted" style="font-size:0.9rem"><?= e($user['email']) ?></div>
          </div>
        </div>

        <dl style="display:grid;gap:6px">
          <div><dt class="muted small">Prénom</dt><dd><?= e($user['firstname'] ?? '—') ?></dd></div>
          <div><dt class="muted small">Nom</dt><dd><?= e($user['lastname'] ?? '—') ?></dd></div>
          <div><dt class="muted small">Téléphone</dt><dd><?= e($user['phone'] ?? '—') ?></dd></div>
          <div><dt class="muted small">Membre depuis</dt><dd><?= e(date('d/m/Y', strtotime($user['created_at'] ?? 'now'))) ?></dd></div>
        </dl>

        <div style="display:flex;gap:8px;margin-top:12px">
          <a class="btn btn-primary" href="../public/profile.php">Éditer mon profil</a>
          <form method="post" action="../public/api/logout.php" style="margin:0">
            <button class="btn btn-ghost" type="submit">Se déconnecter</button>
          </form>
        </div>
      </div>
    </aside>
  </section>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
  <?php endif; ?>

  <section class="section orders-section">
    <header class="section-head">
      <h2>Mes commandes</h2>
      <p class="muted"><?= $order_count ?> commande(s)</p>
    </header>

    <?php if ($order_count === 0): ?>
      <div class="card empty">
        <h3>Aucune commande pour le moment</h3>
        <p class="muted">Passez une commande pour la voir listée ici.</p>
        <div style="margin-top:12px"><a class="btn btn-primary" href="../public/index.php">Voir la boutique</a></div>
      </div>
    <?php else: ?>
      <div class="orders-grid" id="ordersGrid">
        <?php foreach ($orders as $o):
            $order_json = json_encode($o, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $order_json_esc = htmlspecialchars($order_json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ?>
          <article class="order-card" data-order="<?= $order_json_esc ?>">
            <div class="order-top">
              <div class="order-id">#<?= e($o['id']) ?></div>
              <div class="order-status <?= strtolower(e($o['status'])) ?>"><?= e(ucfirst($o['status'])) ?></div>
            </div>
            <div class="order-meta">
              <div class="muted">Le <?= e($o['created_at']) ?></div>
              <div class="order-total"><?= number_format(floatval($o['total'] ?? 0.0), 2) ?> €</div>
            </div>
            <div class="order-actions">
              <button class="btn btn-ghost view-order">Voir</button>
              <a class="btn" href="../public/order_view.php?id=<?= urlencode($o['id']) ?>">Détails</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</main>

<!-- modal same as earlier -->
<div id="orderModal" class="modal" aria-hidden="true" role="dialog" aria-modal="true">
  <div class="modal-backdrop" data-close></div>
  <div class="modal-panel" role="document" aria-labelledby="modalTitle">
    <header class="modal-header">
      <h3 id="modalTitle">Commande</h3>
      <button class="icon-btn" id="closeModal" aria-label="Fermer">✕</button>
    </header>
    <div class="modal-body" id="modalBody"><p class="muted">Chargement...</p></div>
    <footer class="modal-footer">
      <button class="btn btn-ghost" data-close>Fermer</button>
    </footer>
  </div>
</div>

<script src="../assets/js/dashboard.js" defer></script>
</body>
</html>
