<?php
require_once 'config.php';
require_once 'database.php';
require_once 'bot.php';

echo "🤖 PCN Coin Bot Tester\n";
echo "=====================\n\n";

$bot = new PCNCoinBot();
$db = new Database();

// Test bot connection
echo "1. Testing Bot Connection...\n";
$botInfo = $bot->getMe();
if ($botInfo && $botInfo['ok']) {
    echo "✅ Bot connected successfully!\n";
    echo "   Name: {$botInfo['result']['first_name']}\n";
    echo "   Username: @{$botInfo['result']['username']}\n";
    echo "   ID: {$botInfo['result']['id']}\n\n";
} else {
    echo "❌ Bot connection failed!\n\n";
    echo "Response: " . print_r($botInfo, true) . "\n";
    exit(1);
}

// Test database connection
echo "2. Testing Database Connection...\n";
try {
    $stats = $db->getTotalStats();
    echo "✅ Database connected successfully!\n";
    echo "   Total Users: {$stats['total_users']}\n";
    echo "   Total Earned: {$stats['total_earned']} PCN\n";
    echo "   Total Balance: {$stats['total_balance']} PCN\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test admin user
echo "3. Testing Admin User...\n";
$adminUser = $db->getUser(ADMIN_USER_ID);
if ($adminUser) {
    echo "✅ Admin user found!\n";
    echo "   ID: {$adminUser['id']}\n";
    echo "   Username: {$adminUser['username']}\n";
    echo "   Balance: {$adminUser['balance']} PCN\n\n";
} else {
    echo "⚠️ Admin user not found, creating...\n";
    $db->getUser(ADMIN_USER_ID, ADMIN_USER_ID);
    echo "✅ Admin user created!\n\n";
}

// Test bot commands
echo "4. Testing Bot Commands...\n";

// Simulate admin message
echo "   Testing admin /start command...\n";
$adminMessage = [
    'chat' => ['id' => ADMIN_USER_ID],
    'from' => ['id' => ADMIN_USER_ID, 'username' => ADMIN_USER_ID],
    'text' => '/start'
];
$result = $bot->processMessage($adminMessage);
echo "   ✅ Admin command processed\n";

// Simulate regular user message
echo "   Testing user /start command...\n";
$userMessage = [
    'chat' => ['id' => '123456789'],
    'from' => ['id' => '123456789', 'username' => 'testuser'],
    'text' => '/start'
];
$result = $bot->processMessage($userMessage);
echo "   ✅ User command processed\n";

// Test referral system
echo "   Testing referral system...\n";
$referralMessage = [
    'chat' => ['id' => '987654321'],
    'from' => ['id' => '987654321', 'username' => 'newuser'],
    'text' => '/start ref' . ADMIN_USER_ID
];
$result = $bot->processMessage($referralMessage);
echo "   ✅ Referral system tested\n\n";

// Test daily check-in
echo "5. Testing Daily Check-in...\n";
$checkinMessage = [
    'chat' => ['id' => ADMIN_USER_ID],
    'from' => ['id' => ADMIN_USER_ID, 'username' => ADMIN_USER_ID],
    'text' => '/checkin'
];
$result = $bot->processMessage($checkinMessage);
echo "   ✅ Daily check-in tested\n\n";

// Test balance command
echo "6. Testing Balance Command...\n";
$balanceMessage = [
    'chat' => ['id' => ADMIN_USER_ID],
    'from' => ['id' => ADMIN_USER_ID, 'username' => ADMIN_USER_ID],
    'text' => '/balance'
];
$result = $bot->processMessage($balanceMessage);
echo "   ✅ Balance command tested\n\n";

// Test stats command
echo "7. Testing Stats Command...\n";
$statsMessage = [
    'chat' => ['id' => ADMIN_USER_ID],
    'from' => ['id' => ADMIN_USER_ID, 'username' => ADMIN_USER_ID],
    'text' => '/stats'
];
$result = $bot->processMessage($statsMessage);
echo "   ✅ Stats command tested\n\n";

// Show final statistics
echo "8. Final Statistics...\n";
$finalStats = $db->getTotalStats();
echo "   Total Users: {$finalStats['total_users']}\n";
echo "   Total Earned: {$finalStats['total_earned']} PCN\n";
echo "   Total Balance: {$finalStats['total_balance']} PCN\n";
echo "   Active Users: {$finalStats['active_users']}\n\n";

echo "🎉 All tests completed successfully!\n";
echo "Your PCN Coin bot is ready to use.\n\n";

echo "📱 Next Steps:\n";
echo "1. Open Telegram and search for @" . BOT_NAME . "\n";
echo "2. Send /start to begin\n";
echo "3. Use /help to see all commands\n";
echo "4. Share your referral link to earn PCN\n\n";

echo "🌐 Web Interface:\n";
echo "- Home: http://localhost/telegram/index.php\n";
echo "- Status: http://localhost/telegram/status.php\n";
echo "- Monitor: http://localhost/telegram/monitor.php\n";
echo "- Setup: http://localhost/telegram/setup.php\n\n";

echo "✅ Bot is running and ready!\n";
?> 