<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

startSecureSession();

if (!empty($_SESSION['admin_user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf($_POST['csrf_token'] ?? null);
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($username !== '' && $password !== '' && loginAdmin(db(), $username, $password)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login - Brew &amp; Bloom</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/admin.css">
</head>
<body>
<div class="login-wrap">
  <form class="card" method="post" action="login.php" autocomplete="off">
    <span class="eyebrow">ROASTERY COMMAND CENTER</span>
    <h1>Brew &amp; Bloom Admin</h1>
    <?php if ($error !== ''): ?><div class="alert"><?= e($error) ?></div><?php endif; ?>
    <?= csrfField() ?>
    <label for="username">Username</label>
    <input type="text" id="username" name="username" maxlength="50" required autofocus>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <button type="submit" class="btn btn-block">Sign in</button>
  </form>
</div>
</body>
</html>
