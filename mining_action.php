<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'database.php';

$db = new Database();

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$userId = $data['user_id'] ?? null;
$action = $data['action'] ?? null;

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID is required.']);
    exit;
}

try {
    if ($action === 'start') {
        $result = $db->startMining($userId);
        echo json_encode($result);
    } elseif ($action === 'claim') {
        $result = $db->claimMiningReward($userId);
        echo json_encode($result);
    } else {
        $session = $db->getActiveMiningSession($userId);
        echo json_encode(['success' => true, 'session' => $session]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
