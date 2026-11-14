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
$pageTitle = 'Dashboard';
require __DIR__ . '/../../templates/admin_header.php';
?>
<h2>Welcome, <?= e($adminUser['username']) ?></h2>
<?php if ($flash): ?><div class="alert-success"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="panel store-toggle" style="margin-bottom:1rem">
  <div>
    <strong>Online Store Status</strong><br>
    <span class="status <?= $accepting ? 'status-open' : 'status-closed' ?>"><?= $accepting ? 'Accepting Orders' : 'Paused / Closed' ?></span>
  </div>
  <form method="post" action="dashboard.php" style="margin:0">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="toggle_store">
    <button type="submit" class="btn <?= $accepting ? 'btn-danger' : '' ?>" style="margin:0"><?= $accepting ? 'Pause Orders' : 'Resume Orders' ?></button>
  </form>
</div>

<div class="grid">
  <div class="panel"><span>Pending Orders</span><div class="stat"><?= $s['pending_count'] ?></div></div>
  <div class="panel"><span>In Progress</span><div class="stat"><?= $s['active_count'] ?></div></div>
  <div class="panel"><span>Completed Orders</span><div class="stat"><?= $s['completed_count'] ?></div></div>
  <div class="panel"><span>Today's Revenue</span><div class="stat">₱<?= number_format($s['today_revenue'], 2) ?></div></div>
  <div class="panel"><span>All-Time Completed Revenue</span><div class="stat">₱<?= number_format($s['total_revenue'], 2) ?></div></div>
  <div class="panel"><span>Active Categories</span><div class="stat"><?= $catCount ?></div></div>
  <div class="panel"><span>Active Products</span><div class="stat"><?= $prodCount ?></div></div>
</div>
<div class="panel store-toggle" style="margin-bottom:1rem">
  <div>
    <strong>Database Backup</strong><br>
    <span class="meta">Download a .sql snapshot (excludes user accounts).</span>
  </div>
  <form method="post" action="backup.php" style="margin:0">
    <?= csrfField() ?>
    <button type="submit" class="btn btn-secondary" style="margin:0">Download Backup</button>
  </form>
</div>

<div class="quick">
  <a class="btn" href="orders.php">Manage Orders</a>
  <a class="btn" href="products.php">Manage Products</a>
  <a class="btn" href="categories.php">Manage Categories</a>
</div>
<?php require __DIR__ . '/../../templates/admin_footer.php'; ?>