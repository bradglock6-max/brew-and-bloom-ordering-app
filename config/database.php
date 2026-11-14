<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        $logDir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        @error_log('[' . date('Y-m-d H:i:s') . '] DB connection failed: ' . $e->getMessage() . PHP_EOL, 3, $logDir . '/app.log');
        http_response_code(500);
        exit('Service temporarily unavailable. Please try again later.');
    }
    return $pdo;
}

return db();