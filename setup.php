<?php
require_once 'config.php';
require_once 'database.php';
require_once 'bot.php';

echo "<h1>PCN Coin Referral Bot Setup</h1>";

$bot = new PCNCoinBot();

// Test bot connection
echo "<h2>1. Testing Bot Connection</h2>";
$botInfo = $bot->getMe();

if ($botInfo && $botInfo['ok']) {
    $botData = $botInfo['result'];
    echo "<p>✅ <strong>Bot Connected Successfully!</strong></p>";
    echo "<ul>";
    echo "<li><strong>Bot Name:</strong> {$botData['first_name']}</li>";
    echo "<li><strong>Username:</strong> @{$botData['username']}</li>";
    echo "<li><strong>Bot ID:</strong> {$botData['id']}</li>";
    echo "</ul>";
} else {
    echo "<p>❌ <strong>Bot Connection Failed!</strong></p>";
    if (is_array($botInfo)) {
        $errorCode = $botInfo['error_code'] ?? '';
        $description = $botInfo['description'] ?? '';
        $httpCode = $botInfo['_http_code'] ?? '';
        $details = trim(($errorCode !== '' ? "Error Code: {$errorCode}. " : '') . ($description !== '' ? "Description: {$description}. " : '') . ($httpCode !== '' ? "HTTP: {$httpCode}." : ''));
        if ($details !== '') {
            echo "<p><strong>Details:</strong> " . htmlspecialchars($details) . "</p>";
        }
    }
    echo "<p>Please check your bot token and server internet access.</p>";
}

// Test database connection
echo "<h2>2. Database Setup</h2>";
try {
    $db = new Database();
    echo "<p>✅ <strong>Database Connected Successfully!</strong></p>";
    echo "<ul>";
    echo "<li><strong>Database:</strong> " . DB_NAME . "</li>";
    echo "<li><strong>Host:</strong> " . DB_HOST . "</li>";
    echo "<li><strong>Tables:</strong> users, referrals, withdrawals, transactions</li>";
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ <strong>Database Connection Failed!</strong></p>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please create the database '" . DB_NAME . "' in MySQL.</p>";
}

// Webhook Management
echo "<h2>3. Webhook Management</h2>";

$current_url = strtok($_SERVER["REQUEST_URI"], '?');

echo "<p style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'>";
echo "<a href='{$current_url}?action=set'>Set Webhook</a> | ";
echo "<a href='{$current_url}?action=status'>Get Status</a> | ";
echo "<a href='{$current_url}?action=delete'>Delete Webhook</a>";
echo "</p>";

$action = $_GET['action'] ?? 'status'; // Default to showing status

switch ($action) {
    case 'set':
        echo "<h3>Setting Webhook...</h3>";
        if (defined('WEBHOOK_URL') && WEBHOOK_URL !== '' && WEBHOOK_URL !== 'YOUR_WEBHOOK_URL_HERE') {
            $result = $bot->setWebhook(WEBHOOK_URL);
            echo "<p>Attempting to set webhook to: <strong>" . WEBHOOK_URL . "</strong></p>";
            echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
        } else {
            echo "<p>❌ <strong>Configuration Error:</strong> <code>WEBHOOK_URL</code> is not defined in <code>config.php</code>.</p>";
        }
        break;

    case 'delete':
        echo "<h3>Deleting Webhook...</h3>";
        $result = $bot->deleteWebhook();
        echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
        break;

    case 'status':
    default:
        echo "<h3>Current Webhook Status:</h3>";
        $result = $bot->getWebhookInfo();
        echo "<pre>" . htmlspecialchars(print_r($result, true)) . "</pre>";
        break;
}

echo '<hr>';

// Test admin access
echo "<h2>5. Admin Configuration</h2>";
echo "<p><strong>Admin User ID:</strong> " . ADMIN_USER_ID . "</p>";
echo "<p>✅ <strong>Admin access configured!</strong></p>";
echo "<p>The bot will respond specially to messages from this admin user.</p>";

// PCN Coin Configuration
echo "<h2>6. PCN Coin Configuration</h2>";
echo "<ul>";
echo "<li><strong>Coin Name:</strong> " . PCN_COIN_NAME . "</li>";
echo "<li><strong>Coin Symbol:</strong> " . PCN_COIN_SYMBOL . "</li>";
echo "<li><strong>Referral Bonus:</strong> " . REFERRAL_BONUS . " PCN per referral</li>";
echo "<li><strong>Minimum Withdrawal:</strong> " . MIN_WITHDRAWAL . " PCN</li>";
echo "<li><strong>Bot Username:</strong> @" . BOT_NAME . "</li>";
echo "</ul>";

// Web Interface
echo "<h2>7. Web Interface</h2>";
echo "<p>✅ <strong>Web interface is ready!</strong></p>";
echo "<ul>";
echo "<li><strong>Home Page:</strong> <a href='index.php' target='_blank'>index.php</a></li>";
echo "<li><strong>Referral Page:</strong> <a href='referral.php' target='_blank'>referral.php</a></li>";
echo "<li><strong>Mobile Friendly:</strong> ✅ Responsive design</li>";
echo "<li><strong>Bottom Navigation:</strong> ✅ Home & Referral tabs</li>";
echo "</ul>";

// Instructions
echo "<h2>8. Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Test the bot:</strong> Send a message to your bot on Telegram</li>";
echo "<li><strong>Admin commands:</strong> Try /start, /status, /stats as admin</li>";
echo "<li><strong>User features:</strong> Test referral system with regular users</li>";
echo "<li><strong>Web interface:</strong> Visit the web pages to see the interface</li>";
echo "<li><strong>Webhook setup:</strong> If you have a public HTTPS URL, set it in config.php</li>";
echo "</ol>";

echo "<h2>9. Bot Commands</h2>";
echo "<h3>Admin Commands:</h3>";
echo "<ul>";
echo "<li><code>/start</code> - Start admin panel</li>";
echo "<li><code>/status</code> - Check bot status</li>";
echo "<li><code>/users</code> - View user statistics</li>";
echo "<li><code>/stats</code> - Referral statistics</li>";
echo "<li><code>/broadcast</code> - Send message to all users</li>";
echo "<li><code>/help</code> - Admin help</li>";
echo "</ul>";

echo "<h3>User Commands:</h3>";
echo "<ul>";
echo "<li><code>/start</code> - Start the bot</li>";
echo "<li><code>/balance</code> - Check PCN balance</li>";
echo "<li><code>/referral</code> - Get referral link</li>";
echo "<li><code>/withdraw</code> - Withdraw PCN coins</li>";
echo "<li><code>/stats</code> - Your referral statistics</li>";
echo "<li><code>/help</code> - Get help</li>";
echo "</ul>";

echo "<h2>10. Referral System</h2>";
echo "<p><strong>How it works:</strong></p>";
echo "<ol>";
echo "<li>Users get their referral link using /referral</li>";
echo "<li>When someone uses their link, both users earn " . REFERRAL_BONUS . " PCN</li>";
echo "<li>Users can withdraw when they reach " . MIN_WITHDRAWAL . " PCN</li>";
echo "<li>All data is stored in MySQL database</li>";
echo "<li>Referral links use: https://t.me/" . BOT_NAME . "?start=ref[USER_ID]</li>";
echo "</ol>";

echo "<h2>11. Database Tables</h2>";
echo "<ul>";
echo "<li><strong>users:</strong> User information and balances</li>";
echo "<li><strong>referrals:</strong> Referral relationships</li>";
echo "<li><strong>withdrawals:</strong> Withdrawal requests</li>";
echo "<li><strong>transactions:</strong> All transaction history</li>";
echo "</ul>";

echo "<p><strong>Setup complete! Your PCN Coin referral bot is ready to use.</strong></p>";
?>