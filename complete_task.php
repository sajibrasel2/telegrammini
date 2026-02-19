<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/telegram_gate.php';
require_once 'config.php';
require_once 'database.php';

// Function to log errors
function log_error($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] - {$message}\n";
    file_put_contents(__DIR__ . '/error_log.txt', $logMessage, FILE_APPEND);
}

try {
    $db = new Database();

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Log the received data for debugging
    log_error('Received task completion request: ' . $input);

    if (!isset($data['user_id']) || !isset($data['task_id'])) {
        $error = 'Invalid request: missing user_id or task_id. Data: ' . $input;
        log_error($error);
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }

    $userId = $data['user_id'];
    $taskId = $data['task_id'];

    // Special handling for daily check-in
    if ($taskId === 'daily_checkin') {
        if ($db->performDailyCheckIn($userId)) {
            $user = $db->getUser($userId);
            echo json_encode([
                'success' => true,
                'message' => 'You have successfully claimed 5 PCN!',
                'new_balance' => $user['balance']
            ]);
        } else {
            // If user cannot check in, it means already claimed today (idempotent success)
            if (!$db->canCheckIn($userId)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'You have already claimed your daily bonus today.'
                ]);
            } else {
                // Otherwise this is an actual failure (DB error etc.)
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to claim daily bonus. Please try again.'
                ]);
            }
        }
        exit;
    }

    $taskIdInt = (int)$taskId;
    if ($taskIdInt <= 0) {
        $error = 'Invalid task ID: ' . $taskId;
        log_error($error);
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }

    $task = $db->getTaskById($taskIdInt);
    if (!$task) {
        $error = 'Invalid task ID: ' . $taskIdInt;
        log_error($error);
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }

    if (isset($task['status']) && $task['status'] !== 'active') {
        $error = 'Task is inactive: ' . $taskIdInt;
        log_error($error);
        echo json_encode(['success' => false, 'message' => 'Task is currently unavailable.']);
        exit;
    }
    
    // Complete the task
    $result = $db->completeTask($userId, $taskIdInt, (float)$task['reward']);
    
    if ($result['success']) {
        $user = $db->getUser($userId);
        $result['new_balance'] = $user['balance'];
    }
    
    // Log the result before sending
    log_error('Task completion result: ' . json_encode($result));
    
    // Send the response and exit
    echo json_encode($result);
    exit;
} catch (Throwable $e) {
    log_error('Fatal Error in complete_task.php: ' . $e->getMessage() . " on line " . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred.']);
}
