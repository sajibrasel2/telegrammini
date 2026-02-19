<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once __DIR__ . '/telegram_gate.php';
require_once 'config.php';
require_once 'database.php';

$db = new Database();
$user = null;
$user_id = null;
$is_paid_user = false;

// Get user data if user_id is present
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $user = $db->getUser($user_id);
    if ($user) {
        $is_paid_user = ($user['subscription_type'] === 'paid');
    }
}

// Admin-specific data
$pending_payments = [];
if ($user && $user['is_admin']) {
    $pending_payments = $db->getPendingPayments();
}

$user_id_query = $user_id ? '?user_id=' . htmlspecialchars($user_id) . '&t=' . time() : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Upgrade to Premium - PCN Coin</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'app_style.php'; ?>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        window.addEventListener('load', function() {
            const tg = window.Telegram.WebApp;
            tg.ready();
            tg.expand();

            const params = new URLSearchParams(window.location.search);
            const userIdFromUrl = params.get('user_id');

            if (!userIdFromUrl && tg.initDataUnsafe && tg.initDataUnsafe.user) {
                const userId = tg.initDataUnsafe.user.id;
                if (userId) {
                    window.location.href = `${window.location.pathname}?user_id=${userId}&t=${new Date().getTime()}${window.location.hash}`;
                }
            }
        });
    </script>
    <style>
        :root {
            --bg-gradient-start: #1a202c;
            --bg-gradient-end: #2d3748;
            --card-bg: rgba(45, 55, 72, 0.7);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-primary: #e2e8f0;
            --text-secondary: #a0aec0;
            --accent-gradient-start: #38b2ac;
            --accent-gradient-end: #319795;
            --ton-blue: #0088cc;
            --success: #38a169;
            --error: #c53030;
            --font-family: 'Poppins', sans-serif;
        }

        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            color: var(--text-primary);
            margin: 0;
            padding: 20px 15px 100px 15px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .container {
            max-width: 500px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        header {
            text-align: center;
            padding: 10px 0;
        }

        header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            background: -webkit-linear-gradient(45deg, var(--accent-gradient-start), var(--accent-gradient-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0 0 5px 0;
        }

        header p {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .card h2 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 15px 0;
            text-align: center;
            color: var(--text-primary);
            border-bottom: 1px solid var(--card-border);
            padding-bottom: 10px;
        }

        .benefit-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .benefit-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }

        .benefit-list .icon {
            font-size: 1rem;
            color: var(--accent-gradient-start);
            width: 24px;
            text-align: center;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 12px;
            bottom: 12px;
            width: 2px;
            background: var(--card-border);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-item:last-child { margin-bottom: 0; }

        .timeline-icon {
            position: absolute;
            left: -30px;
            top: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--accent-gradient-end);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            border: 2px solid var(--bg-gradient-end);
        }

        .timeline-content p {
            margin: 0 0 8px 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .wallet-address {
            display: flex;
            gap: 10px;
            background-color: var(--bg-gradient-start);
            border-radius: 12px;
            padding: 10px;
            align-items: center;
            border: 1px solid var(--card-border);
        }

        .wallet-address span {
            flex-grow: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-family: monospace;
            font-size: 0.85rem;
        }

        .copy-btn {
            background: var(--accent-gradient-start);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 6px 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .copy-btn:hover { transform: scale(1.05); }

        .input-group {
            margin-bottom: 15px;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            background-color: var(--bg-gradient-start);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 0.9rem;
            box-sizing: border-box;
        }
        .input-group input::placeholder {
            color: var(--text-secondary);
        }

        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn:hover { transform: translateY(-2px); }

        .btn-submit {
            background: linear-gradient(45deg, var(--success), #48bb78);
            color: white;
        }

        .btn-ton {
            margin-top: 10px;
            background: linear-gradient(45deg, var(--ton-blue), #00aaff);
            color: white;
        }

        .message {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 0.9rem;
        }
        .message.success { background-color: rgba(56, 161, 105, 0.2); color: #68d391; border: 1px solid #38a169; }
        .message.error { background-color: rgba(197, 48, 48, 0.2); color: #fc8181; border: 1px solid #c53030; }

        /* Admin Section */
        .admin-section table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        .admin-section th, .admin-section td { padding: 8px; text-align: left; border-bottom: 1px solid var(--card-border); }
        .admin-section th { font-weight: 600; }
        .admin-section .action-btn { padding: 5px 8px; border-radius: 6px; cursor: pointer; color: white; margin-right: 5px; border: none; }
        .admin-section .approve-btn { background-color: var(--success); }
        .admin-section .reject-btn { background-color: var(--error); }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--card-bg);
            padding: 10px 0;
            z-index: 1000;
            border-top: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
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
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            flex: 1;
        }
        .nav-item i { font-size: 1.4rem; margin-bottom: 4px; }
        .nav-item.active, .nav-item:hover {
            color: var(--accent-gradient-start);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="app-header">
            <h1><i class="fas fa-crown"></i> Premium Access</h1>
            <p style="color: var(--text-dim);">Unlock 2X mining and referral bonuses</p>
        </div>

        <?php if (!$user_id): ?>
            <div class="app-card">
                <p style="text-align:center; color: var(--text-dim);">Loading user data...</p>
            </div>
        <?php elseif ($is_paid_user): ?>
            <div class="app-card" style="text-align: center; border-bottom: 3px solid var(--primary);">
                <i class="fas fa-star fa-3x" style="color: var(--accent); margin-bottom: 15px;"></i>
                <h2 style="color: var(--accent);">Premium Member</h2>
                <p style="color: var(--text-dim); margin-top: 10px;">You have active premium access. Thank you for your support!</p>
            </div>
        <?php else: ?>
            <div class="app-card">
                <h3 style="font-size: 1.1rem; margin-bottom: 20px; color: var(--accent);"><i class="fas fa-gem"></i> Premium Benefits</h3>
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-coins" style="color: var(--primary); margin-top: 4px;"></i>
                        <div>
                            <h4 style="font-size: 0.95rem;">High Referral Rewards</h4>
                            <p style="font-size: 0.8rem; color: var(--text-dim);">Earn 10 PCN per referral (Standard: 3 PCN)</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-sitemap" style="color: var(--primary); margin-top: 4px;"></i>
                        <div>
                            <h4 style="font-size: 0.95rem;">10-Level Network Bonus</h4>
                            <p style="font-size: 0.8rem; color: var(--text-dim);">Earn from your entire network up to 10 levels</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-bolt" style="color: var(--primary); margin-top: 4px;"></i>
                        <div>
                            <h4 style="font-size: 0.95rem;">2X Mining Speed</h4>
                            <p style="font-size: 0.8rem; color: var(--text-dim);">Earn 50 PCN per 24h mining session</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-card" style="padding: 10px; background: rgba(255,255,255,0.02); display: flex; gap: 10px; margin-bottom: 20px; border-radius: 15px;">
                <button onclick="switchMethod('ton')" id="btn-method-ton" class="app-btn" style="flex: 1; padding: 10px; font-size: 0.85rem; background: var(--primary); color: white; border-radius: 12px;">
                    <i class="fas fa-gem"></i> TON
                </button>
                <button onclick="switchMethod('usdt')" id="btn-method-usdt" class="app-btn" style="flex: 1; padding: 10px; font-size: 0.85rem; background: rgba(255,255,255,0.05); color: var(--text-dim); border-radius: 12px;">
                    <i class="fas fa-dollar-sign"></i> USDT (TRC20)
                </button>
            </div>

            <div id="method-ton" class="payment-method">
                <div class="app-card">
                    <h3 style="font-size: 1.1rem; margin-bottom: 20px; text-align: center;"><i class="fas fa-wallet"></i> Upgrade (0.50 TON)</h3>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="image/3.jpg" alt="Payment QR Code" style="max-width: 180px; border-radius: 15px; border: 3px solid var(--primary); padding: 5px; background: white;">
                        <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 10px;">Scan QR code to pay via TON</p>
                    </div>
                    <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 15px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05);">
                        <p style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 10px;">Send 0.50 TON to this address:</p>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span id="wallet-addr-ton" style="flex: 1; font-family: monospace; font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">UQByJO2ANNla0RjAUTrXEKc25lC2HyS30TbnoauAQKEv4CVp</span>
                            <button class="app-btn" onclick="copyToClipboard('#wallet-addr-ton', this)" style="width: auto; padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: var(--primary); color: white;">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <a href="#" onclick="openTonWallet(event)" class="app-btn btn-primary" style="background: #0088cc;">
                        <i class="fab fa-telegram-plane" style="margin-right: 10px;"></i> Pay with TON Wallet
                    </a>
                </div>
            </div>

            <div id="method-usdt" class="payment-method" style="display: none;">
                <div class="app-card">
                    <h3 style="font-size: 1.1rem; margin-bottom: 20px; text-align: center;"><i class="fas fa-dollar-sign"></i> Upgrade (0.50 TON equivalent in USDT)</h3>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <img src="image/tron.jpg" alt="USDT TRC20 QR Code" style="max-width: 180px; border-radius: 15px; border: 3px solid #26A17B; padding: 5px; background: white;">
                        <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 10px;">Scan QR code for USDT (TRC20)</p>
                    </div>
                    <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 15px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.05);">
                        <p style="font-size: 0.8rem; color: var(--text-dim); margin-bottom: 10px;">Send USDT (TRC20) to this address:</p>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span id="wallet-addr-usdt" style="flex: 1; font-family: monospace; font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">TM9sy4ST8yFBrsy7isNtkQ4rXk4zc8WcBA</span>
                            <button class="app-btn" onclick="copyToClipboard('#wallet-addr-usdt', this)" style="width: auto; padding: 8px 12px; font-size: 0.8rem; border-radius: 8px; background: #26A17B; color: white;">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <p style="font-size: 0.75rem; color: #ffbc00; text-align: center; margin-bottom: 10px;">
                        <i class="fas fa-exclamation-triangle"></i> Ensure you use <b>TRON (TRC20)</b> network only.
                    </p>
                </div>
            </div>

            <div class="app-card">
                <h3 style="font-size: 1rem; margin-bottom: 15px; text-align: center;"><i class="fas fa-check-circle"></i> Verification</h3>
                <form id="payment-form">
                    <div id="form-message" class="message" style="display:none; margin-bottom: 15px; padding: 10px; border-radius: 10px; font-size: 0.85rem; text-align: center;"></div>
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                    <input type="text" id="transaction_id" name="transaction_id" placeholder="Paste Transaction Hash (ID) here" required 
                           style="width: 100%; padding: 12px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; color: white; margin-bottom: 15px; font-size: 0.9rem;">
                    <button type="submit" class="app-btn btn-primary">Submit for Review</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($user && $user['is_admin'] && !empty($pending_payments)): ?>
            <div class="app-card">
                <h3 style="font-size: 1rem; margin-bottom: 15px;"><i class="fas fa-user-shield"></i> Admin: Pending</h3>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($pending_payments as $payment): ?>
                        <div id="payment-row-<?php echo $payment['id']; ?>" style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <div style="font-size: 0.8rem; margin-bottom: 8px;">
                                <strong style="color: var(--primary);">User:</strong> <?php echo htmlspecialchars($payment['username'] ?: $payment['user_id']); ?>
                            </div>
                            <div style="font-size: 0.7rem; color: var(--text-dim); margin-bottom: 10px; word-break: break-all;">
                                <strong>TX:</strong> <?php echo htmlspecialchars($payment['transaction_id']); ?>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <button class="app-btn" onclick="processPayment(<?php echo $payment['id']; ?>, 'approve')" style="padding: 8px; background: var(--success); font-size: 0.8rem; color: white;">Approve</button>
                                <button class="app-btn" onclick="processPayment(<?php echo $payment['id']; ?>, 'reject')" style="padding: 8px; background: var(--error); font-size: 0.8rem; color: white;">Reject</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
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
        <a href="payment.php<?php echo $user_id_query; ?>" class="nav-item active">
            <i class="fas fa-crown"></i>
            <span>Premium</span>
        </a>
    </nav>

    <script>
        function switchMethod(method) {
            const tonBtn = document.getElementById('btn-method-ton');
            const usdtBtn = document.getElementById('btn-method-usdt');
            const tonSection = document.getElementById('method-ton');
            const usdtSection = document.getElementById('method-usdt');

            if (method === 'ton') {
                tonBtn.style.background = 'var(--primary)';
                tonBtn.style.color = 'white';
                usdtBtn.style.background = 'rgba(255,255,255,0.05)';
                usdtBtn.style.color = 'var(--text-dim)';
                tonSection.style.display = 'block';
                usdtSection.style.display = 'none';
            } else {
                usdtBtn.style.background = '#26A17B';
                usdtBtn.style.color = 'white';
                tonBtn.style.background = 'rgba(255,255,255,0.05)';
                tonBtn.style.color = 'var(--text-dim)';
                usdtSection.style.display = 'block';
                tonSection.style.display = 'none';
            }
        }

        function copyToClipboard(elementId, button) {
            const text = document.querySelector(elementId).textContent;
            navigator.clipboard.writeText(text).then(() => {
                const originalHtml = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => { button.innerHTML = originalHtml; }, 2000);
            }).catch(err => console.error('Failed to copy: ', err));
        }

        document.getElementById('payment-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('form-message');
            const submitBtn = form.querySelector('.btn-submit');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            fetch('submit_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.style.display = 'block';
                messageDiv.className = `message ${data.status}`;
                messageDiv.textContent = data.message;
                if (data.status === 'success') {
                    form.reset();
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Submitted!';
                    setTimeout(() => { 
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                     }, 3000);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageDiv.style.display = 'block';
                messageDiv.className = 'message error';
                messageDiv.textContent = 'An unexpected error occurred.';
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });

        function openTonWallet(event) {
            event.preventDefault();
            // Use a universal link for Tonkeeper which is more reliable
            const tonUrl = 'https://app.tonkeeper.com/transfer/UQDIaftWRzxlGJMPOdYa3cS-_2t7hzEVBASrGEqQ4oJHVK-Z?amount=500000000';
            const tg = window.Telegram.WebApp;

            // Use the standard openLink API for https links
            tg.openLink(tonUrl);
        }

        function processPayment(paymentId, action) {
            const endpoint = action === 'approve' ? 'approve_payment.php' : 'reject_payment.php';
            
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ payment_id: paymentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Use a more modern notification if available, otherwise alert
                    if (window.Telegram && window.Telegram.WebApp) {
                        window.Telegram.WebApp.showAlert(data.message);
                    } else {
                        alert(data.message);
                    }
                    // Remove the processed row from the table
                    const row = document.getElementById(`payment-row-${paymentId}`);
                    if (row) {
                        row.style.transition = 'opacity 0.5s ease';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 500);
                    }
                } else {
                    if (window.Telegram && window.Telegram.WebApp) {
                        window.Telegram.WebApp.showAlert('Error: ' + data.message);
                    } else {
                        alert('Error: ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (window.Telegram && window.Telegram.WebApp) {
                    window.Telegram.WebApp.showAlert('An unexpected network error occurred.');
                } else {
                    alert('An unexpected network error occurred.');
                }
            });
        }
    </script>
</body>
</html>