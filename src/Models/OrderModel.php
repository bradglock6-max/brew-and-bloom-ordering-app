<?php
declare(strict_types=1);

final class OrderModel
{
    public function __construct(private PDO $pdo) {}

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/[\s\-]+/', '', trim($phone)) ?? '';
    }

    public function findByCodeAndPhone(string $orderCode, string $phone): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE order_code = ? AND customer_phone = ? LIMIT 1');
        $stmt->execute([strtoupper(trim($orderCode)), self::normalizePhone($phone)]);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }
        $items = $this->pdo->prepare(
            'SELECT product_name_snapshot, unit_price_snapshot, quantity, line_total
             FROM order_items WHERE order_id = ? ORDER BY id ASC'
        );
        $items->execute([(int)$order['id']]);
        $order['items'] = $items->fetchAll();
        return $order;
    }

    public function getSetting(string $key, string $default = ''): string
    {
        $stmt = $this->pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $v = $stmt->fetchColumn();
        return $v === false ? $default : (string)$v;
    }

    public const STATUSES = ['pending', 'accepted', 'preparing', 'ready', 'completed', 'rejected', 'cancelled'];

    public function getOrdersByStatus(?string $status = null): array
    {
        if ($status !== null && in_array($status, self::STATUSES, true)) {
            $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC, id DESC');
            $stmt->execute([$status]);
        } else {
            $stmt = $this->pdo->prepare('SELECT * FROM orders ORDER BY created_at DESC, id DESC');
            $stmt->execute();
        }
        $orders = $stmt->fetchAll();
        if (!$orders) {
            return [];
        }
        $ids = array_map(fn($o) => (int)$o['id'], $orders);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $it = $this->pdo->prepare(
            "SELECT order_id, product_name_snapshot, unit_price_snapshot, quantity, line_total
             FROM order_items WHERE order_id IN ($in) ORDER BY id ASC"
        );
        $it->execute($ids);
        $by = [];
        foreach ($it->fetchAll() as $row) {
            $by[(int)$row['order_id']][] = $row;
        }
        foreach ($orders as &$o) {
            $o['items'] = $by[(int)$o['id']] ?? [];
        }
        unset($o);
        return $orders;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getSalesSummary(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
               COALESCE(SUM(status = 'pending'), 0) AS pending_count,
               COALESCE(SUM(status IN ('accepted','preparing','ready')), 0) AS active_count,
               COALESCE(SUM(status = 'completed'), 0) AS completed_count,
               COALESCE(SUM(CASE WHEN status = 'completed' AND DATE(created_at) = CURDATE() THEN total_amount END), 0) AS today_revenue,
               COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount END), 0) AS total_revenue
             FROM orders"
        );
        $stmt->execute();
        $r = $stmt->fetch();
        return [
            'pending_count'   => (int)$r['pending_count'],
            'active_count'    => (int)$r['active_count'],
            'completed_count' => (int)$r['completed_count'],
            'today_revenue'   => (float)$r['today_revenue'],
            'total_revenue'   => (float)$r['total_revenue'],
        ];
    }

    public function updateSetting(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }
}