<?php
require_once 'config.php';
require_once 'database.php';
require_once 'bot.php';
require_once 'admin_auth.php';

header('Content-Type: application/json');

$db = new Database();
admin_require_login();

$action = $_POST['action'] ?? '';

if ($action === 'broadcast') {
    $message = $_POST['message'] ?? '';
    $target = $_POST['target'] ?? 'all'; // 'all', 'paid', 'free'
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
        exit;
    }

    $bot = new PCNCoinBot();
    $users = [];

    if ($target === 'all') {
        $users = $db->getAllUsers();
    } elseif ($target === 'paid') {
        $stmt = $db->getConnection()->prepare("SELECT id FROM users WHERE subscription_type = 'paid'");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($target === 'free') {
        $stmt = $db->getConnection()->prepare("SELECT id FROM users WHERE subscription_type = 'free'");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $successCount = 0;
    $failCount = 0;

    foreach ($users as $user) {
        $result = $bot->sendMessage($user['id'], $message);
        if ($result && isset($result['ok']) && $result['ok']) {
            $successCount++;
        } else {
            $failCount++;
        }
        // Small delay to avoid hitting Telegram rate limits for large broadcasts
        if ($successCount % 20 === 0) {
            usleep(500000); // 0.5 seconds
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => "Broadcast complete. Sent: $successCount, Failed: $failCount",
        'details' => ['sent' => $successCount, 'failed' => $failCount]
    ]);
    exit;
}

if ($action === 'send_private') {
    $userId = $_POST['user_id'] ?? '';
    $message = $_POST['message'] ?? '';

    if (empty($userId) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'User ID and message are required.']);
        exit;
    }

    $bot = new PCNCoinBot();
    $result = $bot->sendMessage($userId, $message);

    if ($result && isset($result['ok']) && $result['ok']) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
    } else {
        $error = $result['description'] ?? 'Unknown error';
        echo json_encode(['success' => false, 'message' => "Failed to send message: $error"]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
