(function () {
  'use strict';
  var KEY = 'bb_cart_v1';
  var MAX_QTY = 20;
  var cart = load();

  function load() {
    try {
      var d = JSON.parse(localStorage.getItem(KEY) || '[]');
      if (!Array.isArray(d)) return [];
      return d.filter(function (i) {
        return i && Number(i.id) > 0 && Number(i.qty) > 0;
      }).map(function (i) {
        return { id: Number(i.id), name: String(i.name), price: Number(i.price), qty: Math.min(MAX_QTY, Number(i.qty)) };
      });
    } catch (e) { return []; }
  }
  function save() { localStorage.setItem(KEY, JSON.stringify(cart)); }
  function money(n) { return '\u20B1' + n.toFixed(2); }
  function $(id) { return document.getElementById(id); }

  var itemsEl = $('cart-items'), subEl = $('cart-subtotal'), countEl = $('cart-count');
  var panel = $('cart-panel'), overlay = $('cart-overlay');
  var form = $('checkout-form'), submitBtn = $('checkout-submit');
  if (!itemsEl) return;

  function render() {
    itemsEl.textContent = '';
    var sub = 0, count = 0;
    if (!cart.length) {
      var p = document.createElement('p');
      p.className = 'cart-empty';
      p.textContent = 'Your cart is empty.';
      itemsEl.appendChild(p);
    }
    cart.forEach(function (it) {
      sub += it.price * it.qty; count += it.qty;
      var row = document.createElement('div'); row.className = 'cart-item';
      var nm = document.createElement('div'); nm.className = 'nm'; nm.textContent = it.name;
      var lt = document.createElement('div'); lt.className = 'price'; lt.textContent = money(it.price * it.qty);
      var q = document.createElement('div'); q.className = 'qty';
      var minus = btn('\u2212', function () { change(it.id, -1); });
      var n = document.createElement('span'); n.textContent = it.qty;
      var plus = btn('+', function () { change(it.id, 1); });
      q.appendChild(minus); q.appendChild(n); q.appendChild(plus);
      var rm = document.createElement('button'); rm.type = 'button'; rm.className = 'link-btn'; rm.textContent = 'Remove';
      rm.addEventListener('click', function () { remove(it.id); });
      row.appendChild(nm); row.appendChild(lt); row.appendChild(q); row.appendChild(rm);
      itemsEl.appendChild(row);
    });
    subEl.textContent = money(sub);
    if (countEl) countEl.textContent = count;
    if (submitBtn && !submitBtn.dataset.locked) submitBtn.disabled = !cart.length || submitBtn.dataset.closed === '1';
  }
  function btn(label, fn) {
    var b = document.createElement('button'); b.type = 'button'; b.textContent = label;
    b.setAttribute('aria-label', label === '+' ? 'Increase quantity' : 'Decrease quantity');
    b.addEventListener('click', fn); return b;
  }
  function find(id) { for (var i = 0; i < cart.length; i++) if (cart[i].id === id) return cart[i]; return null; }
  function change(id, d) {
    var it = find(id); if (!it) return;
    it.qty += d;
    if (it.qty < 1) return remove(id);
    if (it.qty > MAX_QTY) it.qty = MAX_QTY;
    save(); render();
  }
  function remove(id) { cart = cart.filter(function (i) { return i.id !== id; }); save(); render(); }

  document.querySelectorAll('.add-to-cart').forEach(function (b) {
    b.addEventListener('click', function () {
      var id = Number(b.dataset.id), it = find(id);
      if (it) { it.qty = Math.min(MAX_QTY, it.qty + 1); }
      else cart.push({ id: id, name: b.dataset.name, price: Number(b.dataset.price), qty: 1 });
      save(); render();
    });
  });

  function openCart(o) { panel.classList.toggle('open', o); overlay.classList.toggle('show', o); }
  var tg = $('cart-toggle'); if (tg) tg.addEventListener('click', function () { openCart(true); });
  var cl = $('cart-close'); if (cl) cl.addEventListener('click', function () { openCart(false); });
  if (overlay) overlay.addEventListener('click', function () { openCart(false); });

  // Category tabs
  document.querySelectorAll('.tab').forEach(function (t) {
    t.addEventListener('click', function () {
      document.querySelectorAll('.tab').forEach(function (x) { x.classList.remove('active'); });
      t.classList.add('active');
      var c = t.dataset.cat;
      document.querySelectorAll('.product-card').forEach(function (card) {
        card.style.display = (c === 'all' || card.dataset.cat === c) ? '' : 'none';
      });
    });
  });

  // Pickup / delivery toggle
  var addrWrap = $('delivery-field'), addr = $('delivery_address'), note = $('fulfillment-note');
  var typeRadios = form.querySelectorAll('input[name="fulfillment_type"]');
  function syncType() {
    var sel = form.querySelector('input[name="fulfillment_type"]:checked');
    var del = !!sel && sel.value === 'delivery';
    addrWrap.style.display = del ? 'block' : 'none';
    addr.required = del;
    if (!del) addr.value = '';
    if (note) note.textContent = del ? (note.dataset.delivery || '') : (note.dataset.pickup || '');
  }
  Array.prototype.forEach.call(typeRadios, function (r) { r.addEventListener('change', syncType); });
  syncType();

  form.addEventListener('submit', function (e) {
    if (!cart.length || submitBtn.dataset.locked) { e.preventDefault(); return; }
    $('cart_json').value = JSON.stringify(cart.map(function (i) { return { id: i.id, qty: i.qty }; }));
    submitBtn.dataset.locked = '1';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Placing order\u2026';
  });

  render();
})();