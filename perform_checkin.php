<?php
require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An error occurred.'];

if (!isset($_GET['user_id'])) {
    $response['message'] = 'User not identified.';
    echo json_encode($response);
    exit;
}

$userId = $_GET['user_id'];
$db = new Database();

try {
    if ($db->canCheckIn($userId)) {
        if ($db->performDailyCheckIn($userId)) {
            $user = $db->getUser($userId);
            $response['success'] = true;
            $response['message'] = 'You have successfully claimed 5 PCN!';
            $response['new_balance'] = $user['balance'];
        } else {
            $response['message'] = 'Failed to perform check-in. Please try again.';
        }
    } else {
        $response['message'] = 'You have already claimed your daily bonus today.';
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
}

echo json_encode($response);
?>
