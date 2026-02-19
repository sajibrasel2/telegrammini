<?php
require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json');

$db = new Database();

// Get the input data
$data = json_decode(file_get_contents('php://input'), true);
$paymentId = $data['payment_id'] ?? null;

if (!$paymentId) {
    echo json_encode(['success' => false, 'message' => 'Payment ID is missing.']);
    exit;
}

if ($db->updatePaymentStatus($paymentId, 'rejected')) {
    echo json_encode(['success' => true, 'message' => 'Payment has been rejected.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reject payment. Please try again.']);
}
