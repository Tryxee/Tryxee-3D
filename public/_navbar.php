<?php
// _navbar.php
if (session_status() === PHP_SESSION_NONE) session_start();
$logged = !empty($_SESSION['user_id']);
?>
<nav class="navbar">
  <div class="brand">Tryxee <span class="lastname">3D</span></div>
  <div class="nav-links">
    <a href="../public/index.php">Boutique</a>
    <a href="../public/order.php">Commander</a>
    <a href="../public/cart.php">Panier <span id="cart-count">(0)</span></a>
    <?php if($logged): ?>
      <a href="../public/dashboard.php">Mon compte</a>
      <a href="../public/api/logout.php">Déconnexion</a>
    <?php else: ?>
      <a href="../public/login.php">Connexion</a>
    <?php endif; ?>
  </div>
  <button class="burger" aria-label="Menu">☰</button>
</nav>