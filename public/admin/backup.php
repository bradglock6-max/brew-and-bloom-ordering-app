<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}
verifyCsrf($_POST['csrf_token'] ?? null);
session_write_close();

$pdo = db();
// Creation order respects FK dependencies; users table intentionally excluded.
$tables = ['categories', 'products', 'orders', 'order_items', 'settings'];

try {
    $out = "-- Brew & Bloom backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- Excludes: users\n\n";
    $out .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach (array_reverse($tables) as $t) {
        $out .= "DROP TABLE IF EXISTS `$t`;\n";
    }
    $out .= "\n";

    foreach ($tables as $t) {
        $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM);
        $out .= $create[1] . ";\n\n";

        $stmt = $pdo->prepare("SELECT * FROM `$t`");
        $stmt->execute();
        $cols = null;
        $batch = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($cols === null) {
                $cols = '`' . implode('`,`', array_keys($row)) . '`';
            }
            $vals = [];
            foreach ($row as $v) {
                $vals[] = $v === null ? 'NULL' : $pdo->quote((string)$v);
            }
            $batch[] = '(' . implode(',', $vals) . ')';
            if (count($batch) >= 100) {
                $out .= "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n";
                $batch = [];
            }
        }
        if ($batch) {
            $out .= "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n";
        }
        $out .= "\n";
    }
    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
} catch (Throwable $e) {
    @error_log('[' . date('Y-m-d H:i:s') . '] Backup failed: ' . $e->getMessage() . PHP_EOL, 3, __DIR__ . '/../../storage/logs/app.log');
    http_response_code(500);
    exit('Backup failed.');
}

$filename = 'brew_and_bloom_backup_' . date('Y-m-d_H-i') . '.sql';
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($out));
header('Cache-Control: no-store');
echo $out;
exit;