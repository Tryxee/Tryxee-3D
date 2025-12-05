<?php
// public/cart.php
declare(strict_types=1);

require_once __DIR__ . '/../config.php'; // doit définir csrf_token(), session, etc.
$csrf = csrf_token();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panier — MonFabLab</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/cart.css">
</head>
<body>
  <?php include __DIR__ . '/_navbar.php'; ?>

  <main class="container">
    <section class="cart-section">
      <header class="cart-header">
        <h2>Mon panier</h2>
        <p class="muted" id="cart-count-note">Chargement…</p>
      </header>

      <div class="cart-layout">
        <div class="cart-list card" id="cart-list" aria-live="polite">
          <!-- items rendered by JS -->
        </div>

        <aside class="cart-summary card">
          <h3>Récapitulatif</h3>
          <div class="summary-row"><span>Sous-total</span><span id="subtotal">0.00 €</span></div>
          <div class="summary-row"><span>TVA (20%)</span><span id="tax">0.00 €</span></div>
          <div class="summary-row total"><strong>Total TTC</strong><strong id="cart-total">0.00 €</strong></div>

          <div class="summary-actions">
            <button id="save-cart" class="btn btn-primary">Sauvegarder le panier</button>
            <button id="checkout" class="btn btn-ghost">Procéder au paiement</button>
            <button id="clear-cart" class="btn btn-ghost" title="Vider le panier">Vider le panier</button>
          </div>

          <div id="save-feedback" class="muted small" aria-live="polite" style="margin-top:0.6rem"></div>
        </aside>
      </div>
    </section>
  </main>

  <!-- Checkout modal (stub) -->
  <div id="checkoutModal" class="modal" aria-hidden="true" role="dialog">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-panel">
      <header class="modal-header"><h3>Procéder au paiement</h3><button class="icon-btn" data-close aria-label="Fermer">✕</button></header>
      <div class="modal-body">
        <p class="muted">Fonction de paiement non implémentée. Ceci est une démo. Vous pouvez implémenter la page de checkout côté serveur ou intégrer Stripe/PayPal ici.</p>
      </div>
      <footer class="modal-footer"><button class="btn btn-primary" data-close>Fermer</button></footer>
    </div>
  </div>

  <script>const FABLAB_CSRF = <?= json_encode($csrf) ?>;</script>
  <script src="../assets/js/cart.js" defer></script>
</body>
</html>
