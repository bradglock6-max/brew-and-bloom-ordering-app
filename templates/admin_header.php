<?php /** @var string $pageTitle @var array $adminUser */
require_once __DIR__ . '/../src/Models/OrderModel.php';
$headerOrderModel = new OrderModel(db());
$pendingNav = (int)$headerOrderModel->getSalesSummary()['pending_count'];
$storeOnlineNav = $headerOrderModel->getSetting('is_accepting_orders', '0') === '1';
$currentScript = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> - Brew &amp; Bloom</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(BASE_URL . '/assets/css/admin.css') ?>">
</head>
<body>
<div class="admin-shell">
  <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
  <aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin Navigation">
    <div class="sidebar-brand">
      <div>
        <span class="brand-title">Brew &amp; Bloom</span>
        <span class="brand-badge">POS &amp; ADMIN</span>
      </div>
      <button type="button" class="sidebar-close" id="sidebar-close" aria-label="Close Menu">&times;</button>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-group">
        <span class="nav-group-label">MAIN</span>
        <a href="dashboard.php" class="side-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
          <span>Dashboard</span>
        </a>
      </div>

      <div class="nav-group">
        <span class="nav-group-label">OPERATIONS</span>
        <a href="orders.php" class="side-link <?= $currentScript === 'orders.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
          <span>Live Orders</span>
          <span id="pending-orders-badge" class="nav-badge"<?= $pendingNav > 0 ? '' : ' style="display:none"' ?>><?= $pendingNav ?></span>
        </a>
      </div>

      <div class="nav-group">
        <span class="nav-group-label">CATALOG</span>
        <a href="products.php" class="side-link <?= $currentScript === 'products.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
          <span>Menu Products</span>
        </a>
        <a href="categories.php" class="side-link <?= $currentScript === 'categories.php' ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7" cy="7" r="1.5"/></svg>
          <span>Categories</span>
        </a>
      </div>
    </nav>

    <div class="sidebar-footer">
      <div class="admin-profile">
        <div class="avatar"><?= e(strtoupper(substr((string)($adminUser['username'] ?? 'A'), 0, 1))) ?></div>
        <div class="profile-meta">
          <strong><?= e($adminUser['username'] ?? '') ?></strong>
          <span class="badge"><?= e($adminUser['role'] ?? 'admin') ?></span>
        </div>
      </div>
      <a href="../index.php" target="_blank" rel="noopener" class="storefront-link">
        <span>Open Storefront</span>
        <span aria-hidden="true">&#8599;</span>
      </a>
      <form method="post" action="logout.php" class="logout-form">
        <?= csrfField() ?>
        <button type="submit" class="btn btn-outline btn-block btn-sm">Sign Out</button>
      </form>
    </div>
  </aside>

  <div class="admin-main">
    <header class="utility-bar">
      <div class="utility-left">
        <button type="button" class="hamburger-btn" id="sidebar-open" aria-label="Open Menu">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div>
          <span class="breadcrumb">Command Center / <?= e($pageTitle ?? 'Admin') ?></span>
          <h1 class="utility-title"><?= e($pageTitle ?? 'Admin') ?></h1>
        </div>
      </div>
      <div class="utility-right">
        <span class="store-pill <?= $storeOnlineNav ? 'online' : 'offline' ?>">
          <span class="dot"></span>
          <?= $storeOnlineNav ? 'Store Online' : 'Store Paused' ?>
        </span>
        <a href="products.php" class="btn btn-sm">+ New Product</a>
      </div>
    </header>
    <main class="container">
