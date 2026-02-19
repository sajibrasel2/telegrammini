<?php
require_once 'config.php';
require_once 'bot.php';

echo "<h1>🧪 Testing WebApp Commands</h1>";

$bot = new PCNCoinBot();

// Test chat ID (your actual chat ID)
$testChatId = '5785952613';

echo "<h2>Testing Commands:</h2>";

// Test 1: /start command
echo "<h3>1. Testing /start command</h3>";
$startMessage = "🚀 <b>Welcome to PCN Coin!</b>\n\n";
$startMessage .= "💰 <b>Earn PCN Coins through referrals!</b>\n";
$startMessage .= "• Get " . REFERRAL_BONUS . " PCN per referral\n";
$startMessage .= "• Minimum withdrawal: " . MIN_WITHDRAWAL . " PCN\n\n";
$startMessage .= "📋 <b>Commands:</b>\n";
$startMessage .= "/balance - Check your balance\n";
$startMessage .= "/referral - Get your referral link\n";
$startMessage .= "/checkin - Daily check-in (5 PCN)\n";
$startMessage .= "/subscription - Upgrade to paid plan\n";
$startMessage .= "/withdraw - Withdraw PCN coins\n";
$startMessage .= "/help - Get help\n";
$startMessage .= "/stats - Your referral stats\n";
$startMessage .= "/webapp - Open Web App\n\n";
$startMessage .= "🌐 <b>Web App:</b>\n";
$startMessage .= "Click the button below to open the Mini App!";

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

$result = $bot->sendMessage($testChatId, $startMessage, 'HTML', $inlineKeyboard);

if ($result && $result['ok']) {
    echo "✅ /start command sent successfully!<br>";
    echo "Message ID: {$result['result']['message_id']}<br><br>";
} else {
    echo "❌ /start command failed<br>";
    if ($result) {
        echo "Error: " . $result['description'] . "<br>";
    }
    echo "<br>";
}

// Test 2: /webapp command
echo "<h3>2. Testing /webapp command</h3>";
$webappMessage = "🌐 <b>PCN Coin Web App</b>\n\n";
$webappMessage .= "Click the button below to open the Mini App!\n";
$webappMessage .= "Experience our full web interface inside Telegram.";

$webappKeyboard = [
    'inline_keyboard' => [
        [
            [
                'text' => '🌐 Open Web App',
                'web_app' => ['url' => 'http://localhost/telegram/index.php']
            ]
        ]
    ]
];

$result2 = $bot->sendMessage($testChatId, $webappMessage, 'HTML', $webappKeyboard);

if ($result2 && $result2['ok']) {
    echo "✅ /webapp command sent successfully!<br>";
    echo "Message ID: {$result2['result']['message_id']}<br><br>";
} else {
    echo "❌ /webapp command failed<br>";
    if ($result2) {
        echo "Error: " . $result2['description'] . "<br>";
    }
    echo "<br>";
}

// Test 3: /app command (alias)
echo "<h3>3. Testing /app command (alias)</h3>";
$appMessage = "🌐 <b>PCN Coin Web App</b>\n\n";
$appMessage .= "Click the button below to open the Mini App!\n";
$appMessage .= "Experience our full web interface inside Telegram.";

$appKeyboard = [
    'inline_keyboard' => [
        [
            [
                'text' => '🌐 Open Web App',
                'web_app' => ['url' => 'http://localhost/telegram/index.php']
            ]
        ]
    ]
];

$result3 = $bot->sendMessage($testChatId, $appMessage, 'HTML', $appKeyboard);

if ($result3 && $result3['ok']) {
    echo "✅ /app command sent successfully!<br>";
    echo "Message ID: {$result3['result']['message_id']}<br><br>";
} else {
    echo "❌ /app command failed<br>";
    if ($result3) {
        echo "Error: " . $result3['description'] . "<br>";
    }
    echo "<br>";
}

echo "<h2>📱 Manual Testing Instructions:</h2>";
echo "<strong>1. Send these commands to your bot:</strong><br>";
echo "• <code>/start</code> - Should show WebApp button<br>";
echo "• <code>/webapp</code> - Direct WebApp access<br>";
echo "• <code>/app</code> - Same as /webapp<br><br>";

echo "<strong>2. If buttons don't appear:</strong><br>";
echo "• Update Telegram app to latest version<br>";
echo "• Use mobile Telegram app<br>";
echo "• Check bot permissions with @BotFather<br><br>";

echo "<strong>3. For production:</strong><br>";
echo "• Use HTTPS URL instead of localhost<br>";
echo "• Set up ngrok: <code>ngrok http 80</code><br>";
echo "• Update URL in bot.php<br><br>";

echo "<strong>🎯 Test Complete!</strong><br>";
echo "Check your Telegram for the test messages.<br>";
?> 