<?php
require_once 'config.php';
require_once 'database.php';

// Simulate Telegram WebApp environment for testing
$testUser = [
    'id' => 123456789,
    'username' => 'testuser',
    'first_name' => 'Test',
    'last_name' => 'User',
    'is_premium' => false,
    'language_code' => 'en'
];

// Create test session
session_start();
$_SESSION['user_id'] = $testUser['id'];
$_SESSION['username'] = $testUser['username'];
$_SESSION['first_name'] = $testUser['first_name'];
$_SESSION['last_name'] = $testUser['last_name'];
$_SESSION['is_premium'] = $testUser['is_premium'];
$_SESSION['language_code'] = $testUser['language_code'];
$_SESSION['authenticated'] = true;

$db = new Database();
$user = $db->getUser($testUser['id'], $testUser['username']);

if (!$user) {
    // Create test user
    $db->getConnection()->prepare("
        INSERT INTO users (id, username, balance, total_earned, joined_date) 
        VALUES (?, ?, 100, 150, NOW())
    ")->execute([$testUser['id'], $testUser['username']]);
    
    $user = $db->getUser($testUser['id'], $testUser['username']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Coin - WebApp Test</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #1a1a1a 100%);
            min-height: 100vh;
            color: white;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
        }
        .test-info {
            background: rgba(0,0,0,0.3);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .button {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
            transition: all 0.3s ease;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-coins"></i> PCN Coin WebApp Test</h1>
        
        <div class="test-info">
            <h3>🧪 Test Environment</h3>
            <p><strong>User ID:</strong> <?php echo $testUser['id']; ?></p>
            <p><strong>Username:</strong> @<?php echo $testUser['username']; ?></p>
            <p><strong>Name:</strong> <?php echo $testUser['first_name'] . ' ' . $testUser['last_name']; ?></p>
            <p><strong>Balance:</strong> <?php echo $user['balance'] ?? 0; ?> PCN</p>
            <p><strong>Total Earned:</strong> <?php echo $user['total_earned'] ?? 0; ?> PCN</p>
        </div>
        
        <h3>🔗 Test Links</h3>
        <a href="index.php" class="button">🏠 Home Page</a>
        <a href="referral.php?user_id=<?php echo $testUser['id']; ?>" class="button">🔗 Referral System</a>
        <a href="payment.php" class="button">💳 Payment System</a>
        <a href="admin_payments.php" class="button">⚙️ Admin Panel</a>
        
        <h3>📱 Telegram WebApp Features</h3>
        <p>✅ WebApp API Integration</p>
        <p>✅ User Authentication</p>
        <p>✅ Session Management</p>
        <p>✅ Mobile Responsive Design</p>
        <p>✅ Real-time User Data</p>
        
        <div class="test-info">
            <h4>🔧 How to Test:</h4>
            <ol>
                <li>Start your bot with: <code>php start_bot.php</code></li>
                <li>Send <code>/start</code> to your bot</li>
                <li>Click "🌐 Open Web App" button</li>
                <li>Test all features in Telegram's in-app browser</li>
            </ol>
        </div>
        
        <div class="test-info">
            <h4>🌐 For Production:</h4>
            <p>Replace <code>http://localhost/telegram/</code> with your public HTTPS URL in:</p>
            <ul>
                <li><code>bot.php</code> - WebApp URL</li>
                <li><code>config.php</code> - Webhook URL (optional)</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Simulate Telegram WebApp API for testing
        if (!window.Telegram) {
            window.Telegram = {
                WebApp: {
                    initDataUnsafe: {
                        user: <?php echo json_encode($testUser); ?>
                    },
                    expand: function() { console.log('WebApp expanded'); },
                    ready: function() { console.log('WebApp ready'); },
                    onEvent: function(event, callback) { console.log('Event listener added:', event); }
                }
            };
        }
        
        // Initialize WebApp
        window.onload = function() {
            if (window.Telegram && window.Telegram.WebApp) {
                const tg = window.Telegram.WebApp;
                tg.expand();
                tg.ready();
                
                console.log('Test WebApp initialized');
                console.log('User:', tg.initDataUnsafe.user);
            }
        };
    </script>
</body>
</html> 