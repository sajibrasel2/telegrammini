<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/telegram_gate.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once 'config.php';
require_once 'database.php';
require_once 'security_helper.php';

$db = new Database();
$stats = $db->getTotalStats();

// Get user data if user_id is present
$user = null;
$is_paid_user = false;
$user_id_query = '';
if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    $user = $db->getUser($userId);
    if ($user) {
        $is_paid_user = ($user['subscription_type'] === 'paid');
        $can_check_in = $db->canCheckIn($userId);
    }
    $user_id_query = '?user_id=' . $userId . '&t=' . time();
    $mining_session = $db->getActiveMiningSession($userId);
    $ads = $db->getAdsConfig();
}

// Filter active ads
$ad_slot_1 = array_filter($ads ?? [], function($a) { return $a['ad_slot'] === 'slot_1' && $a['status'] === 'active'; });
$ad_slot_2 = array_filter($ads ?? [], function($a) { return $a['ad_slot'] === 'slot_2' && $a['status'] === 'active'; });
$ad_code_1 = !empty($ad_slot_1) ? reset($ad_slot_1)['ad_code'] : '';
$ad_code_2 = !empty($ad_slot_2) ? reset($ad_slot_2)['ad_code'] : '';

// Crypto rotation data
$cryptoData = [
    ['name' => 'Bitcoin', 'symbol' => 'BTC', 'price' => '$43,250', 'change' => '+2.5%', 'icon' => 'fab fa-bitcoin'],
    ['name' => 'Ethereum', 'symbol' => 'ETH', 'price' => '$2,680', 'change' => '+1.8%', 'icon' => 'fab fa-ethereum'],
    ['name' => 'TON', 'symbol' => 'TON', 'price' => '$2.45', 'change' => '+5.2%', 'icon' => 'fas fa-coins'],
    ['name' => 'Solana', 'symbol' => 'SOL', 'price' => '$98.50', 'change' => '+3.1%', 'icon' => 'fas fa-sun'],
    ['name' => 'Cardano', 'symbol' => 'ADA', 'price' => '$0.52', 'change' => '+1.2%', 'icon' => 'fas fa-chart-line'],
    ['name' => 'Polkadot', 'symbol' => 'DOT', 'price' => '$7.20', 'change' => '+2.8%', 'icon' => 'fas fa-circle']
];

// Tasks data
require_once 'tasks_config.php';

// Payment schedule data
$paymentSchedule = [
    ['phase' => 'Phase 1', 'date' => '01/01/2026', 'percentage' => '65%', 'status' => 'active', 'description' => 'Initial Listing Payment'],
    ['phase' => 'Phase 2', 'date' => '01/04/2026', 'percentage' => '20%', 'status' => 'upcoming', 'description' => 'Second Quarter Release'],
    ['phase' => 'Phase 3', 'date' => '01/07/2026', 'percentage' => '10%', 'status' => 'upcoming', 'description' => 'Third Quarter Release'],
    ['phase' => 'Phase 4', 'date' => '01/10/2026', 'percentage' => '5%', 'status' => 'upcoming', 'description' => 'Final Release']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Coin - Mining App</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/@tonconnect/ui@latest/dist/tonconnect-ui.min.js"></script>
    <?php include 'app_style.php'; ?>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        let tonConnectUI = null;
        try {
            if (window.TON_CONNECT_UI && TON_CONNECT_UI.TonConnectUI) {
                tonConnectUI = new TON_CONNECT_UI.TonConnectUI({
                    manifestUrl: 'https://' + window.location.hostname + '/telegrammini/tonconnect-manifest.json',
                    buttonRootId: 'ton-connect-button'
                });
            }
        } catch (e) {
            console.error('TonConnect init failed:', e);
            tonConnectUI = null;
        }

        async function saveWalletAddress(address) {
            try {
                const response = await fetch('save_wallet.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: <?php echo json_encode($user ? $user['id'] : null); ?>,
                        wallet_address: address
                    })
                });
                const result = await response.json();
                console.log('Wallet save result:', result);
            } catch (error) {
                console.error('Error saving wallet:', error);
            }
        }

        if (tonConnectUI && typeof tonConnectUI.onStatusChange === 'function') {
            tonConnectUI.onStatusChange(wallet => {
                const statusEl = document.getElementById('wallet-status');
                if (!statusEl) return;
                if (wallet) {
                    const address = wallet.account.address;
                    const shortAddress = address.substring(0, 6) + '...' + address.substring(address.length - 4);
                    statusEl.textContent = 'Connected: ' + shortAddress;
                    statusEl.style.color = 'var(--success)';
                    saveWalletAddress(address);
                } else {
                    statusEl.textContent = 'Not connected';
                    statusEl.style.color = 'var(--text-dim)';
                }
            });
        }

        // Ultra Professional Loading Manager
        function hideOverlay() {
            const overlay = document.getElementById('welcome-overlay');
            if (overlay) {
                overlay.style.transform = 'scale(1.1)';
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.style.display = 'none';
                }, 600);
            }
        }

        (function() {
            let progress = 0;
            const stages = [
                { p: 25, t: "Security Audit..." },
                { p: 50, t: "Mining Setup..." },
                { p: 80, t: "Blockchain Sync..." },
                { p: 100, t: "Launching..." }
            ];

            function updateProgress() {
                const loadingBar = document.getElementById('loading-bar');
                const loadingText = document.getElementById('loading-text');
                const loadingPercentage = document.getElementById('loading-percentage');
                const svgProgress = document.getElementById('svg-progress');
                
                const stage = stages.find(s => progress < s.p) || stages[stages.length - 1];
                progress += Math.random() * 25;
                if (progress > 100) progress = 100;
                
                const roundedProgress = Math.round(progress);
                if (loadingBar) loadingBar.style.width = roundedProgress + '%';
                if (loadingText) loadingText.textContent = stage.t;
                if (loadingPercentage) loadingPercentage.textContent = roundedProgress + '%';
                
                if (svgProgress) {
                    const offset = 283 - (283 * roundedProgress / 100);
                    svgProgress.style.strokeDashoffset = offset;
                }

                if (progress < 100) {
                    setTimeout(updateProgress, 120);
                } else {
                    setTimeout(hideOverlay, 300);
                }
            }

            // Start progress when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', updateProgress);
            } else {
                updateProgress();
            }

            // Absolute safety fallback
            setTimeout(() => {
                hideOverlay();
            }, 3000);
        })();

        window.addEventListener('load', function() {
            const tg = window.Telegram.WebApp;
            tg.ready();

            const userId = <?php echo json_encode($user ? $user['id'] : null); ?>;
            const params = new URLSearchParams(window.location.search);
            const userIdFromUrl = params.get('user_id');

            if (!userIdFromUrl && tg.initDataUnsafe && tg.initDataUnsafe.user) {
                const userId = tg.initDataUnsafe.user.id;
                if (userId) {
                    // Reload the page with the user's ID
                    // Reload the page with the user_s ID
                    window.location.href = window.location.pathname + '?user_id=' + userId + window.location.hash;
                }
            } else {
                // If user_id is present, expand the app
                tg.expand();
            }
        });
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 25%, #16213e 50%, #0f3460 75%, #533483 100%);
            min-height: 100vh;
            padding-bottom: 80px;
            color: white;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
            position: relative;
        }
        
        .header h1 {
            font-size: 3.5rem;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientShift 3s ease-in-out infinite;
            text-shadow: 0 0 30px rgba(255,255,255,0.3);
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .header p {
            font-size: 1.3rem;
            opacity: 0.9;
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
        }

        .balance-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .balance-section h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #f1c40f;
        }

        .balance-amount {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2ecc71;
            margin-bottom: 5px;
        }

        .balance-usd {
            font-size: 1.1rem;
            color: #f1c40f;
            opacity: 0.9;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .balance-section .username {
            font-size: 1rem;
            color: #ecf0f1;
            opacity: 0.8;
        }
        
        /* Crypto Rotation Section */
        .crypto-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .crypto-scroll-container {
            overflow: hidden;
            position: relative;
            width: 100%;
            -webkit-mask-image: linear-gradient(to right, transparent, black 20%, black 80%, transparent);
            mask-image: linear-gradient(to right, transparent, black 20%, black 80%, transparent);
        }

        .crypto-grid {
            display: flex;
            gap: 20px;
            width: fit-content;
            animation: scroll 40s linear infinite;
        }

        @keyframes scroll {
            from {
                transform: translateX(0);
            }
            to {
                transform: translateX(-50%);
            }
        }

        .crypto-scroll-container:hover .crypto-grid {
            animation-play-state: paused;
        }

        .crypto-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 15px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
            width: 160px;
            flex-shrink: 0;
        }
        
        .crypto-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .crypto-card:hover::before {
            left: 100%;
        }
        
        .crypto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        
        .crypto-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #4ecdc4;
        }
        
        .crypto-card h3 {
            font-size: 1.2rem;
            margin-bottom: 5px;
            color: white;
        }
        
        .crypto-card .symbol {
            font-size: 0.9rem;
            color: #bdc3c7;
            margin-bottom: 10px;
        }
        
        .crypto-card .price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #2ecc71;
            margin-bottom: 5px;
        }
        
        .crypto-card .change {
            font-size: 0.9rem;
            color: #2ecc71;
        }
        
        .crypto-card .change.negative {
            color: #e74c3c;
        }
        
        /* Payment Schedule Section */
        .payment-schedule {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        .payment-schedule h2 {
            text-align: center;
            color: white;
            margin-bottom: 25px;
            font-size: 2rem;
            text-shadow: 0 0 10px rgba(255,255,255,0.6);
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .schedule-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        .schedule-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .schedule-card .phase {
            font-size: 1.3rem;
            font-weight: bold;
        }
        .schedule-card .date {
            font-size: 0.9rem;
            color: #bdc3c7;
            margin-bottom: 10px;
        }
        .schedule-card .description {
            font-size: 0.95rem;
            margin-bottom: 15px;
        }
        .schedule-card .status {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }
        .schedule-card .status.active {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        .schedule-card .status.upcoming {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
            border: 1px solid rgba(243, 156, 18, 0.3);
        }

        /* Tasks Section */
        .tasks-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .tasks-section h2 {
            text-align: center;
            color: white;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        .task-list {
            display: grid;
            gap: 15px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        .task-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        .task-item:hover {
            transform: translateY(-3px);
        }
        .task-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .task-info .icon {
            font-size: 1.8rem;
            color: #f39c12;
        }
        .task-details h3 {
            margin: 0;
            font-size: 1.1rem;
        }
        .task-action .btn {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .task-action .btn.completed {
             background: linear-gradient(135deg, #555, #777);
             cursor: not-allowed;
        }
        
        /* Stats Section */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            color: #4ecdc4;
            margin-bottom: 15px;
            text-shadow: 0 0 10px rgba(78, 205, 196, 0.8);
        }
        
        .stat-card h3 {
            font-size: 1.8rem;
            color: white;
            margin-bottom: 5px;
            text-shadow: 0 0 8px rgba(255,255,255,0.6);
        }
        
        .stat-card p {
            color: #ecf0f1;
            font-size: 1rem;
            text-shadow: 0 0 5px rgba(255,255,255,0.4);
        }
        
        .features {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .features h2 {
            text-align: center;
            color: white;
            margin-bottom: 25px;
            font-size: 2rem;
            text-shadow: 0 0 10px rgba(255,255,255,0.6);
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            padding: 20px;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 15px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }
        
        .feature-item i {
            font-size: 2rem;
            margin-right: 20px;
            color: #4ecdc4;
            text-shadow: 0 0 8px rgba(78, 205, 196, 0.6);
        }
        
        .feature-text h3 {
            font-size: 1.3rem;
            margin-bottom: 8px;
            text-shadow: 0 0 6px rgba(255,255,255,0.6);
        }
        
        .feature-text p {
            opacity: 0.9;
            line-height: 1.5;
            text-shadow: 0 0 4px rgba(255,255,255,0.4);
        }
        
        .claim-btn {
            background: linear-gradient(135deg, #4ecdc4 0%, #2a9d8f 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            margin-left: auto;
        }

        .claim-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(78, 205, 196, 0.3);
        }

        .claim-btn:disabled {
            background: #555;
            color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .cta-section {
            text-align: center;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .cta-section h2 {
            color: white;
            margin-bottom: 20px;
            font-size: 2rem;
            text-shadow: 0 0 10px rgba(255,255,255,0.6);
        }
        
        .cta-section p {
            color: #ecf0f1;
            margin-bottom: 30px;
            font-size: 1.1rem;
            line-height: 1.6;
            text-shadow: 0 0 5px rgba(255,255,255,0.4);
        }
        
        .telegram-btn {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #0088cc 0%, #005580 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0,136,204,0.3);
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        
        .telegram-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,136,204,0.4);
        }
        
        .telegram-btn i {
            margin-right: 10px;
            font-size: 1.4rem;
        }
        
        .mining-section {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .mining-live-amount {
            font-size: 2.2rem;
            font-weight: 800;
            color: #4ecdc4;
            margin: 10px 0;
            text-shadow: 0 0 15px rgba(78, 205, 196, 0.5);
            font-family: 'Courier New', monospace;
        }
        .mining-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 8px solid rgba(255, 255, 255, 0.1);
            border-top: 8px solid #4ecdc4;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mining-circle.active {
            animation: rotate 2s linear infinite;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .mining-circle i {
            font-size: 3rem;
            color: #4ecdc4;
        }
        .mining-timer {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 15px 0;
            color: #fff;
        }
        .mining-btn {
            background: linear-gradient(135deg, #4ecdc4, #45b7af);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 200px;
        }
        .mining-btn:disabled { background: #555; cursor: not-allowed; }
        .mining-btn.claim { background: linear-gradient(135deg, #f39c12, #e67e22); }

        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 15, 35, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 15px 0;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
        }
        
        .nav-container {
            display: flex;
            justify-content: space-around;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #bdc3c7;
            transition: all 0.3s ease;
            padding: 8px 15px;
            border-radius: 10px;
        }
        
        .nav-item.active {
            color: #4ecdc4;
            background: rgba(78, 205, 196, 0.2);
        }
        
        .nav-item i {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .nav-item span {
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2.5rem;
            }
            
            .crypto-grid, .schedule-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .stat-card h3 {
                font-size: 1.5rem;
            }
            
            .features {
                padding: 20px;
            }
            
            .feature-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-section {
                padding: 30px 20px;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .telegram-btn {
                padding: 12px 25px;
                font-size: 1rem;
            }
        }
        .pcn-coin-animated span {
            display: inline-block;
            background: linear-gradient(270deg, #FFD700, #FF6B6B, #4ecdc4, #45b7d1, #feca57, #FFD700);
            background-size: 1200% 1200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: pcn-gradient-move 2.5s linear infinite, pcn-coin-scale 1.5s ease-in-out infinite;
        }
        @keyframes pcn-gradient-move {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @keyframes pcn-coin-scale {
            0%, 100% { transform: scale(1); filter: brightness(1); }
            50% { transform: scale(1.08); filter: brightness(1.25); }
        }
    </style>
</head>
<body>
    <!-- Ultra Professional Loading Overlay -->
    <div id="welcome-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #0b0b1a; display: flex; flex-direction: column; justify-content: center; align-items: center; z-index: 9999; transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden;">
        <!-- Animated Background Particles -->
        <div style="position: absolute; width: 100%; height: 100%; overflow: hidden; pointer-events: none; opacity: 0.3;">
            <div style="position: absolute; width: 2px; height: 2px; background: #00f2ff; top: 20%; left: 30%; border-radius: 50%; animation: float 4s infinite alternate;"></div>
            <div style="position: absolute; width: 3px; height: 3px; background: #feca57; top: 70%; left: 80%; border-radius: 50%; animation: float 6s infinite alternate-reverse;"></div>
            <div style="position: absolute; width: 2px; height: 2px; background: #2ecc71; top: 40%; left: 10%; border-radius: 50%; animation: float 5s infinite alternate;"></div>
        </div>

        <div style="position: relative; margin-bottom: 30px;">
            <!-- Outer Glow -->
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 140px; height: 140px; background: radial-gradient(circle, rgba(0, 242, 255, 0.15) 0%, transparent 70%); animation: pulseGlow 2s infinite;"></div>
            
            <!-- Circular Progress SVG -->
            <svg width="100" height="100" viewBox="0 0 100 100" style="transform: rotate(-90deg);">
                <circle cx="50" cy="50" r="45" stroke="rgba(255,255,255,0.05)" stroke-width="4" fill="none" />
                <circle id="svg-progress" cx="50" cy="50" r="45" stroke="var(--primary)" stroke-width="4" fill="none" 
                    stroke-dasharray="283" stroke-dashoffset="283" style="transition: stroke-dashoffset 0.3s ease-out; stroke-linecap: round;" />
            </svg>
            
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <i class="fas fa-coins" style="font-size: 2.8rem; color: var(--primary); animation: coinFlip 3s ease-in-out infinite;"></i>
            </div>
        </div>

        <div style="text-align: center;">
            <h1 style="color: white; font-size: 1.8rem; font-weight: 700; letter-spacing: 4px; margin-bottom: 5px; text-shadow: 0 0 15px rgba(0,242,255,0.3);">PCN COIN</h1>
            <p style="color: var(--primary); font-size: 0.7rem; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; opacity: 0.8; margin-bottom: 25px;">Ecosystem Distribution</p>
        </div>

        <!-- Glassmorphism Loading Box -->
        <div style="background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); padding: 15px 30px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); width: 220px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span id="loading-text" style="color: var(--text-dim); font-size: 0.75rem; font-weight: 500;">Initializing...</span>
                <span id="loading-percentage" style="color: var(--primary); font-size: 0.75rem; font-weight: 600;">0%</span>
            </div>
            <div style="width: 100%; height: 3px; background: rgba(255,255,255,0.05); border-radius: 2px; overflow: hidden;">
                <div id="loading-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, var(--primary), var(--accent)); transition: width 0.3s ease-out;"></div>
            </div>
        </div>
    </div>

    <style>
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes float { from { transform: translateY(0px) translateX(0px); } to { transform: translateY(-20px) translateX(10px); } }
        @keyframes pulseGlow { 0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.5; } 50% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.8; } }
        @keyframes coinFlip { 0% { transform: translate(-50%, -50%) rotateY(0deg); } 50% { transform: translate(-50%, -50%) rotateY(180deg); } 100% { transform: translate(-50%, -50%) rotateY(360deg); } }
    </style>

    <div class="app-container">
        <!-- Wallet Connection Section -->
        <div class="app-card" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; background: rgba(0, 152, 234, 0.05); border: 1px solid rgba(0, 152, 234, 0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(0, 152, 234, 0.15); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-wallet" style="color: #0098ea; font-size: 1.2rem;"></i>
                </div>
                <div>
                    <h4 style="margin: 0; font-size: 0.95rem; font-weight: 600;">TON Wallet</h4>
                    <p id="wallet-status" style="margin: 2px 0 0; font-size: 0.75rem; color: var(--text-dim);">Not connected</p>
                </div>
            </div>
            <div id="ton-connect-button"></div>
        </div>

        <!-- Top Ad Slot -->
        <?php if (!empty($ad_code_1)): ?>
        <div class="ad-container" style="margin-bottom: 20px; text-align: center;">
            <?php echo $ad_code_1; ?>
        </div>
        <?php endif; ?>

        <?php if ($user): ?>
        <div class="app-card" style="text-align: center; border-bottom: 3px solid var(--primary);">
            <h2 style="color: var(--accent); font-size: 1.2rem; margin-bottom: 10px;">My PCN Balance</h2>
            <p style="font-size: 2.5rem; font-weight: 800; color: var(--success); margin-bottom: 5px;"><?php echo number_format($user['balance'], 2); ?> <span style="font-size: 1rem;">PCN</span></p>
            <p style="color: var(--accent); font-weight: 600;">≈ $<?php echo number_format($user['balance'] * 0.40, 2); ?> <span style="font-size: 0.8rem;">(@ $0.40)</span></p>
            <p style="margin-top: 15px; font-size: 0.9rem; opacity: 0.8;">Welcome, <?php echo Security::xss($user['username']); ?>!</p>
        </div>

        <div class="app-card mining-section" style="text-align: center; background: linear-gradient(180deg, rgba(78, 205, 196, 0.1) 0%, rgba(15, 15, 35, 0) 100%);">
            <h2 style="margin-bottom: 20px;"><i class="fas fa-hammer"></i> Mining Center</h2>
            <div class="mining-circle <?php echo ($mining_session && $mining_session['status'] === 'active') ? 'active' : ''; ?>" id="main-circle" style="width: 120px; height: 120px; border-width: 6px;">
                <i class="fas fa-gem" style="font-size: 2.5rem;"></i>
            </div>
            <div class="mining-live-amount" id="live-counter" style="font-size: 2rem;">0.000000</div>
            <div class="mining-timer" id="main-timer" style="background: rgba(0,0,0,0.3); display: inline-block; padding: 5px 20px; border-radius: 20px; font-size: 1.2rem;">--:--:--</div>
            
            <div id="mining-action-container" style="margin-top: 25px;">
                <?php if (!$mining_session): ?>
                    <button class="app-btn btn-primary" onclick="startMining()">Start Mining</button>
                <?php elseif ($mining_session['status'] === 'active'): ?>
                    <button class="app-btn" style="background: #34495e; color: #bdc3c7;" disabled><i class="fas fa-spinner fa-spin"></i> Mining Active</button>
                <?php elseif ($mining_session['status'] === 'completed'): ?>
                    <button class="app-btn" style="background: var(--success); color: white;" onclick="claimReward()"><i class="fas fa-gift"></i> Claim <?php echo $mining_session['reward']; ?> PCN</button>
                <?php endif; ?>
            </div>
            <p style="margin-top: 15px; font-size: 0.8rem; color: var(--text-dim);">
                Network Speed: <span style="color: var(--primary);"><?php echo ($is_paid_user) ? '50' : '25'; ?> PCN/24h</span>
            </p>
        </div>
        <?php endif; ?>

        <div class="app-header">
            <h1>PCN Platform</h1>
            <p style="font-size: 0.9rem; color: var(--text-dim);">Earn through referrals & tasks</p>
        </div>

        <div class="app-card" style="padding: 15px;">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                <div style="text-align: center; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 15px;">
                    <i class="fas fa-users" style="color: var(--primary); font-size: 1.2rem; margin-bottom: 5px;"></i>
                    <div style="font-weight: bold;"><?php echo number_format($stats['total_users']); ?></div>
                    <div style="font-size: 0.6rem; color: var(--text-dim);">Users</div>
                </div>
                <div style="text-align: center; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 15px;">
                    <i class="fas fa-calendar-day" style="color: var(--accent); font-size: 1.2rem; margin-bottom: 5px;"></i>
                    <div style="font-weight: bold;">Daily</div>
                    <div style="font-size: 0.6rem; color: var(--text-dim);">Rewards</div>
                </div>
                <div style="text-align: center; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 15px;">
                    <i class="fas fa-shield-alt" style="color: var(--success); font-size: 1.2rem; margin-bottom: 5px;"></i>
                    <div style="font-weight: bold;">Verified</div>
                    <div style="font-size: 0.6rem; color: var(--text-dim);">System</div>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 25px;">
            <h3 style="margin-bottom: 15px; font-size: 1.1rem; padding-left: 5px;">Daily Activities</h3>
            
            <div class="app-card" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 45px; height: 45px; background: rgba(46, 204, 113, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--success);">
                        <i class="fas fa-calendar-check fa-lg"></i>
                    </div>
                    <div>
                        <h4 style="font-size: 0.95rem;">Daily Check-in</h4>
                        <p style="font-size: 0.75rem; color: var(--text-dim);">+5 PCN every day</p>
                    </div>
                </div>
                <?php if ($user): ?>
                    <button id="checkin-btn" class="app-btn" style="width: auto; padding: 8px 15px; font-size: 0.85rem; border-radius: 10px; background: var(--success); color: white;" <?php echo !$can_check_in ? 'disabled' : ''; ?>>
                        <?php echo $can_check_in ? 'Claim' : 'Done'; ?>
                    </button>
                <?php endif; ?>
            </div>

            <a href="tasks.php<?php echo $user_id_query; ?>" style="text-decoration: none; color: inherit;">
                <div class="app-card" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; background: rgba(243, 156, 18, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--warning);">
                            <i class="fas fa-tasks fa-lg"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 0.95rem;">Social Tasks</h4>
                            <p style="font-size: 0.75rem; color: var(--text-dim);">Complete tasks & earn</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: var(--text-dim);"></i>
                </div>
            </a>

            <a href="referral.php<?php echo $user_id_query; ?>" style="text-decoration: none; color: inherit;">
                <div class="app-card" style="display: flex; align-items: center; justify-content: space-between; padding: 15px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; background: rgba(78, 205, 196, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                            <i class="fas fa-share-alt fa-lg"></i>
                        </div>
                        <div>
                            <h4 style="font-size: 0.95rem;">Invite Friends</h4>
                            <p style="font-size: 0.75rem; color: var(--text-dim);">Up to 10 PCN per referral</p>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: var(--text-dim);"></i>
                </div>
            </a>
        </div>

        <?php if (!$is_paid_user): ?>
        <div class="app-card" style="background: linear-gradient(135deg, rgba(254, 202, 87, 0.2) 0%, rgba(15, 15, 35, 0) 100%); border: 1px solid rgba(254, 202, 87, 0.3);">
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                <i class="fas fa-crown fa-2x" style="color: var(--accent);"></i>
                <div>
                    <h3 style="font-size: 1.1rem; color: var(--accent);">Upgrade to Premium</h3>
                    <p style="font-size: 0.8rem; color: var(--text-dim);">Earn 2X mining & 3X referral rewards</p>
                </div>
            </div>
            <a href="payment.php<?php echo $user_id_query; ?>" class="app-btn btn-primary" style="background: linear-gradient(135deg, #f39c12, #f1c40f);">Get Premium Access</a>
        </div>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <a href="payment_schedule.php<?php echo $user_id_query; ?>" class="app-btn btn-outline" style="border-color: rgba(255,255,255,0.2); color: var(--text-dim); font-size: 0.9rem;">
                <i class="fas fa-calendar-alt" style="margin-right: 10px;"></i> View Distribution Schedule
            </a>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php<?php echo $user_id_query; ?>" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="tasks.php<?php echo $user_id_query; ?>" class="nav-item">
            <i class="fas fa-tasks"></i>
            <span>Tasks</span>
        </a>
        <a href="referral.php<?php echo $user_id_query; ?>" class="nav-item">
            <i class="fas fa-users"></i>
            <span>Friends</span>
        </a>
        <a href="payment.php<?php echo $user_id_query; ?>" class="nav-item">
            <i class="fas fa-crown"></i>
            <span>Premium</span>
        </a>
    </nav>

    <script>
        // Add hover effects to cards that are not part of the auto-scroll
        document.querySelectorAll('.schedule-card, .feature-item').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // For the scrolling crypto cards, the hover effect is handled by pausing the animation via CSS.
        // We can still add a scale effect for a bit more interactivity.
        document.querySelectorAll('.crypto-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.zIndex = '10';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.zIndex = '1';
            });
        });

        // Task Completion on Home Page
        const userId = <?php echo json_encode($user ? $user['id'] : null); ?>;
        const miningEndTime = <?php echo json_encode($mining_session ? $mining_session['end_time'] : null); ?>;
        const miningStatus = <?php echo json_encode($mining_session ? $mining_session['status'] : null); ?>;
        const miningReward = <?php echo json_encode($mining_session ? $mining_session['reward'] : null); ?>;

        function updateMiningTimer(){
            const timerEl = document.getElementById('main-timer');
            const circleEl = document.getElementById('main-circle');
            if (!timerEl) return;
            if (!miningEndTime || miningStatus !== 'active') {
                timerEl.textContent = miningStatus === 'completed' ? 'Ready to Claim!' : '--:--:--';
                if (circleEl) circleEl.classList.toggle('active', miningStatus === 'active');
                return;
            }
            const now = Date.now();
            const distance = new Date(miningEndTime).getTime() - now;
            if (distance <= 0) {
                timerEl.textContent = 'Ready to Claim!';
                if (circleEl) circleEl.classList.remove('active');
                // When 24h is over, reload once to show the Claim button
                setTimeout(() => location.reload(), 800);
                return;
            }
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            timerEl.textContent =
                (hours < 10 ? '0' + hours : hours) + ':' +
                (minutes < 10 ? '0' + minutes : minutes) + ':' +
                (seconds < 10 ? '0' + seconds : seconds);
            if (circleEl) circleEl.classList.add('active');
        }

        function updateLiveCounter(){
            const counterEl = document.getElementById('live-counter');
            if (!counterEl) return;
            if (!miningEndTime || miningStatus !== 'active') {
                counterEl.textContent = '0.000000';
                return;
            }
            const startTime = new Date(miningEndTime).getTime() - (24 * 60 * 60 * 1000);
            const now = Date.now();
            const elapsed = Math.max(0, Math.min(now - startTime, 24 * 60 * 60 * 1000));
            const earned = ((parseFloat(miningReward || 0)) * (elapsed / (24 * 60 * 60 * 1000)));
            counterEl.textContent = earned.toFixed(6);
        }

        async function startMining() {
            if (!userId) return;
            const btn = document.querySelector('#mining-action-container button');
            if (btn) btn.disabled = true;
            try {
                const res = await fetch('mining_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, action: 'start'})
                });
                const data = await res.json();
                if (data && data.success) {
                    location.reload();
                    return;
                }
                alert((data && data.message) ? data.message : 'Failed to start mining');
            } catch (e) {
                console.error(e);
                alert('Network error.');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        async function claimReward() {
            if (!userId) return;
            const btn = document.querySelector('#mining-action-container button');
            if (btn) btn.disabled = true;
            try {
                const res = await fetch('mining_action.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({user_id: userId, action: 'claim'})
                });
                const data = await res.json();
                if (data && data.success) {
                    alert(data.message || 'Reward claimed');
                    location.reload();
                    return;
                }
                alert((data && data.message) ? data.message : 'No reward to claim yet');
            } catch (e) {
                console.error(e);
                alert('Network error.');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        setInterval(() => {
            updateMiningTimer();
            updateLiveCounter();
        }, 1000);
        updateMiningTimer();
        updateLiveCounter();

        const claimButtons = document.querySelectorAll('.claim-btn');
        claimButtons.forEach(button => {
            button.addEventListener('click', async (event) => {
                event.preventDefault(); // Prevent default link behavior


                if (!userId) {
                    alert('User not identified.');
                    return;
                }

                const taskId = button.dataset.taskId;
                const taskLink = button.href;
                const originalHtml = button.innerHTML;

                // Open the link in a new tab immediately
                window.open(taskLink, '_blank');

                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                try {
                    const response = await fetch('complete_task.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId, task_id: taskId })
                    });
                    const result = await response.json();

                    if (result.success) {
                        button.innerHTML = 'Done';
                        button.classList.add('completed');
                        const balanceElement = document.getElementById('user-balance');
                        if (balanceElement && result.new_balance) {
                            balanceElement.textContent = parseFloat(result.new_balance).toFixed(2);
                        }
                    } else {
                        alert(result.message);
                        button.disabled = false;
                        button.innerHTML = originalHtml;
                    }
                } catch (error) {
                    alert('An error occurred while completing the task.');
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                }
            });
        });

        // Daily Check-in
        const checkinBtn = document.getElementById('checkin-btn');
        if (checkinBtn) {
            checkinBtn.addEventListener('click', async () => {
                if (!userId) {
                    alert('User ID not found. Please access through the bot.');
                    return;
                }

                const originalText = checkinBtn.textContent;
                checkinBtn.disabled = true;
                checkinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Claiming...';

                try {
                    const response = await fetch('complete_task.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            task_id: 'daily_checkin'
                        })
                    });

                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }

                    const data = await response.json();

                    if (data.success) {
                        checkinBtn.textContent = 'Claimed Today';
                        checkinBtn.disabled = true;
                        
                        // Update balance on the page
                        const balanceElement = document.querySelector('.balance-amount');
                        if (balanceElement && data.new_balance !== undefined) {
                            balanceElement.textContent = parseFloat(data.new_balance).toFixed(2) + ' PCN';
                        }
                    } else {
                        checkinBtn.textContent = originalText;
                        checkinBtn.disabled = false;
                        
                        if (window.Telegram && window.Telegram.WebApp) {
                            window.Telegram.WebApp.showPopup({
                                title: 'Error',
                                message: data.message || 'Failed to claim daily bonus',
                                buttons: [{ type: 'ok' }]
                            });
                        } else {
                            alert(data.message || 'Failed to claim daily bonus');
                        }
                    }
                } catch (error) {
                    console.error('Check-in error:', error);
                    checkinBtn.textContent = originalText;
                    checkinBtn.disabled = false;
                    
                    const errorMessage = 'Could not connect to the server. Please try again.';
                    if (window.Telegram && window.Telegram.WebApp) {
                        window.Telegram.WebApp.showPopup({
                            title: 'Error',
                            message: errorMessage,
                            buttons: [{ type: 'ok' }]
                        });
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        }
    </script>
<script>
    function navigateToUpgrade(userId) {
        const cacheBuster = `&t=${new Date().getTime()}`;
        window.location.href = `payment.php?user_id=${userId}${cacheBuster}`;
    }
</script>
</body>
</html> 