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

// Fetch payment details to get user_id
$payment = $db->getPaymentById($paymentId);

if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Invalid Payment ID.']);
    exit;
}

$userId = $payment['user_id'];

// Start transaction
$db->getConnection()->beginTransaction();

try {
    // 1. Update payment status to 'approved'
    $db->updatePaymentStatus($paymentId, 'approved');

    // 2. Update user's subscription to 'paid'
    $db->updateUserSubscription($userId, 'paid');

    // 3. Add 500 coins to the user's balance
    $db->updateBalance($userId, 500, 'subscription_bonus', 'Premium subscription bonus');

    // Commit the transaction
    $db->getConnection()->commit();

    echo json_encode(['success' => true, 'message' => 'Payment approved and 500 coins added.']);

} catch (Exception $e) {
    // Rollback the transaction if something failed
    $db->getConnection()->rollback();
    error_log('Payment approval failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
