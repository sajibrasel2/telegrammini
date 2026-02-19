<?php
require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'No data received']);
    exit;
}

try {
    $db = new Database();
    
    // Extract user data
    $userId = $input['user_id'] ?? null;
    $username = $input['username'] ?? '';
    $firstName = $input['first_name'] ?? '';
    $lastName = $input['last_name'] ?? '';
    $isPremium = $input['is_premium'] ?? false;
    $languageCode = $input['language_code'] ?? 'en';
    
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'User ID is required']);
        exit;
    }
    
    // Get or create user
    $user = $db->getUser($userId, $username);
    
    if (!$user) {
        // Create new user
        $user = $db->getUser($userId, $username);
        if (!$user) {
            // If user still doesn't exist, create them
            $db->getConnection()->prepare("
                INSERT INTO users (id, username, balance, total_earned, joined_date) 
                VALUES (?, ?, 0, 0, NOW())
            ")->execute([$userId, $username]);
            
            $user = $db->getUser($userId, $username);
        }
    }
    
    // Start session and store user info
    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    $_SESSION['is_premium'] = $isPremium;
    $_SESSION['language_code'] = $languageCode;
    $_SESSION['authenticated'] = true;
    
    // Log the authentication
    $logMessage = "WebApp Authentication: User ID: $userId, Username: $username, Name: $firstName $lastName";
    file_put_contents('pcn_bot_log.txt', date('Y-m-d H:i:s') . " - $logMessage\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $userId,
            'username' => $username,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'balance' => $user['balance'] ?? 0,
            'total_earned' => $user['total_earned'] ?? 0,
            'subscription_type' => $user['subscription_type'] ?? 'free'
        ],
        'message' => 'User authenticated successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => 'Authentication failed: ' . $e->getMessage()
    ]);
}
?> 