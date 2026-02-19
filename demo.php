<?php
require_once 'config.php';
require_once 'database.php';
require_once 'bot.php';

$db = new Database();
$bot = new PCNCoinBot();

// Handle demo actions
$action = $_GET['action'] ?? '';
$message = '';

if ($action === 'test_admin') {
    $adminMessage = [
        'chat' => ['id' => ADMIN_USER_ID],
        'from' => ['id' => ADMIN_USER_ID, 'username' => ADMIN_USER_ID],
        'text' => '/start'
    ];
    $result = $bot->processMessage($adminMessage);
    $message = 'Admin command tested successfully!';
} elseif ($action === 'test_user') {
    $userMessage = [
        'chat' => ['id' => '123456789'],
        'from' => ['id' => '123456789', 'username' => 'demo_user'],
        'text' => '/start'
    ];
    $result = $bot->processMessage($userMessage);
    $message = 'User command tested successfully!';
} elseif ($action === 'test_referral') {
    $referralMessage = [
        'chat' => ['id' => '987654321'],
        'from' => ['id' => '987654321', 'username' => 'new_user'],
        'text' => '/start ref' . ADMIN_USER_ID
    ];
    $result = $bot->processMessage($referralMessage);
    $message = 'Referral system tested successfully!';
} elseif ($action === 'test_checkin') {
    $checkinMessage = [
        'chat' => ['id' => ADMIN_USER_ID],
        'from' => ['id' => ADMIN_USER_ID, 'username' => ADMIN_USER_ID],
        'text' => '/checkin'
    ];
    $result = $bot->processMessage($checkinMessage);
    $message = 'Daily check-in tested successfully!';
}

$stats = $db->getTotalStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Coin - Demo Interface</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 50%, #1a1a1a 100%);
            min-height: 100vh;
            color: white;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 0 0 10px rgba(255,255,255,0.8);
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .demo-card {
            background: rgba(52, 73, 94, 0.8);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .demo-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #3498db;
        }
        
        .demo-card p {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .demo-btn {
            display: inline-block;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            margin: 5px;
        }
        
        .demo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .demo-btn.success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        
        .demo-btn.warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .demo-btn.danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .stats-section {
            background: rgba(52, 73, 94, 0.8);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .stats-section h2 {
            text-align: center;
            color: white;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: rgba(44, 62, 80, 0.5);
            border-radius: 10px;
        }
        
        .stat-item i {
            font-size: 2rem;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .stat-item h4 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .stat-item p {
            color: #bdc3c7;
        }
        
        .message-box {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .telegram-preview {
            background: rgba(52, 73, 94, 0.8);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .telegram-preview h2 {
            text-align: center;
            color: white;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        
        .telegram-message {
            background: #2c3e50;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }
        
        .telegram-message h4 {
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .telegram-message p {
            color: #ecf0f1;
            line-height: 1.6;
        }
        
        .controls {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-play-circle"></i> Demo Interface</h1>
            <p>Test PCN Coin Bot functionality</p>
        </div>
        
        <?php if ($message): ?>
        <div class="message-box">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="stats-section">
            <h2><i class="fas fa-chart-bar"></i> Current Statistics</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <i class="fas fa-users"></i>
                    <h4><?php echo number_format($stats['total_users']); ?></h4>
                    <p>Total Users</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-coins"></i>
                    <h4><?php echo number_format($stats['total_earned']); ?></h4>
                    <p>Total PCN Earned</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-wallet"></i>
                    <h4><?php echo number_format($stats['total_balance']); ?></h4>
                    <p>Total Balance</p>
                </div>
                <div class="stat-item">
                    <i class="fas fa-user-check"></i>
                    <h4><?php echo number_format($stats['active_users']); ?></h4>
                    <p>Active Users</p>
                </div>
            </div>
        </div>
        
        <div class="demo-grid">
            <div class="demo-card">
                <h3><i class="fas fa-user-shield"></i> Admin Commands</h3>
                <p>Test admin-specific bot commands and functionality.</p>
                <a href="?action=test_admin" class="demo-btn">
                    <i class="fas fa-user-shield"></i> Test Admin
                </a>
                <a href="?action=test_checkin" class="demo-btn success">
                    <i class="fas fa-calendar-check"></i> Test Check-in
                </a>
            </div>
            
            <div class="demo-card">
                <h3><i class="fas fa-user"></i> User Commands</h3>
                <p>Test regular user bot commands and responses.</p>
                <a href="?action=test_user" class="demo-btn">
                    <i class="fas fa-user"></i> Test User
                </a>
                <a href="?action=test_referral" class="demo-btn warning">
                    <i class="fas fa-share-alt"></i> Test Referral
                </a>
            </div>
            
            <div class="demo-card">
                <h3><i class="fas fa-cog"></i> System Tests</h3>
                <p>Test database connections and system functionality.</p>
                <a href="test_bot.php" class="demo-btn" target="_blank">
                    <i class="fas fa-vial"></i> Run Full Test
                </a>
                <a href="status.php" class="demo-btn">
                    <i class="fas fa-server"></i> System Status
                </a>
            </div>
        </div>
        
        <div class="telegram-preview">
            <h2><i class="fab fa-telegram"></i> Telegram Bot Preview</h2>
            
            <div class="telegram-message">
                <h4>🤖 PCN Coin Bot</h4>
                <p>🚀 Welcome to PCN Coin Referral Bot!</p>
                <p>💰 Earn PCN Coins through referrals!</p>
                <p>• Get 10 PCN per referral<br>
                • Minimum withdrawal: 100 PCN</p>
                <p>📋 Commands:<br>
                /balance - Check your balance<br>
                /referral - Get your referral link<br>
                /checkin - Daily check-in (5 PCN)<br>
                /subscription - Upgrade to paid plan<br>
                /withdraw - Withdraw PCN coins<br>
                /help - Get help<br>
                /stats - Your referral stats</p>
            </div>
            
            <div class="telegram-message">
                <h4>💰 Your PCN Balance</h4>
                <p>Current Balance: <b>0 PCN</b><br>
                Total Earned: <b>0 PCN</b><br>
                Referrals: <b>0</b><br>
                Plan: <b>Free</b><br>
                Check-in Streak: <b>0 days</b></p>
                <p>✅ Daily Check-in Available!<br>
                Use /checkin to earn 5 PCN</p>
                <p>⚠️ Minimum withdrawal: 100 PCN<br>
                Share your referral link to earn more!</p>
            </div>
        </div>
        
        <div class="controls">
            <a href="index.php" class="demo-btn">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="monitor.php" class="demo-btn">
                <i class="fas fa-chart-line"></i> Live Monitor
            </a>
            <a href="https://t.me/<?php echo BOT_NAME; ?>" class="demo-btn success" target="_blank">
                <i class="fab fa-telegram"></i> Open Real Bot
            </a>
        </div>
    </div>
</body>
</html> 