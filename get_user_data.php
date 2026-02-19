<?php

header("Access-Control-Allow-Origin: *"); // Allow requests from any origin
header('Content-Type: application/json');
require_once 'config.php';
require_once 'database.php';

$db = new Database();

$userId = null;
if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
}

if (!$userId) {
    echo json_encode(['error' => 'User ID not provided']);
    exit;
}

$user = $db->getUser($userId);

if ($user) {
    $referral_link = "https://t.me/" . BOT_NAME . "?start=ref" . $user['id'];
    $tree = $db->getReferralTree($user['id']);
    $stats = $db->getReferralStats($user['id']);

    $response = [
        'success' => true,
        'user_id' => $user['id'],
        'username' => $user['username'],
        'referral_link' => $referral_link,
        'is_paid' => ($user['subscription_type'] === 'paid'),
        'balance' => $user['balance'],
        'referral_tree' => $tree,
        'referral_stats' => $stats
    ];
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'User not found']);
}
?>
