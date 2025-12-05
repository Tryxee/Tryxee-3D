// assets/js/app.js
// assets/js/app.js
(function(){
  // simple helper fetch JSON
  async function postJSON(url,data){
    const res = await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
    return res.json();
  }

  // price estimate live
  const form = document.getElementById('order-form');
  const priceEl = document.getElementById('price-estimate');
  const addBtn = document.getElementById('add-to-cart');
  const applyPromo = document.getElementById('apply-promo');

  async function estimate(){
    if(!form) return;
    const data = {
      length_mm: parseFloat(document.getElementById('len').value || 0),
      width_mm: parseFloat(document.getElementById('wid').value || 0),
      height_mm: parseFloat(document.getElementById('hei').value || 0),
      filament_id: parseInt(document.getElementById('filament-select').value || 0),
      nozzle_size: parseFloat(document.getElementById('nozzle-select').value || 0.4),
      layer_height: parseFloat(document.getElementById('layer-select').value || 0.2),
      promo: document.getElementById('promo') ? document.getElementById('promo').value : '' ,
      manual: !!(document.getElementById('manual_desc') && document.getElementById('manual_desc').value.trim())
    };
    try{
      const r = await postJSON('../public/api/calc_price.php', data);
      if (r && r.total !== undefined) priceEl.textContent = r.total.toFixed(2) + ' €';
      return r;
    }catch(e){ console.error(e); }
  }

  // throttle estimate
  let timer;
  document.addEventListener('input', ()=>{ clearTimeout(timer); timer = setTimeout(estimate, 420); });
  document.addEventListener('change', ()=>{ clearTimeout(timer); timer = setTimeout(estimate, 200); });
  estimate();

  // ---------- add to cart ----------
  if(addBtn){
    addBtn.addEventListener('click', async ()=>{
      const payload = {
        id: Date.now(),
        firstname: form.firstname?.value || '',
        lastname: form.lastname?.value || '',
        email: form.email?.value || '',
        printer_id: parseInt(document.getElementById('printer-select').value||0),
        length_mm: parseInt(document.getElementById('len').value||0),
        width_mm: parseInt(document.getElementById('wid').value||0),
        height_mm: parseInt(document.getElementById('hei').value||0),
        filament_id: parseInt(document.getElementById('filament-select').value||0),
        nozzle_size: parseFloat(document.getElementById('nozzle-select').value||0.4),
        layer_height: parseFloat(document.getElementById('layer-select').value||0.2),
        promo: document.getElementById('promo')?.value||'',
        price_estimate: priceEl ? priceEl.textContent || '0.00' : '0.00'
      };
      // save local
      let cart = JSON.parse(localStorage.getItem('neptune_cart') || '[]');
      cart.push(payload); localStorage.setItem('neptune_cart', JSON.stringify(cart));
      // save server session (fire-and-forget but catch errors)
      try {
        await postJSON('http://localhost/site_impression_3d/public/api/cart_api.php?action=add', payload);
      } catch (err) {
        console.warn('Server cart add failed', err);
      }
      alert('Ajouté au panier');
      updateCartCount();
    });
  }

  function updateCartCount(){
    const count = JSON.parse(localStorage.getItem('neptune_cart') || '[]').length;
    const el = document.getElementById('cart-count'); if(el) el.textContent = '('+count+')';
  }
  updateCartCount();

  // ---------- cart page rendering ----------
  if(document.getElementById('cart-list')){
    const list = document.getElementById('cart-list');
    const items = JSON.parse(localStorage.getItem('neptune_cart') || '[]');
    let total = 0;
    list.innerHTML = items.map(it=>{
      const price = parseFloat((it.price_estimate||'0').replace(' €','')) || 0; total += price;
      return `<div class="card"><h4>Item #${it.id} — ${it.price_estimate}</h4><p class="muted">${it.firstname} ${it.lastname} — ${it.email}</p></div>`;
    }).join('');
    const totEl = document.getElementById('cart-total');
    if (totEl) totEl.textContent = total.toFixed(2) + ' €';

    document.getElementById('save-cart').addEventListener('click', async ()=>{
      // sauvegarder côté serveur: simple POST de tous les items
      try {
        await postJSON('../public/api/cart_api.php?action=clear', {});
      } catch (err) { /* ignore */ }
      for(const it of items){
        try {
          await postJSON('../public/api/cart_api.php?action=add', it);
        } catch (err) {
          console.warn('Failed to add item to server cart', err);
        }
      }
      alert('Panier sauvegardé en session.');
    });
  }

  // promo button
  if(applyPromo){ applyPromo.addEventListener('click', estimate); }

  // ---------- submit order helper (example) ----------
  // Attache sur le formulaire si présent
  if (form) {
    form.addEventListener('submit', async (ev) => {
      ev.preventDefault();

      const payload = {
        csrf: (window.FABLAB_CSRF || document.querySelector('input[name="csrf_token"]')?.value || '' ),
        firstname: form.firstname?.value || '',
        lastname: form.lastname?.value || '',
        email: form.email?.value || '',
        phone: form.phone?.value || '',
        printer_id: parseInt(document.getElementById('printer-select')?.value || 0),
        length_mm: parseInt(document.getElementById('len')?.value || 0),
        width_mm: parseInt(document.getElementById('wid')?.value || 0),
        height_mm: parseInt(document.getElementById('hei')?.value || 0),
        filament_id: parseInt(document.getElementById('filament-select')?.value || 0),
        nozzle_size: parseFloat(document.getElementById('nozzle-select')?.value || 0.4),
        layer_height: parseFloat(document.getElementById('layer-select')?.value || 0.2),
        qty: parseInt(document.getElementById('qty')?.value || 1),
        promo: document.getElementById('promo')?.value || '',
        manual_desc: document.getElementById('manual_desc')?.value || '',
        total_estimate: parseFloat((priceEl?.textContent || '0').replace(' €','')) || 0.0
      };

      try {
        const resp = await postJSON('../public/api/submit_order.php', payload);
        if (resp && resp.ok) {
          alert('Commande soumise ! ID: ' + resp.order_id);
          // optionally redirect to a thank-you page
          window.location.href = '/public/thankyou.php?order=' + encodeURIComponent(resp.order_id);
        } else {
          // unexpected but handle
          console.warn('Submit returned unexpected payload', resp);
          alert('Réponse inattendue du serveur.');
        }
      } catch (err) {
        // err may contain err.response with server payload
        console.error('Order submit error', err);
        let msg = err.message || 'Erreur lors de l\'envoi';
        if (err.response && err.response.error) msg = err.response.error;
        alert('Erreur: ' + msg);
      }
    });
  }

})();