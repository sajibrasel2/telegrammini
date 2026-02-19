<?php
require_once 'config.php';
require_once 'database.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    
    // Get user ID from request (you can modify this based on your authentication system)
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1; // Default to user 1 for demo
    $depth = isset($_GET['depth']) ? (int)$_GET['depth'] : 3;
    
    // Get referral tree data
    $treeData = $db->getReferralTree($userId, $depth);
    
    // Get tree statistics
    $treeStats = $db->getReferralTreeStats($userId);
    
    $response = [
        'success' => true,
        'tree' => $treeData,
        'stats' => $treeStats
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 