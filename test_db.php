<?php
// Test script to check database connectivity on the server
require_once 'config.php';
require_once 'database.php';

header('Content-Type: text/plain');

try {
    $db = new Database();
    echo "✅ Database connection successful!\n";
    
    // Check users table
    try {
        $stmt = $db->getUser(ADMIN_USER_ID);
        echo "✅ Users table access successful (Admin ID: " . ADMIN_USER_ID . ")\n";
    } catch (Exception $e) {
        echo "❌ Users table access failed: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Check your env.php settings (DB_NAME, DB_USER, DB_PASS, DB_HOST)\n";
}
?>
