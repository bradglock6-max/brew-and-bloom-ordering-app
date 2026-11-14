</main>
<script>
(function () {
  var badge = document.getElementById('pending-orders-badge');
  if (!badge) return;
  var last = parseInt(badge.textContent, 10) || 0;
  var ctx = null;
  function unlock() {
    try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); if (ctx.state === 'suspended') ctx.resume(); } catch (e) {}
  }
  document.addEventListener('click', unlock);
  document.addEventListener('keydown', unlock);
  function beep() {
    if (!ctx || ctx.state !== 'running') return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.type = 'sine'; o.frequency.value = 880;
      g.gain.setValueAtTime(0.0001, ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.15, ctx.currentTime + 0.02);
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.5);
      o.connect(g); g.connect(ctx.destination);
      o.start(); o.stop(ctx.currentTime + 0.5);
    } catch (e) {}
  }
  function poll() {
    fetch(<?= json_encode(BASE_URL . '/admin/api-pending-count.php') ?>, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d || typeof d.pending !== 'number') return;
        badge.textContent = d.pending;
        badge.style.display = d.pending > 0 ? '' : 'none';
        if (d.pending > last) beep();
        last = d.pending;
      }).catch(function () {});
  }
  setInterval(poll, 15000);
})();
</script>
</body>
</html>