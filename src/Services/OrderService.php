<?php
declare(strict_types=1);

require_once __DIR__ . '/../Models/OrderModel.php';

final class OrderService
{
    public static function placeOrder(PDO $pdo, array $customerData, array $cartItems): string
    {
        $model = new OrderModel($pdo);
        if ($model->getSetting('is_accepting_orders', '0') !== '1') {
            throw new RuntimeException('Store is currently closed for online orders.');
        }

        // --- Customer validation ---
        $name = trim((string)($customerData['customer_name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            throw new RuntimeException('Please enter your name (max 100 characters).');
        }
        $phoneRaw = trim((string)($customerData['customer_phone'] ?? ''));
        if (!preg_match('/^[0-9+\-\s]{7,20}$/', $phoneRaw)) {
            throw new RuntimeException('Please enter a valid phone number.');
        }
        $phone = OrderModel::normalizePhone($phoneRaw);
        $type = (string)($customerData['fulfillment_type'] ?? '');
        if (!in_array($type, ['pickup', 'delivery'], true)) {
            throw new RuntimeException('Invalid fulfillment type.');
        }
        $address = null;
        if ($type === 'delivery') {
            $address = trim((string)($customerData['delivery_address'] ?? ''));
            if ($address === '' || mb_strlen($address) > 500) {
                throw new RuntimeException('Delivery address is required (max 500 characters).');
            }
        }
        $notes = trim((string)($customerData['notes'] ?? ''));
        if (mb_strlen($notes) > 255) {
            throw new RuntimeException('Notes must be 255 characters or fewer.');
        }
        $notes = $notes !== '' ? $notes : null;

        // --- Cart validation (merge duplicate products) ---
        if (!$cartItems) {
            throw new RuntimeException('Your cart is empty.');
        }
        $qtyById = [];
        foreach ($cartItems as $item) {
            if (!is_array($item)) {
                throw new RuntimeException('Invalid cart.');
            }
            $id = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
            $qty = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($id === false || $id <= 0 || $qty === false || $qty < 1 || $qty > 20) {
                throw new RuntimeException('Invalid cart item or quantity (1-20 per item).');
            }
            $qtyById[$id] = ($qtyById[$id] ?? 0) + $qty;
            if ($qtyById[$id] > 20) {
                throw new RuntimeException('Maximum quantity per item is 20.');
            }
        }

        // --- Trusted product data ---
        $ids = array_keys($qtyById);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($in) AND is_active = 1 AND is_available = 1");
        $stmt->execute($ids);
        $products = [];
        foreach ($stmt->fetchAll() as $p) {
            $products[(int)$p['id']] = $p;
        }
        $lines = [];
        $total = 0.0;
        foreach ($qtyById as $id => $qty) {
            if (!isset($products[$id])) {
                throw new RuntimeException('One or more items are unavailable or sold out.');
            }
            $unit = round((float)$products[$id]['price'], 2);
            $line = round($unit * $qty, 2);
            $total = round($total + $line, 2);
            $lines[] = [$id, (string)$products[$id]['name'], $unit, $qty, $line];
        }

        // --- Persist ---
        $attempts = 0;
        while (true) {
            $orderCode = 'BB-' . strtoupper(bin2hex(random_bytes(3)));
            try {
                $pdo->beginTransaction();
                $ins = $pdo->prepare(
                    'INSERT INTO orders (order_code, customer_name, customer_phone, fulfillment_type, delivery_address, notes, total_amount)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([$orderCode, $name, $phone, $type, $address, $notes, number_format($total, 2, '.', '')]);
                $orderId = (int)$pdo->lastInsertId();
                $itm = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, product_name_snapshot, unit_price_snapshot, quantity, line_total)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                foreach ($lines as [$pid, $pname, $unit, $qty, $line]) {
                    $itm->execute([$orderId, $pid, $pname, number_format($unit, 2, '.', ''), $qty, number_format($line, 2, '.', '')]);
                }
                $pdo->commit();
                return $orderCode;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                // Retry on order_code collision only.
                if ((int)$e->getCode() === 23000 && str_contains($e->getMessage(), 'order_code') && ++$attempts < 5) {
                    continue;
                }
                throw $e;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    }

    public const TRANSITIONS = [
        'pending'   => ['accepted', 'rejected'],
        'accepted'  => ['preparing', 'cancelled'],
        'preparing' => ['ready', 'cancelled'],
        'ready'     => ['completed'],
        'completed' => [],
        'rejected'  => [],
        'cancelled' => [],
    ];

    public static function transitionStatus(PDO $pdo, int $orderId, string $newStatus, ?string $reason = null): void
    {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $current = $stmt->fetchColumn();
            if ($current === false) {
                throw new RuntimeException('Order not found.');
            }
            if (!in_array($newStatus, self::TRANSITIONS[$current] ?? [], true)) {
                throw new RuntimeException('Invalid order status transition.');
            }
            if ($newStatus === 'rejected') {
                $reason = trim((string)$reason);
                if ($reason === '' || mb_strlen($reason) > 255) {
                    throw new RuntimeException('A rejection reason is required (max 255 characters).');
                }
                $upd = $pdo->prepare('UPDATE orders SET status = ?, rejection_reason = ? WHERE id = ?');
                $upd->execute([$newStatus, $reason, $orderId]);
            } else {
                $upd = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
                $upd->execute([$newStatus, $orderId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}