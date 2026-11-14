<?php
declare(strict_types=1);

if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only'); }

require_once __DIR__ . '/../config/database.php';

$hash = password_hash('Admin@Brew2026!', PASSWORD_DEFAULT);
$stmt = db()->prepare(
    "INSERT INTO users (username, password_hash, role, is_active) VALUES ('admin', ?, 'admin', 1)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = 'admin', is_active = 1"
);
$stmt->execute([$hash]);
echo "Admin user 'admin' created/updated.\n";