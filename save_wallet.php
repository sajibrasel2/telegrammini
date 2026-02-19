<?php
require_once 'telegram_gate.php';
require_once 'config.php';
require_once 'database.php';
require_once 'security_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? null;
$walletAddress = Security::sanitizeInput($data['wallet_address'] ?? '');

if (!$userId || !$walletAddress) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

$db = new Database();
if ($db->saveWalletAddress($userId, $walletAddress)) {
    echo json_encode(['success' => true, 'message' => 'Wallet address saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save wallet address']);
}
