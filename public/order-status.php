<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Models/OrderModel.php';

startSecureSession();
$model = new OrderModel(db());

$order = null;
$fresh = false;
$error = '';
$prefill = strtoupper(trim((string)($_GET['code'] ?? '')));
$prefill = preg_match('/^[A-Z0-9\-]{1,20}$/', $prefill) ? $prefill : '';

$flash = $_SESSION['order_flash'] ?? null;
unset($_SESSION['order_flash']);

if ($flash && is_array($flash)) {
    $order = $model->findByCodeAndPhone((string)$flash['code'], (string)$flash['phone']);
    $fresh = $order !== null;
    $prefill = (string)$flash['code'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf($_POST['csrf_token'] ?? null);
    $code = (string)($_POST['code'] ?? '');
    $phone = (string)($_POST['phone'] ?? '');
    $prefill = strtoupper(trim($code));
    $order = $model->findByCodeAndPhone($code, $phone);
    if (!$order) {
        $error = 'No order found for that reference code and phone number.';
    }
}

$steps = ['pending', 'accepted', 'preparing', 'ready', 'completed'];
$bad = $order && in_array($order['status'], ['rejected', 'cancelled'], true);
$idx = $order ? array_search($order['status'], $steps, true) : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Order Status - Brew &amp; Bloom</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/store.css">
</head>
<body>
<header class="site-header">
  <h1>Brew &amp; Bloom</h1>
  <div class="header-actions"><a href="index.php">&larr; Back to Menu</a></div>
</header>
<div class="layout"><div class="narrow">
<?php if ($fresh): ?>
  <div class="alert alert-info">Thank you! Your order has been received. Save your reference code: <strong><?= e($order['order_code']) ?></strong></div>
  <script>try{localStorage.removeItem('bb_cart_v1');}catch(e){}</script>
<?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<?php if (!$order): ?>
<div class="card">
  <h2>Track Your Order</h2>
  <p class="meta">Enter your order reference code and the phone number used at checkout to check live preparation progress.</p>
  <form method="post" action="order-status.php" class="checkout-form">
    <?= csrfField() ?>
    <label for="code">Order Reference Code</label>
    <input type="text" id="code" name="code" maxlength="20" required value="<?= e($prefill) ?>" placeholder="BB-XXXXXX">
    <label for="phone">Phone Number</label>
    <input type="tel" id="phone" name="phone" maxlength="20" required>
    <button type="submit" class="btn btn-block" style="margin-top:1.15rem">Check Status</button>
  </form>
</div>
<?php else: ?>
<div class="card">
  <h2><span>Order <?= e($order['order_code']) ?></span> <span class="status-pill <?= $bad ? 'bad' : '' ?>"><?= e($order['status']) ?></span></h2>
  <p class="meta">Placed <?= e($order['created_at']) ?> &middot; <?= e(ucfirst($order['fulfillment_type'])) ?></p>

  <?php if ($bad): ?>
    <div class="alert alert-error" style="margin-top:1rem">This order was <?= e($order['status']) ?>.<?= $order['rejection_reason'] ? ' Reason: ' . e($order['rejection_reason']) : '' ?></div>
  <?php else: ?>
  <ol class="tracker">
    <?php foreach ($steps as $i => $s): $cls = $i < $idx ? 'done' : ($i === $idx ? 'current' : ''); ?>
      <li class="<?= $cls ?>"><span><?= $i < $idx ? '&#10003;' : $i + 1 ?></span><?= e($s) ?></li>
    <?php endforeach; ?>
  </ol>
  <?php endif; ?>

  <p><strong><?= e($order['customer_name']) ?></strong><?php if ($order['fulfillment_type'] === 'delivery'): ?><br><span class="meta"><?= nl2br(e($order['delivery_address'])) ?></span><?php endif; ?>
  <?php if ($order['notes']): ?><br><span class="meta">Notes: <?= e($order['notes']) ?></span><?php endif; ?></p>

  <table class="receipt">
    <thead><tr><th>Item</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Total</th></tr></thead>
    <tbody>
    <?php foreach ($order['items'] as $it): ?>
      <tr><td><?= e($it['product_name_snapshot']) ?></td><td class="num"><?= (int)$it['quantity'] ?></td><td class="num">₱<?= number_format((float)$it['unit_price_snapshot'], 2) ?></td><td class="num">₱<?= number_format((float)$it['line_total'], 2) ?></td></tr>
    <?php endforeach; ?>
      <tr class="total-row"><td colspan="3">Total</td><td class="num">₱<?= number_format((float)$order['total_amount'], 2) ?></td></tr>
    </tbody>
  </table>
  <p style="margin-top:1.25rem;margin-bottom:0"><a class="btn btn-light btn-sm" href="order-status.php">Look up another order</a></p>
</div>
<?php endif; ?>
</div></div>
</body>
</html>
