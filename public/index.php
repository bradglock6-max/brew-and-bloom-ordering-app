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

function fallbackProductImage(string $name, string $categoryName): string
{
    $hay = strtolower($name . ' ' . $categoryName);
    if (str_contains($hay, 'brown sugar') || str_contains($hay, 'pearl')) {
        return 'https://images.unsplash.com/photo-1558857563-b371033873b8?auto=format&fit=crop&w=700&q=80';
    }
    if (str_contains($hay, 'wintermelon') || str_contains($hay, 'milk tea')) {
        return 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?auto=format&fit=crop&w=700&q=80';
    }
    if (str_contains($hay, 'matcha') || str_contains($hay, 'green tea') || str_contains($hay, 'passion') || str_contains($hay, 'fruit')) {
        return 'https://images.unsplash.com/photo-1515823064-d6e0c04616a7?auto=format&fit=crop&w=700&q=80';
    }
    if (str_contains($hay, 'cold brew') || str_contains($hay, 'caramel')) {
        return 'https://images.unsplash.com/photo-1461023058943-07fcbe16d735?auto=format&fit=crop&w=700&q=80';
    }
    if (str_contains($hay, 'latte') || str_contains($hay, 'espresso') || str_contains($hay, 'coffee')) {
        return 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=700&q=80';
    }
    if (str_contains($hay, 'waffle') || str_contains($hay, 'snack') || str_contains($hay, 'pastry')) {
        return 'https://images.unsplash.com/photo-1562376552-0d160a2f238d?auto=format&fit=crop&w=700&q=80';
    }
    return 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=700&q=80';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($storeName) ?> - Order Online</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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
  <section class="hero-banner">
    <span class="hero-eyebrow">SMALL-BATCH ROASTS &amp; ONLINE ORDERING</span>
    <h2>Artisanal Espresso, Signature Milk Teas &amp; Warm Pastries</h2>
    <p>Crafted to order with single-origin espresso, ceremonial-grade tea leaves, and house-made syrups for pickup or local delivery.</p>
  </section>

  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <?php if (!$open): ?><div class="alert alert-error">Store is currently closed for online orders.</div><?php endif; ?>
  <div class="tabs">
    <button type="button" class="tab active" data-cat="all">All</button>
    <?php foreach ($categories as $c): ?>
      <button type="button" class="tab" data-cat="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></button>
    <?php endforeach; ?>
  </div>
  <div class="product-grid">
    <?php foreach ($products as $p):
        $sold = (int)$p['is_available'] === 0;
        $imgSrc = $p['image_path']
            ? BASE_URL . '/' . $p['image_path']
            : fallbackProductImage((string)$p['name'], (string)$p['category_name']);
    ?>
    <article class="product-card" data-cat="<?= (int)$p['category_id'] ?>">
      <div class="img">
        <img src="<?= e($imgSrc) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        <?php if ($sold): ?><span class="badge-soldout">Sold Out</span><?php endif; ?>
      </div>
      <div class="product-body">
        <h3><?= e($p['name']) ?></h3>
        <p><?= e($p['description']) ?></p>
        <div class="product-foot">
          <div class="price">₱<?= number_format((float)$p['price'], 2) ?></div>
          <button type="button" class="btn btn-sm add-to-cart" data-id="<?= (int)$p['id'] ?>" data-name="<?= e($p['name']) ?>" data-price="<?= e((string)$p['price']) ?>" <?= $sold ? 'disabled' : '' ?>><?= $sold ? 'Sold Out' : 'Add to Order' ?></button>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</main>

<div id="cart-overlay" class="overlay"></div>
<aside id="cart-panel" class="cart-panel" aria-label="Cart">
  <div class="cart-head"><h2>Your Order</h2><button type="button" id="cart-close" class="btn btn-sm btn-light cart-close">Close</button></div>
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
