<?php
require __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
$data = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
if (!$data || !duitku_valid_callback($data)) { http_response_code(403); exit('INVALID SIGNATURE'); }
$orderId = trim((string)($data['merchantOrderId'] ?? ''));
$resultCode = (string)($data['resultCode'] ?? '');
$reference = (string)($data['reference'] ?? $orderId);
$status = $resultCode === '00' ? 'selesai' : 'dibatalkan';
$update = $pdo->prepare('UPDATE transaksi SET status_pesanan=?, duitku_reference=?, duitku_status=?, duitku_paid_at=CASE WHEN ?="selesai" THEN NOW() ELSE duitku_paid_at END WHERE duitku_order_id=?');
$update->execute([$status, $reference, $resultCode, $status, $orderId]);
echo 'SUCCESS';
