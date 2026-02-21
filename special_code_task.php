<?php
header('Content-Type: application/json');
require_once __DIR__ . '/telegram_gate.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

$db = new Database();

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$userId = $data['user_id'] ?? null;
$code = $data['code'] ?? '';
$token = $data['_token'] ?? '';

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

$expectedToken = md5('pcn_secure_' . date('Y-m-d'));
if ($token !== $expectedToken) {
    echo json_encode(['success' => false, 'message' => 'Invalid request token.']);
    exit;
}

try {
    $result = $db->claimDailySpecialCode($userId, (string)$code, 5);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error.']);
}
