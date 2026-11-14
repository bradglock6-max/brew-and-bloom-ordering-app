<?php require_once __DIR__ . '/../../src/bootstrap.php'; $adminUser = Auth::requireAdmin();
require_once __DIR__ . '/../../src/Models/CategoryModel.php';

$model = new CategoryModel(db());
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf($_POST['csrf_token'] ?? null);
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'create') {
            $name = trim((string)($_POST['name'] ?? ''));
            $sort = (int)($_POST['sort_order'] ?? 0);
            if ($name === '' || mb_strlen($name) > 80) {
                throw new RuntimeException('Category name is required (max 80 characters).');
            }
            $model->create($name, $sort);
            $_SESSION['flash'] = ['ok', 'Category added.'];
        } elseif ($action === 'toggle') {
            $model->toggleActive((int)($_POST['id'] ?? 0));
            $_SESSION['flash'] = ['ok', 'Category updated.'];
        }
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['err', ((int)$e->getCode() === 23000) ? 'A category with that name already exists.' : 'Database error.'];
    } catch (RuntimeException $e) {
        $_SESSION['flash'] = ['err', $e->getMessage()];
    }
    header('Location: categories.php');
    exit;
}

$categories = $model->getAll();
$pageTitle = 'Categories';
require __DIR__ . '/../../templates/admin_header.php';
?>
<h2>Categories</h2>
<?php if ($flash): ?><div class="<?= $flash[0] === 'ok' ? 'alert-success' : 'alert-error' ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="panel">
  <h3>Add Category</h3>
  <form method="post" action="categories.php">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-grid">
      <div><label for="name">Name</label><input type="text" id="name" name="name" maxlength="80" required></div>
      <div><label for="sort_order">Sort Order</label><input type="number" id="sort_order" name="sort_order" value="0"></div>
    </div>
    <button type="submit" class="btn">Add Category</button>
  </form>
</div>

<div class="panel table-wrap">
  <table class="data">
    <thead><tr><th>Name</th><th>Sort</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach ($categories as $c): ?>
      <tr>
        <td><?= e($c['name']) ?></td>
        <td><?= (int)$c['sort_order'] ?></td>
        <td><span class="status <?= $c['is_active'] ? 'status-available' : 'status-inactive' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
        <td>
          <form method="post" action="categories.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit" class="btn btn-sm btn-secondary"><?= $c['is_active'] ? 'Deactivate' : 'Activate' ?></button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../../templates/admin_footer.php'; ?>