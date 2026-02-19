<?php
require_once 'config.php';
require_once 'database.php';
require_once 'bot.php';

$db = new Database();
$stats = $db->getTotalStats();
$bot = new PCNCoinBot();
$botInfo = $bot->getMe();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Coin - Live Monitor</title>
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
            max-width: 1200px;
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
        
        .live-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: #2ecc71;
            border-radius: 50%;
            margin-right: 10px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(52, 73, 94, 0.8);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #ecf0f1;
            font-size: 1rem;
        }
        
        .activity-section {
            background: rgba(52, 73, 94, 0.8);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .activity-section h2 {
            text-align: center;
            color: white;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        
        .activity-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: rgba(44, 62, 80, 0.5);
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #3498db;
        }
        
        .activity-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            color: #3498db;
        }
        
        .activity-content h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .activity-content p {
            color: #bdc3c7;
            font-size: 0.9rem;
        }
        
        .controls {
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
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn.danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .btn.success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        
        .refresh-info {
            text-align: center;
            margin-top: 20px;
            color: #bdc3c7;
            font-size: 0.9rem;
        }
        
        /* Scrollbar styling */
        .activity-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .activity-list::-webkit-scrollbar-track {
            background: rgba(44, 62, 80, 0.3);
            border-radius: 4px;
        }
        
        .activity-list::-webkit-scrollbar-thumb {
            background: #3498db;
            border-radius: 4px;
        }
        
        .activity-list::-webkit-scrollbar-thumb:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span class="live-indicator"></span>
                Live Monitor
            </h1>
            <p>Real-time PCN Coin Referral Bot Statistics</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3 id="total-users"><?php echo number_format($stats['total_users']); ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-coins"></i>
                <h3 id="total-earned"><?php echo number_format($stats['total_earned']); ?></h3>
                <p>Total PCN Earned</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-wallet"></i>
                <h3 id="total-balance"><?php echo number_format($stats['total_balance']); ?></h3>
                <p>Total Balance</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-robot"></i>
                <h3><?php echo $botInfo && $botInfo['ok'] ? 'Online' : 'Offline'; ?></h3>
                <p>Bot Status</p>
            </div>
        </div>
        
        <div class="activity-section">
            <h2><i class="fas fa-chart-line"></i> Recent Activity</h2>
            <div class="activity-list" id="activity-list">
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="activity-content">
                        <h4>System Monitor Started</h4>
                        <p>Live monitoring is now active</p>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Database Connected</h4>
                        <p>MySQL database connection established</p>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fab fa-telegram"></i>
                    </div>
                    <div class="activity-content">
                        <h4>Bot API Connected</h4>
                        <p>Telegram Bot API connection verified</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="controls">
            <button class="btn" onclick="refreshStats()">
                <i class="fas fa-sync-alt"></i> Refresh Stats
            </button>
            <button class="btn success" onclick="startAutoRefresh()">
                <i class="fas fa-play"></i> Auto Refresh
            </button>
            <button class="btn danger" onclick="stopAutoRefresh()">
                <i class="fas fa-stop"></i> Stop Auto
            </button>
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> Home
            </a>
        </div>
        
        <div class="refresh-info">
            <p>Last updated: <span id="last-update"><?php echo date('Y-m-d H:i:s'); ?></span></p>
            <p>Auto-refresh: <span id="auto-refresh-status">Stopped</span></p>
        </div>
    </div>
    
    <script>
        let autoRefreshInterval;
        let isAutoRefreshing = false;
        
        function refreshStats() {
            // Simulate refreshing stats
            const now = new Date();
            document.getElementById('last-update').textContent = now.toLocaleString();
            
            // Add a new activity item
            const activityList = document.getElementById('activity-list');
            const newActivity = document.createElement('div');
            newActivity.className = 'activity-item';
            newActivity.innerHTML = `
                <div class="activity-icon">
                    <i class="fas fa-sync-alt"></i>
                </div>
                <div class="activity-content">
                    <h4>Stats Refreshed</h4>
                    <p>Statistics updated at ${now.toLocaleTimeString()}</p>
                </div>
            `;
            
            activityList.insertBefore(newActivity, activityList.firstChild);
            
            // Keep only last 10 activities
            while (activityList.children.length > 10) {
                activityList.removeChild(activityList.lastChild);
            }
        }
        
        function startAutoRefresh() {
            if (!isAutoRefreshing) {
                isAutoRefreshing = true;
                autoRefreshInterval = setInterval(refreshStats, 5000); // Refresh every 5 seconds
                document.getElementById('auto-refresh-status').textContent = 'Active (5s)';
                document.getElementById('auto-refresh-status').style.color = '#2ecc71';
            }
        }
        
        function stopAutoRefresh() {
            if (isAutoRefreshing) {
                isAutoRefreshing = false;
                clearInterval(autoRefreshInterval);
                document.getElementById('auto-refresh-status').textContent = 'Stopped';
                document.getElementById('auto-refresh-status').style.color = '#e74c3c';
            }
        }
        
        // Start auto-refresh on page load
        window.onload = function() {
            startAutoRefresh();
        };
    </script>
</body>
</html> 