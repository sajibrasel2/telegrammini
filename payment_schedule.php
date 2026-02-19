<?php
require_once __DIR__ . '/telegram_gate.php';
require_once 'config.php';
require_once 'database.php';

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
    }
    $user_id_query = '?user_id=' . $userId;
}

// Payment schedule data
$paymentSchedule = [
    [
        'phase' => 'Phase 1',
        'date' => '01/01/2026',
        'percentage' => '65%',
        'status' => 'active',
        'description' => 'Initial Listing Payment',
        'details' => 'First major payment release when PCN Coin gets listed on major exchanges',
        'benefits' => ['Exchange Listing', 'Market Launch', 'Initial Liquidity', 'Community Building']
    ],
    [
        'phase' => 'Phase 2',
        'date' => '01/04/2026',
        'percentage' => '20%',
        'status' => 'upcoming',
        'description' => 'Second Quarter Release',
        'details' => 'Second payment phase after successful Q1 performance and market stabilization',
        'benefits' => ['Market Stabilization', 'Partnership Development', 'Platform Expansion', 'User Growth']
    ],
    [
        'phase' => 'Phase 3',
        'date' => '01/07/2026',
        'percentage' => '10%',
        'status' => 'upcoming',
        'description' => 'Third Quarter Release',
        'details' => 'Third payment phase focusing on ecosystem development and advanced features',
        'benefits' => ['Ecosystem Development', 'Advanced Features', 'DeFi Integration', 'Cross-chain Bridge']
    ],
    [
        'phase' => 'Phase 4',
        'date' => '01/10/2026',
        'percentage' => '5%',
        'status' => 'upcoming',
        'description' => 'Final Release',
        'details' => 'Final payment phase completing the full PCN Coin ecosystem and governance',
        'benefits' => ['Full Ecosystem', 'DAO Governance', 'Complete Integration', 'Long-term Vision']
    ]
];

// Calculate total days until July 1st, 2026
$targetPaymentDate = new DateTime('2026-07-01');
$currentDate = new DateTime();
$interval = $currentDate->diff($targetPaymentDate);
$daysUntilPayment = $interval->invert ? 0 : $interval->days;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Coin - Payment Schedule</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'app_style.php'; ?>
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
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 3rem;
            margin-bottom: 15px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradientShift 3s ease-in-out infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .countdown-section {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .countdown-section h2 {
            color: #4ecdc4;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        
        .countdown-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .countdown-item {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        
        .countdown-item .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2ecc71;
            margin-bottom: 5px;
        }
        
        .countdown-item .label {
            font-size: 0.9rem;
            color: #bdc3c7;
        }
        
        .schedule-section {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }
        
        .schedule-section h2 {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            font-size: 2.2rem;
            text-shadow: 0 0 15px rgba(255,255,255,0.6);
        }
        
        .schedule-timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .schedule-timeline::before {
            content: '';
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 3px;
            background: rgba(255,255,255,0.15);
            transform: translateX(-50%);
            border-radius: 3px;
        }
        
        .schedule-item {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .schedule-item:nth-child(odd) {
            flex-direction: row;
        }
        
        .schedule-item:nth-child(even) {
            flex-direction: row-reverse;
        }
        
        .schedule-content {
            flex: 1;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 15px;
            padding: 25px;
            margin: 0 20px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .schedule-content:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .schedule-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            z-index: 10;
            border: 3px solid rgba(255,255,255,0.2);
            flex-shrink: 0;
        }
        
        .schedule-item:nth-child(1) .schedule-icon {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }
        
        .schedule-item:nth-child(2) .schedule-icon {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .schedule-item:nth-child(3) .schedule-icon {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }
        
        .schedule-item:nth-child(4) .schedule-icon {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        
        .phase-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .phase-title {
            font-size: 1.6rem;
            font-weight: bold;
        }
        
        .phase-status {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .phase-status.active {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }
        
        .phase-status.upcoming {
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .phase-date {
            font-size: 0.9rem;
            color: #bdc3c7;
            margin-bottom: 10px;
        }
        
        .phase-description {
            color: #bdc3c7;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .phase-benefits {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .benefit-tag {
            background: rgba(78, 205, 196, 0.2);
            color: #4ecdc4;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            border: 1px solid rgba(78, 205, 196, 0.3);
        }
        
        .info-section {
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .info-section h2 {
            text-align: center;
            color: white;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
        }
        
        .info-card i {
            font-size: 2.5rem;
            color: #4ecdc4;
            margin-bottom: 15px;
        }
        
        .info-card h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: white;
        }
        
        .info-card p {
            color: #bdc3c7;
            line-height: 1.5;
        }
        
        .cta-section {
            text-align: center;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .cta-section h2 {
            color: white;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        
        .cta-section p {
            color: #ecf0f1;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, #4ecdc4 0%, #2ecc71 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 1.2rem;
            font-weight: bold;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(78, 205, 196, 0.4);
        }
        
        .btn i {
            margin-right: 10px;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 15, 35, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 10px 0;
            z-index: 1000;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-around;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #bdc3c7;
            transition: all 0.3s ease;
            flex: 1;
        }
        
        .nav-item.active {
            color: #4ecdc4;
        }
        
        .nav-item i {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .nav-item span {
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        @media (max-width: 768px) {
            .schedule-timeline::before {
                left: 20px;
            }
            
            .schedule-item {
                flex-direction: column !important;
                text-align: center;
            }
            
            .schedule-content {
                margin: 20px 0 0 0;
            }
            
            .schedule-icon {
                margin-bottom: 20px;
            }
            
            .countdown-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="app-header">
            <h1><i class="fas fa-calendar-alt"></i> Payment Schedule</h1>
            <p style="color: var(--text-dim);">Phase-wise Distribution Plan</p>
        </div>
        
        <!-- Countdown Section -->
        <div class="app-card" style="text-align: center; border-bottom: 3px solid var(--primary);">
            <h2 style="color: var(--accent); font-size: 1.1rem; margin-bottom: 20px;"><i class="fas fa-clock"></i> Next Phase Countdown</h2>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px;">
                <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px;">
                    <div id="days" style="font-size: 1.5rem; font-weight: bold; color: var(--primary);"><?php echo $daysUntilPayment; ?></div>
                    <div style="font-size: 0.6rem; color: var(--text-dim); text-transform: uppercase;">Days</div>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px;">
                    <div id="hours" style="font-size: 1.5rem; font-weight: bold; color: var(--primary);">00</div>
                    <div style="font-size: 0.6rem; color: var(--text-dim); text-transform: uppercase;">Hrs</div>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px;">
                    <div id="minutes" style="font-size: 1.5rem; font-weight: bold; color: var(--primary);">00</div>
                    <div style="font-size: 0.6rem; color: var(--text-dim); text-transform: uppercase;">Min</div>
                </div>
                <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 12px;">
                    <div id="seconds" style="font-size: 1.5rem; font-weight: bold; color: var(--primary);">00</div>
                    <div style="font-size: 0.6rem; color: var(--text-dim); text-transform: uppercase;">Sec</div>
                </div>
            </div>
            <p style="color: var(--primary); font-size: 0.9rem; font-weight: 500;">
                Phase 3 Release: July 1st, 2026
            </p>
        </div>

        <!-- Price Prediction Card -->
        <div class="app-card" style="background: linear-gradient(135deg, rgba(254, 202, 87, 0.1) 0%, rgba(15, 15, 35, 0) 100%); border: 1px solid rgba(254, 202, 87, 0.2);">
            <h3 style="color: var(--accent); font-size: 1rem; margin-bottom: 15px; text-align: center;"><i class="fas fa-chart-line"></i> Price Prediction</h3>
            <div style="display: flex; justify-content: space-around; align-items: center;">
                <div style="text-align: center;">
                    <div style="font-size: 0.7rem; color: var(--text-dim); margin-bottom: 5px;">MINIMUM</div>
                    <div style="font-size: 1.2rem; font-weight: bold; color: var(--success);">$0.10</div>
                </div>
                <i class="fas fa-arrow-right" style="opacity: 0.3;"></i>
                <div style="text-align: center;">
                    <div style="font-size: 0.7rem; color: var(--text-dim); margin-bottom: 5px;">POTENTIAL</div>
                    <div style="font-size: 1.2rem; font-weight: bold; color: var(--success);">$1.40</div>
                </div>
            </div>
        </div>
        
        <!-- Timeline Section -->
        <div style="margin-bottom: 25px;">
            <h3 style="margin-bottom: 15px; font-size: 1.1rem; padding-left: 5px;"><i class="fas fa-list-ol"></i> Distribution Phases</h3>
            <?php foreach ($paymentSchedule as $item): ?>
                <div class="app-card" style="padding: 15px; margin-bottom: 12px; border-left: 4px solid <?php echo ($item['status'] === 'active' ? 'var(--success)' : 'rgba(255,255,255,0.1)'); ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                        <span style="font-weight: bold; font-size: 0.95rem; color: var(--primary);"><?php echo $item['phase']; ?> (<?php echo $item['percentage']; ?>)</span>
                        <span style="font-size: 0.7rem; padding: 3px 8px; border-radius: 8px; background: <?php echo ($item['status'] === 'active' ? 'rgba(46, 204, 113, 0.2)' : 'rgba(255,255,255,0.05)'); ?>; color: <?php echo ($item['status'] === 'active' ? 'var(--success)' : 'var(--text-dim)'); ?>; text-transform: uppercase; font-weight: bold;">
                            <?php echo $item['status']; ?>
                        </span>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 10px;">
                        <i class="fas fa-calendar-day"></i> <?php echo $item['date']; ?>
                    </div>
                    <p style="font-size: 0.85rem; line-height: 1.4; opacity: 0.9;">
                        <?php echo $item['description']; ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php<?php echo $user_id_query; ?>" class="nav-item">
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
        // Countdown timer
        function updateCountdown() {
            const targetDate = new Date('2026-07-01T00:00:00').getTime();
            const now = new Date().getTime();
            const distance = targetDate - now;
            
            if (distance < 0) {
                document.getElementById('days').textContent = '0';
                document.getElementById('hours').textContent = '00';
                document.getElementById('minutes').textContent = '00';
                document.getElementById('seconds').textContent = '00';
                return;
            }
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('days').textContent = days;
            document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
        }
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown();
        
        // Add animation to schedule items
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.schedule-item').forEach(item => {
            item.style.opacity = '0';
            item.style.transform = 'translateY(30px)';
            item.style.transition = 'all 0.6s ease';
            observer.observe(item);
        });
    </script>
</body>
</html> 