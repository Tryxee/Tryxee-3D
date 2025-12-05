// assets/js/order.js
// Version robuste — attendu : order.php est dans public/ et calc_price.php dans public/api/
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('order-form');
    if (!form) return console.warn('order.js: form introuvable');

    const priceEl = document.getElementById('price-estimate');
    const applyPromo = document.getElementById('apply-promo');
    const addToCartBtn = document.getElementById('add-to-cart');
    const submitBtn = document.getElementById('submit-order');
    const fileInput = document.getElementById('file-input');
    const dropzone = document.getElementById('dropzone');
    const preview = document.getElementById('file-preview');
    const toast = document.getElementById('toast');
    const toastClose = document.getElementById('toast-close');

    // helper toast
    function showToast(title, body, timeout = 5000) {
      if (!toast) { console.info(title, body); return; }
      document.getElementById('toast-title').textContent = title;
      document.getElementById('toast-body').textContent = body;
      toast.hidden = false;
      toast.classList.add('open');
      if (timeout > 0) setTimeout(() => closeToast(), timeout);
    }
    function closeToast() {
      if (!toast) return;
      toast.classList.remove('open');
      setTimeout(() => toast.hidden = true, 250);
    }
    if (toastClose) toastClose.addEventListener('click', closeToast);

    // Debounce helper
    function debounce(fn, wait = 300) {
      let t;
      return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
    }

    // Build payload from form fields
    function buildPayload() {
      return {
        length_mm: Number(document.getElementById('len')?.value || 0),
        width_mm: Number(document.getElementById('wid')?.value || 0),
        height_mm: Number(document.getElementById('hei')?.value || 0),
        filament_id: parseInt(document.getElementById('filament-select')?.value || 0),
        nozzle_size: parseFloat(document.getElementById('nozzle-select')?.value || 0.4),
        layer_height: parseFloat(document.getElementById('layer-select')?.value || 0.2),
        promo: document.getElementById('promo')?.value || '',
        qty: parseInt(document.getElementById('qty')?.value || 1),
        manual: !!(document.getElementById('manual_desc')?.value?.trim())
      };
    }

    // Correct URL for calc_price.php from order.php in public/
    const CALC_URL = 'api/calc_price.php';

    async function fetchRaw(url, body) {
      // safe fetch with text read for debugging
      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body)
        });
        const text = await res.text();
        return { ok: res.ok, status: res.status, text };
      } catch (err) {
        console.error('fetchRaw error:', err);
        return { ok: false, status: 0, text: '' , error: err };
      }
    }

    // Estimate -> robust parse
    let lastRaw = null;
    async function estimate() {
      const payload = buildPayload();
      // if no dims and no manual desc -> don't call
      if (!payload.manual && (payload.length_mm <= 0 || payload.width_mm <= 0 || payload.height_mm <= 0)) {
        priceEl.textContent = '—';
        return;
      }

      const r = await fetchRaw(CALC_URL, payload);
      lastRaw = r;
      if (!r.ok) {
        console.error('calc_price returned non-ok status', r.status, r.text);
        priceEl.textContent = '—';
        return;
      }

      // parse JSON safely
      try {
        const json = JSON.parse(r.text);
        // If your calc_price returns {ok:true, breakdown:{...}}, adapt here
        if (json && json.breakdown && json.breakdown.price_ttc !== undefined) {
          priceEl.textContent = Number(json.breakdown.price_ttc).toFixed(2) + ' €';
        } else if (json && json.price_ttc !== undefined) {
          priceEl.textContent = Number(json.price_ttc).toFixed(2) + ' €';
        } else if (json && json.total !== undefined) {
          priceEl.textContent = Number(json.total).toFixed(2) + ' €';
        } else if (json && json.ok && json.manual) {
          priceEl.textContent = 'Demande manuelle — devis par email';
        } else {
          console.warn('calc_price: réponse JSON inattendue', json);
          priceEl.textContent = '—';
        }
      } catch (err) {
        console.error('Impossible de parser JSON depuis calc_price.php — réponse brute :', r.text);
        priceEl.textContent = '—';
      }
    }

    const estimateDebounced = debounce(estimate, 280);

    // Wire inputs -> estimate
    const inputSelectors = ['len','wid','hei','filament-select','nozzle-select','layer-select','promo','manual_desc','qty'];
    inputSelectors.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', estimateDebounced);
      el.addEventListener('change', estimateDebounced);
    });

    if (applyPromo) applyPromo.addEventListener('click', estimateDebounced);

    // initial estimate when page loads (if values present)
    estimateDebounced();

    // Dropzone & file preview (basic)
    function handleFile(f) {
      if (!f) return;
      const allowed = ['stl','obj','zip'];
      const ext = (f.name.split('.').pop() || '').toLowerCase();
      if (!allowed.includes(ext)) {
        showToast('Fichier invalide', 'Format non accepté.');
        fileInput.value = '';
        return;
      }
      if (f.size > 25*1024*1024) {
        showToast('Fichier trop lourd', 'Max 25 MB');
        fileInput.value = '';
        return;
      }
      preview.innerHTML = `<div><strong>${f.name}</strong> — ${(f.size/1024|0)} KB</div>`;
    }

    if (dropzone) {
      ['dragenter','dragover'].forEach(ev => dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.add('dz-over'); }));
      ['dragleave','drop','dragend'].forEach(ev => dropzone.addEventListener(ev, e => { e.preventDefault(); dropzone.classList.remove('dz-over'); }));
      dropzone.addEventListener('drop', e => {
        const f = e.dataTransfer.files && e.dataTransfer.files[0];
        if (f) { fileInput.files = e.dataTransfer.files; handleFile(f); }
      });
    }
    if (fileInput) fileInput.addEventListener('change', e => handleFile(e.target.files[0]));

    // add to cart (simple localStorage)
    if (addToCartBtn) {
      /*addToCartBtn.addEventListener('click', () => {
        const payload = buildPayload();
        payload.price_estimate = priceEl.textContent || '—';
        let cart = JSON.parse(localStorage.getItem('neptune_cart') || '[]');
        cart.push(payload);
        localStorage.setItem('neptune_cart', JSON.stringify(cart));
        showToast('Panier', 'Article ajouté au panier');
        // update cart-count if exists
        const el = document.getElementById('cart-count'); if (el) el.textContent = '('+cart.length+')';
      });*/
    }

    // submit order -> uses multipart/form-data with file upload via fetch
    if (submitBtn) {
      submitBtn.addEventListener('click', async () => {
        submitBtn.disabled = true; submitBtn.textContent = 'Envoi...';
        const fd = new FormData(form);
        // attach price estimate (for server logging)
        fd.append('price_estimate', priceEl.textContent || '');
        try {
          const res = await fetch('api/submit_order.php', { method: 'POST', body: fd });
          const text = await res.text();
          try {
            const json = JSON.parse(text);
            if (json && json.ok) {
              showToast('Envoyé', 'Demande reçue — vous recevrez un email.');
              form.reset(); preview.innerHTML = ''; priceEl.textContent = '—';
            } else {
              console.error('submit_order response:', json, text);
              showToast('Erreur', json && json.error ? json.error : 'Erreur serveur');
            }
          } catch (err) {
            console.error('submit_order returned non-json:', text);
            showToast('Erreur', 'Réponse serveur invalide (voir console)');
          }
        } catch (err) {
          console.error('submit_order network error:', err);
          showToast('Erreur', 'Impossible de contacter le serveur');
        } finally {
          submitBtn.disabled = false; submitBtn.textContent = 'Envoyer la demande (recevoir devis)';
        }
      });
    }

    // Expose for debug
    window.__neptune_debug = { estimate, estimateDebounced, fetchRaw };

  }); // DOMContentLoaded
})();
