<?php
require_once 'config.php';
require_once 'database.php';
require_once 'bot.php';

$db = new Database();

// Handle admin actions
$action = $_GET['action'] ?? '';
$payment_id = $_GET['payment_id'] ?? '';
$message = '';

if ($action === 'approve' && !empty($payment_id)) {
    $userId = $db->approvePayment($payment_id);
    if ($userId) {
        // Send notification to user via bot
        $bot = new PCNCoinBot();
        $notificationMessage = "🎉 <b>Payment Approved!</b>\n\n";
        $notificationMessage .= "✅ Your payment has been verified and approved!\n";
        $notificationMessage .= "📋 Payment ID: <code>$payment_id</code>\n\n";
        $notificationMessage .= "💰 <b>Your Account Has Been Upgraded!</b>\n";
        $notificationMessage .= "• ✅ Paid Plan activated\n";
        $notificationMessage .= "• 🎁 500 PCN bonus credited\n";
        $notificationMessage .= "• 🔗 10 PCN per referral (vs 3 for free)\n";
        $notificationMessage .= "• 🌟 10-generation multi-level system\n";
        $notificationMessage .= "• ⭐ Priority support\n\n";
        $notificationMessage .= "🚀 <b>Start earning more PCN coins now!</b>\n";
        $notificationMessage .= "Use /referral to get your premium referral link!";
        
        $bot->sendMessage($userId, $notificationMessage);
        $message = "Payment {$payment_id} approved successfully! User notified.";
    } else {
        $message = "Error approving payment {$payment_id}";
    }
} elseif ($action === 'reject' && !empty($payment_id)) {
    $reason = $_GET['reason'] ?? 'No reason provided';
    if ($db->rejectPayment($payment_id, $reason)) {
        $message = "Payment {$payment_id} rejected successfully!";
    } else {
        $message = "Error rejecting payment {$payment_id}";
    }
}

// Get all payments
$all_payments = $db->getAllPayments();
$pending_payments = $db->getPendingPayments();
$approved_payments = $db->getApprovedPayments();
$rejected_payments = $db->getRejectedPayments();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Coin - Admin Payment Management</title>
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
            max-width: 1400px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card.pending i { color: #f39c12; }
        .stat-card.approved i { color: #2ecc71; }
        .stat-card.rejected i { color: #e74c3c; }
        .stat-card.total i { color: #3498db; }
        
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #ecf0f1;
            font-size: 1rem;
        }
        
        .message-box {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message-box.error {
            background: rgba(231, 76, 60, 0.2);
            border-color: #e74c3c;
        }
        
        .payments-section {
            background: rgba(52, 73, 94, 0.8);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .payments-section h2 {
            text-align: center;
            color: white;
            margin-bottom: 25px;
            font-size: 2rem;
        }
        
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(44, 62, 80, 0.5);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .payments-table th, .payments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .payments-table th {
            background: rgba(44, 62, 80, 0.8);
            font-weight: bold;
            color: #3498db;
        }
        
        .payments-table tr:hover {
            background: rgba(44, 62, 80, 0.3);
        }
        
        .status-pending {
            color: #f39c12;
            font-weight: bold;
        }
        
        .status-approved {
            color: #2ecc71;
            font-weight: bold;
        }
        
        .status-rejected {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .action-btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-size: 0.9rem;
            margin: 2px;
            transition: all 0.3s ease;
        }
        
        .action-btn.approve {
            background: #2ecc71;
        }
        
        .action-btn.approve:hover {
            background: #27ae60;
        }
        
        .action-btn.reject {
            background: #e74c3c;
        }
        
        .action-btn.reject:hover {
            background: #c0392b;
        }
        
        .action-btn.view {
            background: #3498db;
        }
        
        .action-btn.view:hover {
            background: #2980b9;
        }
        
        .payment-details {
            background: rgba(44, 62, 80, 0.5);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
        
        .payment-details p {
            margin-bottom: 5px;
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
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .filter-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        
        .filter-tab {
            padding: 10px 20px;
            background: rgba(44, 62, 80, 0.5);
            border-radius: 25px;
            text-decoration: none;
            color: #bdc3c7;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active {
            background: #3498db;
            color: white;
        }
        
        .filter-tab:hover {
            background: rgba(52, 152, 219, 0.3);
            color: white;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .payments-table {
                font-size: 0.9rem;
            }
            
            .payments-table th, .payments-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cog"></i> Admin Payment Management</h1>
            <p>Manage and verify PCN Coin payment requests</p>
        </div>
        
        <?php if ($message): ?>
        <div class="message-box <?php echo strpos($message, 'Error') !== false ? 'error' : ''; ?>">
            <i class="fas fa-<?php echo strpos($message, 'Error') !== false ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card total">
                <i class="fas fa-list"></i>
                <h3><?php echo count($all_payments); ?></h3>
                <p>Total Payments</p>
            </div>
            <div class="stat-card pending">
                <i class="fas fa-clock"></i>
                <h3><?php echo count($pending_payments); ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card approved">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo count($approved_payments); ?></h3>
                <p>Approved</p>
            </div>
            <div class="stat-card rejected">
                <i class="fas fa-times-circle"></i>
                <h3><?php echo count($rejected_payments); ?></h3>
                <p>Rejected</p>
            </div>
        </div>
        
        <div class="payments-section">
            <h2><i class="fas fa-credit-card"></i> Payment Requests</h2>
            
            <div class="filter-tabs">
                <a href="#all" class="filter-tab active" onclick="filterPayments('all')">All Payments</a>
                <a href="#pending" class="filter-tab" onclick="filterPayments('pending')">Pending</a>
                <a href="#approved" class="filter-tab" onclick="filterPayments('approved')">Approved</a>
                <a href="#rejected" class="filter-tab" onclick="filterPayments('rejected')">Rejected</a>
            </div>
            
            <table class="payments-table" id="payments-table">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>User ID</th>
                        <th>Amount</th>
                        <th>Transaction ID</th>
                        <th>Memo</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_payments as $payment): ?>
                    <tr class="payment-row" data-status="<?php echo $payment['status']; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($payment['payment_id']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($payment['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($payment['amount']); ?> <?php echo htmlspecialchars($payment['currency']); ?></td>
                        <td>
                            <?php if ($payment['transaction_id']): ?>
                                <code><?php echo htmlspecialchars($payment['transaction_id']); ?></code>
                            <?php else: ?>
                                <span style="color: #95a5a6;">Not provided</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($payment['memo']): ?>
                                <div class="payment-details">
                                    <?php echo nl2br(htmlspecialchars($payment['memo'])); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #95a5a6;">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="status-<?php echo $payment['status']; ?>">
                            <?php echo ucfirst($payment['status']); ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($payment['created_date'])); ?></td>
                        <td>
                            <?php if ($payment['status'] === 'pending'): ?>
                                <a href="?action=approve&payment_id=<?php echo $payment['payment_id']; ?>" 
                                   class="action-btn approve" 
                                   onclick="return confirm('Approve this payment?')">
                                    <i class="fas fa-check"></i> Approve
                                </a>
                                <a href="?action=reject&payment_id=<?php echo $payment['payment_id']; ?>&reason=Invalid payment" 
                                   class="action-btn reject" 
                                   onclick="return confirm('Reject this payment?')">
                                    <i class="fas fa-times"></i> Reject
                                </a>
                            <?php else: ?>
                                <span style="color: #95a5a6;">Processed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="controls">
            <a href="index.php" class="btn">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="payment.php" class="btn">
                <i class="fas fa-crown"></i> Payment Page
            </a>
            <a href="status.php" class="btn">
                <i class="fas fa-server"></i> System Status
            </a>
        </div>
    </div>
    
    <script>
        function filterPayments(status) {
            const rows = document.querySelectorAll('.payment-row');
            const tabs = document.querySelectorAll('.filter-tab');
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter rows
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html> 