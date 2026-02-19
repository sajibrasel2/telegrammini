<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'database.php';

$db = new Database();

$user_id = $_POST['user_id'] ?? null;
$transaction_id = $_POST['transaction_id'] ?? null;

if (empty($user_id) || empty($transaction_id)) {
    echo json_encode(['status' => 'error', 'message' => 'User ID and Transaction ID are required.']);
    exit;
}

// Sanitize input
$user_id = htmlspecialchars($user_id);
$transaction_id = htmlspecialchars($transaction_id);

// Check if transaction ID has already been submitted to prevent duplicates
if ($db->isTransactionIdSubmitted($transaction_id)) {
    echo json_encode(['status' => 'error', 'message' => 'This Transaction ID has already been submitted. Please wait for verification.']);
    exit;
}

$amount = 0.50; // The required amount for the upgrade
$currency = 'TON';

try {
    if ($db->submitPaymentForVerification($user_id, $transaction_id, $amount, $currency)) {
        echo json_encode(['status' => 'success', 'message' => 'Your submission has been received! Your account will be upgraded after verification (usually within a few hours).']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit your payment. Please try again.']);
    }
} catch (Exception $e) {
    error_log($e->getMessage()); // Log the actual error for debugging
    echo json_encode(['status' => 'error', 'message' => 'An unexpected error occurred. Please contact support.']);
}
