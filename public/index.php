<?php
require_once __DIR__ . '/../config.php';
// fetch printers to show
$printers = $pdo->query('SELECT * FROM printers ORDER BY id ASC')->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Tryxee 3D — Boutique & Services d'impression</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <meta name="description" content="Tryxee 3D — impression & modélisation 3D professionnelle. Devis instantané, impressions haute précision.">
</head>
<body class="page-home">
  <?php include __DIR__ . '/_navbar.php'; ?>

  <header class="hero" role="banner" aria-label="Hero Tryxee 3D">
    <div class="hero-inner container">
      <div class="hero-copy">
        <h1 class="hero-title">Tryxee <span class="accent">3D</span></h1>
        <p class="hero-sub">Impression & modélisation 3D haute précision — du prototype au produit fini.</p>

        <div class="hero-ctas">
          <a class="btn btn-primary" href="order.php">Commander / Devis</a>
          <a class="btn btn-ghost" href="cart.php">Voir le panier</a>
        </div>

        <ul class="hero-features" aria-hidden="true">
          <li class="feature"><strong>Précision</strong><span>Jusqu'à 0.05mm</span></li>
          <li class="feature"><strong>Matériaux</strong><span>PETG</span></li>
          <li class="feature"><strong>Livraison</strong><span>Calculée au panier</span></li>
        </ul>
      </div>

      <div class="hero-visual" aria-hidden="true">
        <!-- Animated SVG + decorative shapes -->
        <svg class="visual-canvas" viewBox="0 0 600 400" preserveAspectRatio="xMidYMid meet" role="img">
          <defs>
            <linearGradient id="g1" x1="0" x2="1">
              <stop offset="0" stop-color="#4dd0e1"/>
              <stop offset="1" stop-color="#7ce7ff"/>
            </linearGradient>
            <filter id="blurglow" x="-50%" y="-50%" width="200%" height="200%">
              <feGaussianBlur stdDeviation="18" result="b"/>
              <feBlend in="SourceGraphic" in2="b"/>
            </filter>
          </defs>

          <!-- swirling ribbon -->
          <g transform="translate(300,200)">
            <path class="ribbon" d="M-160,-20 C-100,-120 100,-120 160,-20 C100,80 -100,80 -160,-20 Z" fill="url(#g1)" opacity="0.12" filter="url(#blurglow)"></path>
            <path class="ribbon-sharp" d="M-140,-10 C-80,-100 80,-100 140,-10" stroke="url(#g1)" stroke-width="6" fill="none" stroke-linecap="round" stroke-linejoin="round" />
            <!-- pseudo-print layer lines -->
            <g class="layers" transform="translate(-140, -60)">
              <?php for($i=0;$i<6;$i++): ?>
                <rect x="<?=($i*40)?>" y="<?=($i*8)?>" width="220" height="6" rx="3" class="layer"></rect>
              <?php endfor; ?>
            </g>
          </g>
        </svg>
      </div>
    </div>
    <div class="hero-shine" aria-hidden="true"></div>
  </header>

  <main class="container">
    <section class="section intro">
      <div class="grid-two">
        <div class="about-card reveal">
          <h2>Pourquoi choisir Tryxee 3D ?</h2>
          <p class="muted">Nous allions expertise, équipements pro (Neptune 4 Plus & co) et un workflow sécurisé pour tes fichiers et impressions. Chaque commande est préparée, vérifiée et optimisée pour la meilleure qualité.</p>

          <div class="card-stats">
            <div><h3>0</h3><p class="muted">Impressions</p></div>
            <div><h3>1</h3><p class="muted">Matériaux</p></div>
            <div><h3>-</h3><p class="muted">Satisfaction</p></div>
          </div>
        </div>

        <aside class="about-side reveal">
          <div class="card about-visual">
            <h4 class="muted">Débuter</h4>
            <p class="muted">Crée un devis instantané ou envoie ta demande de modélisation.</p>
            <a class="btn btn-primary small" href="order.php">Demander un devis</a>
          </div>
        </aside>
      </div>
    </section>

    <section class="section printers">
      <h2 class="reveal">Nos imprimantes</h2>
      <div class="projects-grid">
        <?php foreach($printers as $p): ?>
          <?php
            $name = htmlspecialchars($p['name']);
            $dim = intval($p['build_x']).'×'.intval($p['build_y']).'×'.intval($p['build_z']).' mm';
          ?>
          <article class="project-card reveal printer-card" tabindex="0" data-x="<?=$p['build_x']?>" data-y="<?=$p['build_y']?>" data-z="<?=$p['build_z']?>">
            <?php
              $slug = strtolower(str_replace(' ', '-', $p['name']));
              $imgPath = "../assets/img/printers/$slug.png";
            ?>
            <div class="project-thumb">
              <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            </div>
            <h3><?= $name ?></h3>
            <small class="muted"><?= $dim ?></small>
            <p class="muted">Zone utile affichée — parfaite pour prototypes et pièces fonctionnelles.</p>
            <div class="project-actions">
              <a class="btn btn-ghost" href="order.php?printer=<?=$p['id']?>">Choisir</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="section cta-hero reveal">
      <div class="card grid-two" style="align-items:center">
        <div>
          <h2>Prêt à lancer ton projet ?</h2>
          <p class="muted">Demande un devis détaillé ou télécharge ton fichier. Nous vérifions tout côté technique avant impression.</p>
        </div>
        <div style="text-align:right">
          <a class="btn btn-primary" href="order.php">Obtenir un devis</a>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer container" role="contentinfo">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
      <div class="muted">© <?=date('Y')?> Tryxee 3D — Tous droits réservés</div>
      <div class="muted">Made with ♥</div>
    </div>
  </footer>

  <script src="../assets/js/app.js"></script>
  <script src="../assets/js/home.js"></script>
</body>
</html>
