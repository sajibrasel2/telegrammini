<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'database.php';

$db = new Database();

// Get the input from the POST request
$payment_id = $_POST['payment_id'] ?? null;
$action = $_POST['action'] ?? null;

// A simple way to get the admin user ID for checking permissions.
// In a real app, you should use a secure session-based authentication.
$admin_user_id = $_GET['user_id'] ?? null; // Assuming admin's user_id is passed for verification

if (!$payment_id || !$action || !$admin_user_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

// Verify if the user is an admin
$admin_user = $db->getUser($admin_user_id);
if (!$admin_user || !$admin_user['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Get the payment details
$payment = $db->getPaymentById($payment_id);
if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Payment not found.']);
    exit;
}

$target_user_id = $payment['user_id'];

if ($action === 'approve') {
    // Update user subscription and payment status
    $user_updated = $db->updateUserSubscription($target_user_id, 'paid');
    $payment_updated = $db->updatePaymentStatus($payment_id, 'approved');

    if ($user_updated && $payment_updated) {
        // Optionally, send a notification to the user via Telegram bot here
        echo json_encode(['success' => true, 'message' => 'Payment approved and user upgraded to premium.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user or payment status.']);
    }
} elseif ($action === 'reject') {
    // Update payment status to rejected
    $payment_updated = $db->updatePaymentStatus($payment_id, 'rejected');

    if ($payment_updated) {
        echo json_encode(['success' => true, 'message' => 'Payment has been rejected.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject the payment.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}
