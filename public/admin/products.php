<?php require_once __DIR__ . '/../../src/bootstrap.php'; $adminUser = Auth::requireAdmin();
require_once __DIR__ . '/../../src/Helpers/Uploader.php';
require_once __DIR__ . '/../../src/Models/CategoryModel.php';
require_once __DIR__ . '/../../src/Models/ProductModel.php';

$pdo = db();
$products = new ProductModel($pdo);
$cats = new CategoryModel($pdo);
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf($_POST['csrf_token'] ?? null);
    $action = (string)($_POST['action'] ?? '');
    $redirect = 'products.php';
    try {
        if ($action === 'create' || $action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            $desc = trim((string)($_POST['description'] ?? ''));
            $priceRaw = trim((string)($_POST['price'] ?? ''));
            $catId = (int)($_POST['category_id'] ?? 0);
            if ($action === 'update') {
                $redirect = 'products.php?edit=' . $id;
            }
            if ($name === '' || mb_strlen($name) > 120) {
                throw new RuntimeException('Product name is required (max 120 characters).');
            }
            if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $priceRaw)) {
                throw new RuntimeException('Enter a valid price.');
            }
            $stmt = $pdo->prepare('SELECT 1 FROM categories WHERE id = ?');
            $stmt->execute([$catId]);
            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('Select a valid category.');
            }
            $existing = null;
            if ($action === 'update') {
                $existing = $products->findById($id);
                if (!$existing) {
                    throw new RuntimeException('Product not found.');
                }
            }
            $imagePath = Uploader::uploadProductImage($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            $data = [
                'category_id' => $catId,
                'name' => $name,
                'description' => $desc !== '' ? $desc : null,
                'price' => number_format((float)$priceRaw, 2, '.', ''),
                'image_path' => $imagePath,
                'is_available' => isset($_POST['is_available']) ? 1 : 0,
            ];
            try {
                if ($action === 'create') {
                    $products->create($data);
                    $_SESSION['flash'] = ['ok', 'Product added.'];
                } else {
                    $products->update($id, $data);
                    if ($imagePath !== null) {
                        Uploader::deleteFile($existing['image_path']);
                    }
                    $_SESSION['flash'] = ['ok', 'Product updated.'];
                    $redirect = 'products.php';
                }
            } catch (Throwable $t) {
                Uploader::deleteFile($imagePath);
                throw $t;
            }
        } elseif ($action === 'toggle_availability') {
            $products->toggleAvailability((int)($_POST['id'] ?? 0));
            $_SESSION['flash'] = ['ok', 'Availability updated.'];
        } elseif ($action === 'delete') {
            $products->softDelete((int)($_POST['id'] ?? 0));
            $_SESSION['flash'] = ['ok', 'Product deactivated.'];
        }
    } catch (RuntimeException $e) {
        $_SESSION['flash'] = ['err', $e->getMessage()];
    } catch (PDOException $e) {
        @error_log('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../../storage/logs/app.log');
        $_SESSION['flash'] = ['err', 'Database error.'];
    }
    header('Location: ' . $redirect);
    exit;
}

$edit = null;
if (isset($_GET['edit'])) {
    $edit = $products->findById((int)$_GET['edit']);
}
$categories = $cats->getAll(true);
$rows = $products->getAllWithCategory(true);
$pageTitle = 'Products';
require __DIR__ . '/../../templates/admin_header.php';
?>
<h2>Products</h2>
<?php if ($flash): ?><div class="<?= $flash[0] === 'ok' ? 'alert-success' : 'alert-error' ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="panel">
  <h3><?= $edit ? 'Edit Product' : 'Add Product' ?></h3>
  <form method="post" action="products.php" enctype="multipart/form-data">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
    <div class="form-grid">
      <div><label for="name">Name</label><input type="text" id="name" name="name" maxlength="120" required value="<?= e($edit['name'] ?? '') ?>"></div>
      <div><label for="category_id">Category</label>
        <select id="category_id" name="category_id" required>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($edit && (int)$edit['category_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div><label for="price">Price (₱)</label><input type="number" id="price" name="price" step="0.01" min="0" required value="<?= e(isset($edit['price']) ? (string)$edit['price'] : '') ?>"></div>
      <div><label for="image">Image (JPG/PNG/WEBP, max 2MB)</label><input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
        <?php if ($edit && $edit['image_path']): ?><div class="current-img"><img class="thumb" src="<?= e(BASE_URL . '/' . $edit['image_path']) ?>" alt=""><small>Current image</small></div><?php endif; ?></div>
      <div class="full"><label for="description">Description</label><textarea id="description" name="description" rows="3"><?= e($edit['description'] ?? '') ?></textarea></div>
      <div class="full"><label><input type="checkbox" name="is_available" value="1" <?= (!$edit || $edit['is_available']) ? 'checked' : '' ?>> Available</label></div>
    </div>
    <button type="submit" class="btn"><?= $edit ? 'Save Changes' : 'Add Product' ?></button>
    <?php if ($edit): ?><a class="btn btn-secondary" href="products.php">Cancel</a><?php endif; ?>
  </form>
</div>

<div class="panel table-wrap">
  <table class="data">
    <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
      <tr>
        <td><?php if ($p['image_path']): ?><img class="thumb" src="<?= e(BASE_URL . '/' . $p['image_path']) ?>" alt=""><?php else: ?><span class="thumb"></span><?php endif; ?></td>
        <td><?= e($p['name']) ?></td>
        <td><?= e($p['category_name']) ?></td>
        <td>₱<?= number_format((float)$p['price'], 2) ?></td>
        <td><span class="status <?= $p['is_available'] ? 'status-available' : 'status-soldout' ?>"><?= $p['is_available'] ? 'Available' : 'Sold Out' ?></span></td>
        <td><div class="actions">
          <form method="post" action="products.php"><?= csrfField() ?><input type="hidden" name="action" value="toggle_availability"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button type="submit" class="btn btn-sm btn-secondary"><?= $p['is_available'] ? 'Mark Sold Out' : 'Mark Available' ?></button></form>
          <a class="btn btn-sm" href="products.php?edit=<?= (int)$p['id'] ?>">Edit</a>
          <form method="post" action="products.php" onsubmit="return confirm('Deactivate this product?');"><?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button type="submit" class="btn btn-sm btn-danger">Deactivate</button></form>
        </div></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require __DIR__ . '/../../templates/admin_footer.php'; ?>