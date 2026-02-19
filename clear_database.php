<?php
require_once 'config.php';
require_once 'database.php';

echo "<style>body { font-family: monospace; background-color: #1a1a1a; color: #00ff00; padding: 20px; }
.success { color: #00ff00; }
.error { color: #ff4444; }
.info { color: #ffff00; }</style>";

echo "<h1>Clearing Database...</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();

    $tables = [
        'payments',
        'daily_checkins',
        'transactions',
        'withdrawals',
        'referrals',
        'users'
    ];

    echo "<p class='info'>Disabling foreign key checks...</p>";
    $conn->exec('SET FOREIGN_KEY_CHECKS = 0;');

    foreach ($tables as $table) {
        try {
            $conn->exec("TRUNCATE TABLE `$table`");
            echo "<p class='success'>Successfully truncated table: `$table`</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>Error truncating table `$table`: " . $e->getMessage() . "</p>";
        }
    }

    echo "<p class='info'>Enabling foreign key checks...</p>";
    $conn->exec('SET FOREIGN_KEY_CHECKS = 1;');

    echo "<hr><h2><span class='success'>Database cleared successfully!</span></h2>";

} catch (Exception $e) {
    echo "<p class='error'>An error occurred: " . $e->getMessage() . "</p>";
}
