<?php
require_once __DIR__ . '/telegram_gate.php';
require_once 'config.php';
require_once 'database.php';

$db = new Database();
$user_id = $_GET['user_id'] ?? null;
$user = null;
$mining_session = null;

if ($user_id) {
    $user = $db->getUser($user_id);
    $mining_session = $db->getActiveMiningSession($user_id);
}

$user_id_query = $user_id ? '?user_id=' . htmlspecialchars($user_id) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mining - PCN Coin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4ecdc4;
            --bg: #0f0f23;
            --card-bg: rgba(255, 255, 255, 0.1);
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%);
            color: white;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .header { margin-bottom: 30px; }
        .header h1 { color: var(--primary); font-size: 2rem; margin-bottom: 5px; }
        
        /* Mining Animation */
        .mining-circle {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            border: 10px solid var(--card-bg);
            border-top: 10px solid var(--primary);
            margin: 40px auto;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }
        .mining-circle.active {
            animation: rotate 2s linear infinite;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .mining-circle i {
            font-size: 4rem;
            color: var(--primary);
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .timer { font-size: 1.5rem; font-weight: bold; margin: 20px 0; }
        .reward-info { background: var(--card-bg); padding: 15px; border-radius: 15px; margin-bottom: 20px; }
        
        .btn {
            background: linear-gradient(135deg, #4ecdc4, #45b7af);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 80%;
        }
        .btn:disabled { background: #555; cursor: not-allowed; }
        .btn-claim { background: linear-gradient(135deg, #f39c12, #e67e22); }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: rgba(15, 15, 35, 0.95);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
            z-index: 1000;
            border-top: 1px solid var(--card-bg);
        }
        .nav-container {
            display: flex;
            justify-content: space-around;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        .nav-item {
            color: #bdc3c7;
            text-decoration: none;
            font-size: 0.8rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        .nav-item.active { color: var(--primary); }
        .nav-item i { font-size: 1.5rem; margin-bottom: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-hammer"></i> PCN Mining</h1>
            <p>Earn coins every 24 hours</p>
        </div>

        <div class="mining-circle <?php echo ($mining_session && $mining_session['status'] === 'active') ? 'active' : ''; ?>" id="circle">
            <i class="fas fa-gem"></i>
        </div>

        <div class="timer" id="timer">--:--:--</div>

        <div class="reward-info">
            <p>Your Rate: <span id="rate"><?php echo ($user && $user['subscription_type'] === 'paid') ? '50' : '25'; ?></span> PCN / 24h</p>
        </div>

        <div id="action-container">
            <?php if (!$mining_session): ?>
                <button class="btn" onclick="startMining()">Start Mining</button>
            <?php elseif ($mining_session['status'] === 'active'): ?>
                <button class="btn" disabled>Mining...</button>
            <?php elseif ($mining_session['status'] === 'completed'): ?>
                <button class="btn btn-claim" onclick="claimReward()">Claim <?php echo $mining_session['reward']; ?> PCN</button>
            <?php endif; ?>
        </div>
    </div>

    <nav class="bottom-nav">
        <div class="nav-container">
            <a href="index.php<?php echo $user_id_query; ?>" class="nav-item">
                <i class="fas fa-home"></i><span>Home</span>
            </a>
            <a href="tasks.php<?php echo $user_id_query; ?>" class="nav-item">
                <i class="fas fa-tasks"></i><span>Tasks</span>
            </a>
            <a href="referral.php<?php echo $user_id_query; ?>" class="nav-item">
                <i class="fas fa-share-alt"></i><span>Referral</span>
            </a>
            <a href="payment.php<?php echo $user_id_query; ?>" class="nav-item">
                <i class="fas fa-crown"></i><span>Premium</span>
            </a>
        </div>
    </nav>

    <script>
        const userId = <?php echo json_encode($user_id); ?>;
        let endTime = <?php echo json_encode($mining_session ? $mining_session['end_time'] : null); ?>;
        
        function updateTimer() {
            if (!endTime) return;
            const now = new Date().getTime();
            const distance = new Date(endTime).getTime() - now;
            
            if (distance < 0) {
                document.getElementById('timer').innerHTML = "Ready to Claim!";
                document.getElementById('circle').classList.remove('active');
                // Refresh if it was active
                return;
            }

            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('timer').innerHTML = 
                (hours < 10 ? "0" + hours : hours) + ":" + 
                (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                (seconds < 10 ? "0" + seconds : seconds);
        }

        setInterval(updateTimer, 1000);
        updateTimer();

        async function startMining() {
            const res = await fetch('mining_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({user_id: userId, action: 'start'})
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        }

        async function claimReward() {
            const res = await fetch('mining_action.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({user_id: userId, action: 'claim'})
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        }
    </script>
</body>
</html>
