<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/Models/OrderModel.php';
Auth::requireAdmin();
session_write_close();

$n = (int)(new OrderModel(db()))->getSalesSummary()['pending_count'];
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode(['pending' => $n]);