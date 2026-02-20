<?php
require_once 'config.php';
require_once 'bot.php';

echo "<h1>PCN Coin Referral Bot Test</h1>";

$bot = new PCNCoinBot();

// Test 1: Bot Connection
echo "<h2>Test 1: Bot Connection</h2>";
$botInfo = $bot->getMe();
if ($botInfo && $botInfo['ok']) {
    echo "✅ Bot connection successful<br>";
    echo "Bot name: " . $botInfo['result']['first_name'] . "<br>";
    echo "Bot username: @" . $botInfo['result']['username'] . "<br>";
} else {
    echo "❌ Bot connection failed<br>";
}

// Test 2: Configuration Check
echo "<h2>Test 2: Configuration Check</h2>";
echo "Bot Token: hidden<br>";
echo "Admin User ID: hidden<br>";
echo "PCN Coin Name: " . PCN_COIN_NAME . "<br>";
echo "Referral Bonus: " . REFERRAL_BONUS . " PCN<br>";
echo "Minimum Withdrawal: " . MIN_WITHDRAWAL . " PCN<br>";
echo "Debug Mode: " . (DEBUG_MODE ? "Enabled" : "Disabled") . "<br>";

// Test 3: File Permissions
echo "<h2>Test 3: File Permissions</h2>";
$logFile = LOG_FILE;
if (is_writable(dirname($logFile))) {
    echo "✅ Log directory is writable<br>";
} else {
    echo "❌ Log directory is not writable<br>";
}

// Test 4: Users File
echo "<h2>Test 4: Users File</h2>";
$userFile = 'users.json';
if (file_exists($userFile)) {
    $users = json_decode(file_get_contents($userFile), true);
    echo "✅ Users file exists<br>";
    echo "Total users: " . count($users) . "<br>";
} else {
    echo "⚠️ Users file will be created when first user joins<br>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Send /start to your bot on Telegram</li>";
echo "<li>Check if you get a response</li>";
echo "<li>If you're the admin user, try /status and /stats commands</li>";
echo "<li>Test referral system with regular users</li>";
echo "<li>Check pcn_bot_log.txt for activity logs</li>";
echo "</ol>";

echo "<h2>Referral System Test:</h2>";
echo "<ol>";
echo "<li>Send /referral to get your referral link</li>";
echo "<li>Share the link with someone</li>";
echo "<li>When they use the link, both should earn " . REFERRAL_BONUS . " PCN</li>";
echo "<li>Check /balance to see your earnings</li>";
echo "<li>Use /withdraw when you reach " . MIN_WITHDRAWAL . " PCN</li>";
echo "</ol>";

echo "<p><strong>Test completed! Your PCN Coin referral bot is ready.</strong></p>";
?> 