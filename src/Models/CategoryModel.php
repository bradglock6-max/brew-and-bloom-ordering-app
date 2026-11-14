<?php
declare(strict_types=1);

final class CategoryModel
{
    public function __construct(private PDO $pdo) {}

    public function getAll(bool $onlyActive = false): array
    {
        $sql = 'SELECT id, name, sort_order, is_active, created_at FROM categories'
            . ($onlyActive ? ' WHERE is_active = 1' : '')
            . ' ORDER BY sort_order ASC, name ASC';
        return $this->pdo->query($sql)->fetchAll();
    }

    public function create(string $name, int $sortOrder = 0): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO categories (name, sort_order) VALUES (?, ?)');
        $stmt->execute([$name, $sortOrder]);
    }

    public function toggleActive(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE categories SET is_active = 1 - is_active WHERE id = ?');
        $stmt->execute([$id]);
    }
}