<?php
declare(strict_types=1);

final class ProductModel
{
    public function __construct(private PDO $pdo) {}

    public function getAllWithCategory(bool $onlyActive = true): array
    {
        $sql = 'SELECT p.*, c.name AS category_name FROM products p
                JOIN categories c ON c.id = p.category_id'
            . ($onlyActive ? ' WHERE p.is_active = 1' : '')
            . ' ORDER BY c.sort_order ASC, p.name ASC';
        return $this->pdo->query($sql)->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (category_id, name, description, price, image_path, is_available)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (int)$data['category_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['price'],
            $data['image_path'] ?? null,
            (int)($data['is_available'] ?? 1),
        ]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products SET category_id = ?, name = ?, description = ?, price = ?,
             image_path = COALESCE(?, image_path), is_available = ? WHERE id = ?'
        );
        $stmt->execute([
            (int)$data['category_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['price'],
            $data['image_path'] ?? null,
            (int)($data['is_available'] ?? 1),
            $id,
        ]);
    }

    public function toggleAvailability(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE products SET is_available = 1 - is_available WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE products SET is_active = 0 WHERE id = ?');
        $stmt->execute([$id]);
    }
}