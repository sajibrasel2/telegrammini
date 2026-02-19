<?php
require_once 'config.php';
require_once 'database.php';
require_once 'bot.php';

$status = [];
$errors = [];

// Check bot connection
try {
    $bot = new PCNCoinBot();
    $botInfo = $bot->getMe();
    if ($botInfo && $botInfo['ok']) {
        $status['bot'] = [
            'status' => '✅ Online',
            'name' => $botInfo['result']['first_name'],
            'username' => '@' . $botInfo['result']['username'],
            'id' => $botInfo['result']['id']
        ];
    } else {
        $status['bot'] = ['status' => '❌ Offline', 'error' => 'Bot connection failed'];
        $errors[] = 'Bot connection failed';
    }
} catch (Exception $e) {
    $status['bot'] = ['status' => '❌ Error', 'error' => $e->getMessage()];
    $errors[] = 'Bot error: ' . $e->getMessage();
}

// Check database connection
try {
    $db = new Database();
    $stats = $db->getTotalStats();
    $status['database'] = [
        'status' => '✅ Connected',
        'host' => DB_HOST,
        'database' => DB_NAME,
        'total_users' => $stats['total_users'],
        'total_earned' => $stats['total_earned'],
        'total_balance' => $stats['total_balance']
    ];
} catch (Exception $e) {
    $status['database'] = ['status' => '❌ Error', 'error' => $e->getMessage()];
    $errors[] = 'Database error: ' . $e->getMessage();
}

// Check webhook status
try {
    $webhookInfo = $bot->getWebhookInfo();
    if ($webhookInfo && $webhookInfo['ok']) {
        $info = $webhookInfo['result'];
        $status['webhook'] = [
            'status' => $info['url'] ? '✅ Active' : '⚠️ Not Set',
            'url' => $info['url'] ?: 'Not configured',
            'pending_updates' => $info['pending_update_count']
        ];
    } else {
        $status['webhook'] = ['status' => '❌ Error', 'error' => 'Webhook check failed'];
    }
} catch (Exception $e) {
    $status['webhook'] = ['status' => '❌ Error', 'error' => $e->getMessage()];
}

// Check log files
$logFiles = [
    'pcn_bot_log.txt' => 'Bot Activity Log',
    'bot_log.txt' => 'General Bot Log'
];

foreach ($logFiles as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        $status['logs'][$file] = [
            'status' => '✅ Exists',
            'size' => $size . ' bytes',
            'description' => $description
        ];
    } else {
        $status['logs'][$file] = [
            'status' => '⚠️ Missing',
            'description' => $description
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Coin - System Status</title>
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
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .status-card {
            background: rgba(52, 73, 94, 0.8);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .status-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #3498db;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            font-weight: 500;
        }
        
        .status-value {
            font-weight: bold;
        }
        
        .online { color: #2ecc71; }
        .offline { color: #e74c3c; }
        .warning { color: #f39c12; }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .error-section {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .error-section h3 {
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .error-list {
            list-style: none;
        }
        
        .error-list li {
            color: #ecf0f1;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-server"></i> System Status</h1>
            <p>PCN Coin Referral Bot - Real-time Status Monitor</p>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="error-section">
            <h3><i class="fas fa-exclamation-triangle"></i> Issues Detected</h3>
            <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="status-grid">
            <!-- Bot Status -->
            <div class="status-card">
                <h3><i class="fas fa-robot"></i> Telegram Bot</h3>
                <?php foreach ($status['bot'] as $key => $value): ?>
                <div class="status-item">
                    <span class="status-label"><?php echo ucfirst($key); ?>:</span>
                    <span class="status-value <?php echo $key === 'status' ? (strpos($value, '✅') !== false ? 'online' : 'offline') : ''; ?>">
                        <?php echo htmlspecialchars($value); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Database Status -->
            <div class="status-card">
                <h3><i class="fas fa-database"></i> Database</h3>
                <?php foreach ($status['database'] as $key => $value): ?>
                <div class="status-item">
                    <span class="status-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</span>
                    <span class="status-value <?php echo $key === 'status' ? (strpos($value, '✅') !== false ? 'online' : 'offline') : ''; ?>">
                        <?php echo htmlspecialchars($value); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Webhook Status -->
            <div class="status-card">
                <h3><i class="fas fa-link"></i> Webhook</h3>
                <?php foreach ($status['webhook'] as $key => $value): ?>
                <div class="status-item">
                    <span class="status-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</span>
                    <span class="status-value <?php echo $key === 'status' ? (strpos($value, '✅') !== false ? 'online' : (strpos($value, '⚠️') !== false ? 'warning' : 'offline')) : ''; ?>">
                        <?php echo htmlspecialchars($value); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Log Files -->
            <div class="status-card">
                <h3><i class="fas fa-file-alt"></i> Log Files</h3>
                <?php foreach ($status['logs'] as $file => $info): ?>
                <div class="status-item">
                    <span class="status-label"><?php echo htmlspecialchars($info['description']); ?>:</span>
                    <span class="status-value <?php echo $info['status'] === '✅ Exists' ? 'online' : 'warning'; ?>">
                        <?php echo $info['status']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="actions">
            <a href="index.php" class="btn"><i class="fas fa-home"></i> Home</a>
            <a href="setup.php" class="btn"><i class="fas fa-cog"></i> Setup</a>
            <a href="test.php" class="btn"><i class="fas fa-vial"></i> Test</a>
            <a href="https://t.me/<?php echo BOT_NAME; ?>" class="btn" target="_blank">
                <i class="fab fa-telegram"></i> Open Bot
            </a>
        </div>
    </div>
</body>
</html> 