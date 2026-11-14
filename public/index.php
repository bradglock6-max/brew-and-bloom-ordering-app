<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Models/CategoryModel.php';
require_once __DIR__ . '/../src/Models/ProductModel.php';
require_once __DIR__ . '/../src/Models/OrderModel.php';

startSecureSession();
$pdo = db();
$categories = (new CategoryModel($pdo))->getAll(true);
$products = (new ProductModel($pdo))->getAllWithCategory(true);
$open = (new OrderModel($pdo))->getSetting('is_accepting_orders', '0') === '1';
$storeName = (new OrderModel($pdo))->getSetting('store_name', 'Brew & Bloom');
$pickup = (new OrderModel($pdo))->getSetting('pickup_instructions');
$deliveryNote = (new OrderModel($pdo))->getSetting('delivery_note');
$error = $_SESSION['checkout_error'] ?? null;
unset($_SESSION['checkout_error']);

$activeCatIds = array_column($categories, 'id');
$products = array_values(array_filter($products, fn($p) => (int)$p['is_active'] === 1 && in_array($p['category_id'], $activeCatIds)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($storeName) ?> - Order Online</title>
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/store.css">
</head>
<body>
<header class="site-header">
  <h1><?= e($storeName) ?></h1>
  <div class="header-actions">
    <a href="order-status.php">Track Order</a>
    <button type="button" id="cart-toggle" class="btn btn-sm cart-toggle">Cart<span id="cart-count" class="cart-count">0</span></button>
  </div>
</header>
<div class="layout has-cart">
<main>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if (!$open): ?><div class="alert alert-error">Store is currently closed for online orders.</div><?php endif; ?>
  <div class="tabs">
    <button type="button" class="tab active" data-cat="all">All</button>
    <?php foreach ($categories as $c): ?>
      <button type="button" class="tab" data-cat="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></button>
    <?php endforeach; ?>
  </div>
  <div class="product-grid">
    <?php foreach ($products as $p): $sold = (int)$p['is_available'] === 0; ?>
    <article class="product-card" data-cat="<?= (int)$p['category_id'] ?>">
      <div class="img">
        <?php if ($p['image_path']): ?><img src="<?= e(BASE_URL . '/' . $p['image_path']) ?>" alt="<?= e($p['name']) ?>" loading="lazy"><?php else: ?><span aria-hidden="true">🧋</span><?php endif; ?>
        <?php if ($sold): ?><span class="badge-soldout">Sold Out</span><?php endif; ?>
      </div>
      <div class="product-body">
        <h3><?= e($p['name']) ?></h3>
        <p><?= e($p['description']) ?></p>
        <div class="price">₱<?= number_format((float)$p['price'], 2) ?></div>
        <button type="button" class="btn btn-sm add-to-cart" data-id="<?= (int)$p['id'] ?>" data-name="<?= e($p['name']) ?>" data-price="<?= e((string)$p['price']) ?>" <?= $sold ? 'disabled' : '' ?>><?= $sold ? 'Sold Out' : 'Add' ?></button>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</main>

<div id="cart-overlay" class="overlay"></div>
<aside id="cart-panel" class="cart-panel" aria-label="Cart">
  <div class="cart-head"><h2>Your Cart</h2><button type="button" id="cart-close" class="btn btn-sm btn-light cart-close">Close</button></div>
  <div class="cart-body"><div id="cart-items"></div></div>
  <div class="cart-foot">
    <div class="subtotal"><span>Subtotal</span><span id="cart-subtotal">₱0.00</span></div>
    <form id="checkout-form" class="checkout-form" method="post" action="checkout.php">
      <?= csrfField() ?>
      <input type="hidden" name="cart_json" id="cart_json" value="">
      <label for="customer_name">Name</label>
      <input type="text" id="customer_name" name="customer_name" maxlength="100" required>
      <label for="customer_phone">Phone</label>
      <input type="tel" id="customer_phone" name="customer_phone" maxlength="20" pattern="[0-9+\-\s]{7,20}" required>
      <label>Fulfillment</label>
      <div class="radio-row">
        <label><input type="radio" name="fulfillment_type" value="pickup" checked> Pickup</label>
        <label><input type="radio" name="fulfillment_type" value="delivery"> Delivery</label>
      </div>
      <small class="meta" id="fulfillment-note" data-pickup="<?= e($pickup) ?>" data-delivery="<?= e($deliveryNote) ?>"><?= e($pickup) ?></small>
      <div id="delivery-field" style="display:none">
        <label for="delivery_address">Delivery Address</label>
        <textarea id="delivery_address" name="delivery_address" rows="2" maxlength="500"></textarea>
      </div>
      <label for="notes">Notes (optional)</label>
      <input type="text" id="notes" name="notes" maxlength="255">
      <button type="submit" id="checkout-submit" class="btn btn-block" style="margin-top:1rem" data-closed="<?= $open ? '0' : '1' ?>" disabled>Place Order</button>
    </form>
  </div>
</aside>
</div>
<script src="<?= e(BASE_URL) ?>/assets/js/cart.js"></script>
</body>
</html>