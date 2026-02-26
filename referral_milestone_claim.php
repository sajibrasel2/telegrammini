<?php
header('Content-Type: application/json');
require_once __DIR__ . '/telegram_gate.php';
require_once __DIR__ . '/database.php';

$db = new Database();

$input = file_get_contents('php://input');
$payload = [];
if ($input) {
    $decoded = json_decode($input, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

$userId = null;
$milestone = null;

if (isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
} elseif (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
} elseif (isset($payload['user_id'])) {
    $userId = $payload['user_id'];
}

if (isset($_POST['milestone'])) {
    $milestone = $_POST['milestone'];
} elseif (isset($_GET['milestone'])) {
    $milestone = $_GET['milestone'];
} elseif (isset($payload['milestone'])) {
    $milestone = $payload['milestone'];
}

if (!$userId || !$milestone) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

$result = $db->claimReferralMilestone($userId, $milestone);
echo json_encode($result);
