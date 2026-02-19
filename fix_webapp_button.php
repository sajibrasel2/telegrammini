<?php
require_once 'config.php';
require_once 'bot.php';

echo "<h1>🔧 Fixing WebApp Button Issue</h1>";

$bot = new PCNCoinBot();

// Step 1: Check bot info
echo "<h2>1. Checking Bot Information</h2>";
$botInfo = $bot->getMe();
if ($botInfo && $botInfo['ok']) {
    $botData = $botInfo['result'];
    echo "✅ Bot Name: {$botData['first_name']}<br>";
    echo "✅ Username: @{$botData['username']}<br>";
    echo "✅ Bot ID: {$botData['id']}<br><br>";
} else {
    echo "❌ Bot connection failed<br><br>";
    exit;
}

// Step 2: Delete webhook to use polling
echo "<h2>2. Setting up Polling Mode</h2>";
$webhookResult = $bot->deleteWebhook();
if ($webhookResult && $webhookResult['ok']) {
    echo "✅ Webhook deleted successfully<br>";
    echo "✅ Bot will use polling mode<br><br>";
} else {
    echo "⚠️ Could not delete webhook<br><br>";
}

// Step 3: Test WebApp button with different approach
echo "<h2>3. Testing WebApp Button</h2>";

// Test with inline keyboard instead of regular keyboard
$testChatId = '5785952613'; // Your chat ID from logs
$testMessage = "🧪 Testing WebApp Button\n\nClick the button below to open the Mini App!";

$inlineKeyboard = [
    'inline_keyboard' => [
        [
            [
                'text' => '🌐 Open Web App',
                'web_app' => ['url' => 'http://localhost/telegram/index.php']
            ]
        ]
    ]
];

$testResult = $bot->sendMessage($testChatId, $testMessage, 'HTML', $inlineKeyboard);

if ($testResult && $testResult['ok']) {
    echo "✅ Inline WebApp button sent successfully!<br>";
    echo "Message ID: {$testResult['result']['message_id']}<br><br>";
} else {
    echo "❌ Inline WebApp button failed<br>";
    if ($testResult) {
        echo "Error: " . $testResult['description'] . "<br>";
    }
    echo "<br>";
}

// Step 4: Create alternative keyboard layout
echo "<h2>4. Creating Alternative Keyboard</h2>";

$alternativeKeyboard = [
    'keyboard' => [
        [
            [
                'text' => '🌐 Open Web App',
                'web_app' => ['url' => 'http://localhost/telegram/index.php']
            ]
        ],
        [
            [
                'text' => '💰 Balance'
            ],
            [
                'text' => '🔗 Referral'
            ]
        ],
        [
            [
                'text' => '📊 Stats'
            ],
            [
                'text' => '💳 Payment'
            ]
        ]
    ],
    'resize_keyboard' => true,
    'one_time_keyboard' => false,
    'selective' => false
];

$altTestResult = $bot->sendMessage($testChatId, "🔧 Alternative Keyboard Test\n\nTry this keyboard layout:", 'HTML', $alternativeKeyboard);

if ($altTestResult && $altTestResult['ok']) {
    echo "✅ Alternative keyboard sent successfully!<br><br>";
} else {
    echo "❌ Alternative keyboard failed<br><br>";
}

// Step 5: Update bot.php with inline keyboard option
echo "<h2>5. Updating Bot Code</h2>";

// Create a backup of the current bot.php
copy('bot.php', 'bot_backup.php');
echo "✅ Backup created: bot_backup.php<br>";

// Step 6: Instructions for manual testing
echo "<h2>6. Manual Testing Instructions</h2>";
echo "<strong>To test WebApp button:</strong><br>";
echo "1. Stop the current bot (Ctrl+C)<br>";
echo "2. Run: <code>php start_bot.php</code><br>";
echo "3. Send <code>/start</code> to your bot<br>";
echo "4. Look for the WebApp button<br><br>";

echo "<strong>If button still doesn't appear:</strong><br>";
echo "1. Update your Telegram app to the latest version<br>";
echo "2. Make sure you're using the official Telegram app<br>";
echo "3. Try on mobile Telegram app<br>";
echo "4. Check if your bot has WebApp permissions<br><br>";

// Step 7: Alternative solutions
echo "<h2>7. Alternative Solutions</h2>";
echo "<strong>Option 1: Use Inline Keyboard</strong><br>";
echo "The inline keyboard might work better than regular keyboard<br><br>";

echo "<strong>Option 2: Use Deep Link</strong><br>";
echo "Create a deep link: <code>https://t.me/PCN_OfficialBot?start=webapp</code><br><br>";

echo "<strong>Option 3: Use Menu Button</strong><br>";
echo "Set up a menu button that opens the WebApp<br><br>";

// Step 8: Check Telegram app version
echo "<h2>8. Telegram App Requirements</h2>";
echo "✅ Telegram app version 6.0+ required<br>";
echo "✅ Official Telegram app (not third-party)<br>";
echo "✅ WebApp feature enabled<br>";
echo "✅ Bot has WebApp permissions<br><br>";

echo "<h2>9. Next Steps</h2>";
echo "1. Try the inline keyboard test above<br>";
echo "2. Update your Telegram app<br>";
echo "3. Test on mobile Telegram app<br>";
echo "4. Check bot permissions with @BotFather<br><br>";

echo "<strong>🎯 Test Complete!</strong><br>";
echo "The inline keyboard test should work better than the regular keyboard.<br>";
?> 