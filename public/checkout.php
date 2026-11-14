<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/OrderService.php';

startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}
verifyCsrf($_POST['csrf_token'] ?? null);

try {
    $decoded = json_decode((string)($_POST['cart_json'] ?? ''), true);
    if (!is_array($decoded) || count($decoded) > 50) {
        throw new RuntimeException('Your cart is empty or invalid.');
    }
    $cart = [];
    foreach ($decoded as $row) {
        $cart[] = ['product_id' => $row['id'] ?? null, 'quantity' => $row['qty'] ?? null];
    }
    $customer = [
        'customer_name'    => $_POST['customer_name'] ?? '',
        'customer_phone'   => $_POST['customer_phone'] ?? '',
        'fulfillment_type' => $_POST['fulfillment_type'] ?? '',
        'delivery_address' => $_POST['delivery_address'] ?? '',
        'notes'            => $_POST['notes'] ?? '',
    ];
    $code = OrderService::placeOrder(db(), $customer, $cart);
    $_SESSION['order_flash'] = [
        'code'  => $code,
        'phone' => OrderModel::normalizePhone((string)$customer['customer_phone']),
    ];
    header('Location: order-status.php?code=' . urlencode($code));
    exit;
} catch (RuntimeException $e) {
    $_SESSION['checkout_error'] = $e->getMessage();
} catch (Throwable $e) {
    @error_log('[' . date('Y-m-d H:i:s') . '] Checkout error: ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../storage/logs/app.log');
    $_SESSION['checkout_error'] = 'Something went wrong placing your order. Please try again.';
}
header('Location: index.php');
exit;