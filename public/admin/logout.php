<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}
verifyCsrf($_POST['csrf_token'] ?? null);
logoutAdmin();
header('Location: login.php');
exit;