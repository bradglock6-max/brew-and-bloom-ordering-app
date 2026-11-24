<?php require_once __DIR__ . '/../../src/bootstrap.php'; $adminUser = Auth::requireAdmin();
require_once __DIR__ . '/../../src/Models/OrderModel.php';

$pdo = db();
$orders = new OrderModel($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf($_POST['csrf_token'] ?? null);
    if (($_POST['action'] ?? '') === 'toggle_store') {
        $now = $orders->getSetting('is_accepting_orders', '0');
        $orders->updateSetting('is_accepting_orders', $now === '1' ? '0' : '1');
        $_SESSION['flash'] = ['ok', 'Store status updated.'];
    }
    header('Location: dashboard.php');
    exit;
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$s = $orders->getSalesSummary();
$accepting = $orders->getSetting('is_accepting_orders', '0') === '1';
$catCount = (int)$pdo->query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
$prodCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
$recentOrders = array_slice($orders->getOrdersByStatus(null), 0, 7);
$totalTracked = max(1, $s['pending_count'] + $s['active_count'] + $s['completed_count']);

$pageTitle = 'Dashboard';
require __DIR__ . '/../../templates/admin_header.php';
?>
<?php if ($flash): ?><div class="alert-success"><?= e($flash[1]) ?></div><?php endif; ?>

<section class="kpi-grid">
  <div class="kpi-card">
    <div>
      <span class="kpi-label">Today's Revenue</span>
      <div class="kpi-value">₱<?= number_format($s['today_revenue'], 2) ?></div>
      <span class="kpi-sub">All-time: ₱<?= number_format($s['total_revenue'], 2) ?></span>
    </div>
    <div class="kpi-icon">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
    </div>
  </div>

  <div class="kpi-card <?= $s['pending_count'] > 0 ? 'amber-glow' : '' ?>">
    <div>
      <span class="kpi-label">Pending Orders</span>
      <div class="kpi-value"><?= $s['pending_count'] ?></div>
      <span class="kpi-sub"><?= $s['active_count'] ?> currently in preparation</span>
    </div>
    <div class="kpi-icon amber">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
  </div>

  <div class="kpi-card">
    <div>
      <span class="kpi-label">Active Menu Items</span>
      <div class="kpi-value"><?= $prodCount ?></div>
      <span class="kpi-sub">Across <?= $catCount ?> active categories</span>
    </div>
    <div class="kpi-icon sky">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>
    </div>
  </div>

  <div class="kpi-card">
    <div>
      <span class="kpi-label">Completed Orders</span>
      <div class="kpi-value"><?= $s['completed_count'] ?></div>
      <span class="kpi-sub">Fulfilled &amp; settled tickets</span>
    </div>
    <div class="kpi-icon emerald">
      <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    </div>
  </div>
</section>

<div class="dash-split">
  <div class="panel">
    <div class="panel-head">
      <h3>Recent Orders Queue</h3>
      <a href="orders.php" class="btn btn-sm btn-secondary">View All Orders &rarr;</a>
    </div>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Order Code</th><th>Customer</th><th>Fulfillment</th><th>Total</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if (!$recentOrders): ?>
          <tr><td colspan="6" class="meta">No orders placed yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($recentOrders as $ro): ?>
          <tr>
            <td><strong><?= e($ro['order_code']) ?></strong><br><span class="meta"><?= e($ro['created_at']) ?></span></td>
            <td><?= e($ro['customer_name']) ?><br><span class="meta"><?= e($ro['customer_phone']) ?></span></td>
            <td><span class="status <?= $ro['fulfillment_type'] === 'delivery' ? 'status-delivery' : 'status-pickup' ?>" style="margin:0"><?= e(ucfirst($ro['fulfillment_type'])) ?></span></td>
            <td><strong>₱<?= number_format((float)$ro['total_amount'], 2) ?></strong></td>
            <td><span class="status status-o-<?= e($ro['status']) ?>"><?= e(ucfirst($ro['status'])) ?></span></td>
            <td><a href="orders.php" class="btn btn-sm btn-outline">Manage &rarr;</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div>
    <div class="panel">
      <h3>Quick Operations &amp; Store Summary</h3>
      <div class="quick-cards">
        <a href="orders.php?status=pending" class="quick-action-card">
          <span>Review Pending Orders (<?= $s['pending_count'] ?>)</span>
          <span>&rarr;</span>
        </a>
        <a href="products.php" class="quick-action-card">
          <span>Add Menu Item</span>
          <span>+</span>
        </a>
        <a href="../index.php" target="_blank" rel="noopener" class="quick-action-card">
          <span>Preview Customer Menu</span>
          <span>&#8599;</span>
        </a>
      </div>

      <div class="breakdown-list">
        <div class="breakdown-row">
          <div class="breakdown-head"><span>Pending Queue</span><strong><?= $s['pending_count'] ?></strong></div>
          <div class="breakdown-bar"><div class="breakdown-fill amber" style="width:<?= min(100, round(($s['pending_count'] / $totalTracked) * 100)) ?>%"></div></div>
        </div>
        <div class="breakdown-row">
          <div class="breakdown-head"><span>In Progress (Accepted / Preparing / Ready)</span><strong><?= $s['active_count'] ?></strong></div>
          <div class="breakdown-bar"><div class="breakdown-fill sky" style="width:<?= min(100, round(($s['active_count'] / $totalTracked) * 100)) ?>%"></div></div>
        </div>
        <div class="breakdown-row">
          <div class="breakdown-head"><span>Completed</span><strong><?= $s['completed_count'] ?></strong></div>
          <div class="breakdown-bar"><div class="breakdown-fill emerald" style="width:<?= min(100, round(($s['completed_count'] / $totalTracked) * 100)) ?>%"></div></div>
        </div>
      </div>
    </div>

    <div class="panel store-toggle">
      <div>
        <strong>Online Store Status</strong><br>
        <span class="status <?= $accepting ? 'status-open' : 'status-closed' ?>"><?= $accepting ? 'Accepting Orders' : 'Paused / Closed' ?></span>
      </div>
      <form method="post" action="dashboard.php" style="margin:0">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="toggle_store">
        <button type="submit" class="btn btn-sm <?= $accepting ? 'btn-danger' : '' ?>" style="margin:0"><?= $accepting ? 'Pause Orders' : 'Resume Orders' ?></button>
      </form>
    </div>

    <div class="panel store-toggle">
      <div>
        <strong>Database Backup</strong><br>
        <span class="meta">Download a .sql snapshot (excludes user accounts).</span>
      </div>
      <form method="post" action="backup.php" style="margin:0">
        <?= csrfField() ?>
        <button type="submit" class="btn btn-sm btn-secondary" style="margin:0">Download Backup</button>
      </form>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../../templates/admin_footer.php'; ?>
