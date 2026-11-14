<?php /** @var string $pageTitle @var array $adminUser */
require_once __DIR__ . '/../src/Models/OrderModel.php';
$pendingNav = (int)(new OrderModel(db()))->getSalesSummary()['pending_count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> - Brew &amp; Bloom</title>
<link rel="stylesheet" href="<?= e(BASE_URL . '/assets/css/admin.css') ?>">
</head>
<body>
<nav class="navbar">
  <span class="brand">Brew &amp; Bloom</span>
  <div class="nav-links">
    <a href="dashboard.php">Dashboard</a>
    <a href="orders.php">Orders<span id="pending-orders-badge" class="nav-badge"<?= $pendingNav > 0 ? '' : ' style="display:none"' ?>><?= $pendingNav ?></span></a>
    <a href="products.php">Products</a>
    <a href="categories.php">Categories</a>
  </div>
  <div class="nav-user">
    <span><?= e($adminUser['username'] ?? '') ?><span class="badge"><?= e($adminUser['role'] ?? '') ?></span></span>
    <form method="post" action="logout.php">
      <?= csrfField() ?>
      <button type="submit" class="btn btn-outline">Log out</button>
    </form>
  </div>
</nav>
<main class="container">