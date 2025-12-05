<?php
require_once __DIR__ . '/../config.php';
$printers = $pdo->query('SELECT * FROM printers')->fetchAll();
$filaments = $pdo->query('SELECT * FROM filaments')->fetchAll();
$csrf = csrf_token();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Commander / Demande de modélisation — Tryxee 3D</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../assets/css/order.css">
  <meta name="description" content="Envoyez votre modèle 3D ou décrivez votre besoin — nous vous renvoyons un devis par email.">
</head>
<body class="page-order">
  <?php include __DIR__ . '/_navbar.php'; ?>

  <?php 
    include __DIR__ . '/helpers.php';
    debug_session()
  ?>

  <main class="container">
    <section class="order-hero reveal" aria-hidden="false">
      <div class="grid-two">
        <div class="card order-left">
          <h1>Demande d'impression / modélisation</h1>
          <p class="muted">Envoyez votre fichier 3D (.stl / .obj / .zip) ou décrivez précisément ce que vous souhaitez — nous vous renverrons un devis par e-mail.</p>

          <form id="order-form" class="order-form" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="csrf" value="<?=htmlspecialchars($csrf)?>">

            <div class="row">
              <label class="field">
                <span>Nom *</span>
                <input name="lastname" type="text" required>
              </label>
              <label class="field">
                <span>Prénom *</span>
                <input name="firstname" type="text" required>
              </label>
            </div>

            <div class="row">
              <label class="field">
                <span>Email *</span>
                <input name="email" type="email" required>
              </label>
              <label class="field">
                <span>Téléphone (opt.)</span>
                <input name="phone" type="tel">
              </label>
            </div>

            <label class="field">
              <span>Imprimante cible *</span>
              <select name="printer_id" id="printer-select" required>
                <?php foreach($printers as $p): ?>
                  <option value="<?=intval($p['id'])?>" data-x="<?=intval($p['build_x'])?>" data-y="<?=intval($p['build_y'])?>" data-z="<?=intval($p['build_z'])?>">
                    <?=htmlspecialchars($p['name'])?> — <?=intval($p['build_x']).'×'.intval($p['build_y']).'×'.intval($p['build_z'])?> mm
                  </option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="field">
              <span>Importer un fichier 3D (.stl, .obj, .zip) — ou glisser ici</span>
              <div id="dropzone" class="dropzone" tabindex="0">
                <input id="file-input" name="file" type="file" accept=".stl,.obj,.zip" />
                <div class="dz-inner">
                  <svg width="44" height="44" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5 20h14v-2H5v2zM12 3l-5 5h3v4h4V8h3l-5-5z"/></svg>
                  <div class="dz-text">Cliquez ou glissez votre fichier ici</div>
                  <div class="dz-sub muted">Formats acceptés : .stl .obj .zip — max 25MB</div>
                </div>
              </div>
              <div id="file-preview" class="file-preview muted" aria-live="polite"></div>
            </label>

            <label class="field">
              <span>Ou description détaillée pour modélisation</span>
              <textarea id="manual_desc" name="manual_desc" rows="5" placeholder="Ex : bouchon cylindrique, diamètre 25 mm, hauteur 8 mm, trou central 4 mm..."></textarea>
            </label>

            <div class="three">
              <label class="field small">
                <span>Longueur (mm)</span>
                <input id="len" name="length_mm" type="number" min="1" placeholder="ex: 50">
              </label>
              <label class="field small">
                <span>Largeur (mm)</span>
                <input id="wid" name="width_mm" type="number" min="1" placeholder="ex: 30">
              </label>
              <label class="field small">
                <span>Hauteur (mm)</span>
                <input id="hei" name="height_mm" type="number" min="1" placeholder="ex: 10">
              </label>
            </div>

            <div class="row">
              <label class="field">
                <span>Filament</span>
                <select id="filament-select" name="filament_id">
                  <?php foreach($filaments as $f): ?>
                    <option value="<?=intval($f['id'])?>" data-price="<?=floatval($f['price_per_kg'])?>"><?=htmlspecialchars($f['name'])?> — <?=htmlspecialchars($f['material'])?></option>
                  <?php endforeach; ?>
                </select>
              </label>

              <label class="field">
                <span>Buse (mm)</span>
                <select id="nozzle-select" name="nozzle_size">
                  <option value="0.25">0.25</option>
                  <option value="0.4" selected>0.40</option>
                  <option value="0.6">0.60</option>
                  <option value="0.8">0.80</option>
                </select>
              </label>
            </div>

            <div class="row small">
              <label class="field small">
                <span>Hauteur de couche</span>
                <select id="layer-select" name="layer_height">
                  <option value="0.05">0.05</option>
                  <option value="0.1">0.10</option>
                  <option value="0.15">0.15</option>
                  <option value="0.2" selected>0.20</option>
                </select>
              </label>

              <label class="field small">
                <span>Code promo</span>
                <div style="display:flex;gap:.5rem">
                  <input id="promo" name="promo" type="text">
                  <button id="apply-promo" type="button" class="btn btn-ghost">Appliquer</button>
                </div>
              </label>

              <label class="field small">
                <span>Quantité</span>
                <input id="qty" name="qty" type="number" min="1" value="1">
              </label>
            </div>

            <div class="form-footer">
              <div class="estimate">
                <div><small class="muted">Estimation instantanée</small></div>
                <div class="price"><span id="price-estimate">—</span></div>
              </div>

              <div class="actions">
                <button id="add-to-cart" type="button" class="btn btn-ghost">Ajouter au panier</button>
                <button id="submit-order" type="button" class="btn btn-primary">Envoyer la demande (recevoir devis)</button>
              </div>
            </div>
          </form>
        </div>

        <aside class="card order-right reveal">
          <h4>Guide rapide</h4>
          <ul class="muted">
            <li>Fournis un fichier 3D ou une description claire.</li>
            <li>Indique la taille exacte et le matériau souhaité.</li>
            <li>Nous contrôlons et optimisons le modèle avant impression.</li>
            <li>Recevez un devis par e-mail sous 24–72h (selon complexité).</li>
          </ul>

          <div class="small-cta" style="margin-top:1rem">
            <a href="cart.php" class="btn btn-ghost">Voir le panier</a>
            <a href="login.php" class="btn btn-ghost">Connexion / Compte</a>
          </div>

          <div class="muted" style="margin-top:1.25rem;font-size:.95rem">Vos fichiers sont sécurisés. Le dossier uploads est protégé et non listable.</div>
        </aside>
      </div>
    </section>
  </main>

  <!-- Success modal -->
  <div id="toast" class="toast" aria-live="polite" role="status" hidden>
    <div class="toast-content">
      <strong id="toast-title">Envoyé !</strong>
      <div id="toast-body">Nous avons bien reçu votre demande. Vous recevrez un e-mail sous peu.</div>
      <button id="toast-close" class="toast-close" aria-label="Fermer">×</button>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
<script src="../assets/js/home.js"></script>
  <script src="../assets/js/order.js"></script>
</body>
</html>
