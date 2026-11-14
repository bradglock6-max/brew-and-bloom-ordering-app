<?php require_once __DIR__ . '/../../src/bootstrap.php'; $adminUser = Auth::requireAdmin();
require_once __DIR__ . '/../../src/Models/OrderModel.php';
require_once __DIR__ . '/../../src/Services/OrderService.php';

$pdo = db();
$model = new OrderModel($pdo);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$tabs = [
    'all' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'preparing' => 'Preparing',
    'ready' => 'Ready', 'completed' => 'Completed', 'closed' => 'Rejected/Cancelled',
];
$filter = (string)($_GET['status'] ?? 'all');
if (!isset($tabs[$filter])) {
    $filter = 'all';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf($_POST['csrf_token'] ?? null);
    $back = 'orders.php' . ($filter !== 'all' ? '?status=' . urlencode($filter) : '');
    if (isset($_POST['return']) && isset($tabs[$_POST['return']]) && $_POST['return'] !== 'all') {
        $back = 'orders.php?status=' . urlencode((string)$_POST['return']);
    }
    try {
        OrderService::transitionStatus(
            $pdo,
            (int)($_POST['order_id'] ?? 0),
            (string)($_POST['new_status'] ?? ''),
            isset($_POST['reason']) ? (string)$_POST['reason'] : null
        );
        $_SESSION['flash'] = ['ok', 'Order updated.'];
    } catch (RuntimeException $e) {
        $_SESSION['flash'] = ['err', $e->getMessage()];
    } catch (PDOException $e) {
        @error_log('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../../storage/logs/app.log');
        $_SESSION['flash'] = ['err', 'Database error.'];
    }
    header('Location: ' . $back);
    exit;
}

if ($filter === 'closed') {
    $orders = array_values(array_filter($model->getOrdersByStatus(null), fn($o) => in_array($o['status'], ['rejected', 'cancelled'], true)));
} else {
    $orders = $model->getOrdersByStatus($filter === 'all' ? null : $filter);
}
$labels = ['accepted' => 'Accept', 'preparing' => 'Start Preparing', 'ready' => 'Mark Ready', 'completed' => 'Complete', 'cancelled' => 'Cancel Order'];
$pageTitle = 'Orders';
require __DIR__ . '/../../templates/admin_header.php';
?>
<h2>Orders</h2>
<?php if ($flash): ?><div class="<?= $flash[0] === 'ok' ? 'alert-success' : 'alert-error' ?>"><?= e($flash[1]) ?></div><?php endif; ?>
<div class="filter-tabs">
  <?php foreach ($tabs as $k => $label): ?>
    <a href="orders.php<?= $k === 'all' ? '' : '?status=' . e($k) ?>" class="<?= $filter === $k ? 'active' : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$orders): ?><div class="panel">No orders found.</div><?php endif; ?>
<?php foreach ($orders as $o): $next = OrderService::TRANSITIONS[$o['status']] ?? []; ?>
<div class="panel order-card">
  <div class="order-head">
    <div><strong><?= e($o['order_code']) ?></strong> <span class="meta"><?= e($o['created_at']) ?></span></div>
    <span class="status status-o-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span>
  </div>
  <div class="order-cust">
    <strong><?= e($o['customer_name']) ?></strong> &middot; <?= e($o['customer_phone']) ?>
    <span class="status <?= $o['fulfillment_type'] === 'delivery' ? 'status-delivery' : 'status-pickup' ?>"><?= e(ucfirst($o['fulfillment_type'])) ?></span>
    <?php if ($o['fulfillment_type'] === 'delivery'): ?><div class="meta"><?= nl2br(e($o['delivery_address'])) ?></div><?php endif; ?>
    <?php if ($o['notes']): ?><div class="meta">Notes: <?= e($o['notes']) ?></div><?php endif; ?>
    <?php if ($o['rejection_reason']): ?><div class="meta">Reason: <?= e($o['rejection_reason']) ?></div><?php endif; ?>
  </div>
  <ul class="order-items">
    <?php foreach ($o['items'] as $it): ?>
      <li><span><?= (int)$it['quantity'] ?> &times; <?= e($it['product_name_snapshot']) ?></span><span>₱<?= number_format((float)$it['line_total'], 2) ?></span></li>
    <?php endforeach; ?>
    <li class="order-total"><span>Total</span><span>₱<?= number_format((float)$o['total_amount'], 2) ?></span></li>
  </ul>
  <?php if ($next): ?>
  <div class="actions">
    <?php foreach ($next as $s): ?>
      <?php if ($s === 'rejected'): ?>
        <details class="reject-box">
          <summary class="btn btn-sm btn-danger">Reject</summary>
          <form method="post" action="orders.php<?= $filter !== 'all' ? '?status=' . e($filter) : '' ?>" class="reject-form">
            <?= csrfField() ?>
            <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
            <input type="hidden" name="new_status" value="rejected">
            <input type="text" name="reason" maxlength="255" placeholder="Reason for rejection" required>
            <button type="submit" class="btn btn-sm btn-danger">Confirm Reject</button>
          </form>
        </details>
      <?php else: ?>
        <form method="post" action="orders.php<?= $filter !== 'all' ? '?status=' . e($filter) : '' ?>"<?= $s === 'cancelled' ? ' onsubmit="return confirm(\'Cancel this order?\');"' : '' ?>>
          <?= csrfField() ?>
          <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
          <input type="hidden" name="new_status" value="<?= e($s) ?>">
          <button type="submit" class="btn btn-sm <?= $s === 'cancelled' ? 'btn-danger' : '' ?>"><?= e($labels[$s] ?? ucfirst($s)) ?></button>
        </form>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>
<?php require __DIR__ . '/../../templates/admin_footer.php'; ?>