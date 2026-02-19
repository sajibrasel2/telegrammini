<?php
require_once 'config.php';
require_once 'database.php';
require_once 'security_helper.php';
require_once 'admin_auth.php';

$db = new Database();

admin_require_login();

$stats = $db->getTotalStats();
$recent_payments = $db->getPendingPayments();
$users = $db->getUsersWithPagination(1, 10);
$sub_counts = $db->getUserSubscriptionCounts();
$tasks = $db->getAllTasks();
$ads = $db->getAdsConfig();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'app_style.php'; ?>
    <style>
        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: var(--card-bg); padding: 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: var(--primary); }
        .stat-label { font-size: 0.8rem; color: var(--text-dim); text-transform: uppercase; }
        .table-container { background: var(--card-bg); border-radius: 15px; overflow-x: auto; padding: 15px; margin-bottom: 25px; }
        table { width: 100%; border-collapse: collapse; color: white; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { color: var(--text-dim); font-size: 0.85rem; }
        .status-badge { padding: 4px 10px; border-radius: 10px; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .status-approved { background: rgba(46, 204, 113, 0.1); color: #2ecc71; }
    </style>
</head>
<body style="padding-bottom: 0;">
    <div class="app-container" style="max-width: 1000px; margin: 0 auto; padding: 20px;">
        <header style="margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="font-size: 1.8rem;"><i class="fas fa-user-shield" style="color: var(--primary);"></i> Admin Panel</h1>
                <p style="color: var(--text-dim);">Real-time Ecosystem Analytics</p>
            </div>
            <a href="index.php" class="app-btn" style="width: auto; padding: 10px 20px;">Back to App</a>
        </header>

        <div class="admin-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($stats['total_coins_distributed'], 0); ?></div>
                <div class="stat-label">PCN Distributed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $sub_counts['paid']; ?></div>
                <div class="stat-label">Premium Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($tasks); ?></div>
                <div class="stat-label">Active Tasks</div>
            </div>
        </div>

        <section id="task-management" style="margin-bottom: 40px;">
            <h2 style="margin-bottom: 15px;"><i class="fas fa-tasks"></i> Task Management</h2>
            <div class="app-card">
                <form id="add-task-form" style="display: grid; gap: 10px;">
                    <input type="text" name="title" placeholder="Task Title" class="app-input" required style="padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                    <input type="number" name="reward" placeholder="Reward (PCN)" class="app-input" required style="padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                    <input type="url" name="link" placeholder="Task Link" class="app-input" required style="padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                    <select name="type" class="app-input" style="padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                        <option value="social">Social</option>
                        <option value="daily">Daily</option>
                        <option value="special">Special</option>
                    </select>
                    <button type="submit" class="app-btn">Add New Task</button>
                </form>
                <div style="margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" onclick="resetSocialTasks()" class="app-btn" style="width: auto; padding: 10px 15px; font-size: 0.85rem; background: rgba(254, 202, 87, 0.15); box-shadow: none;">
                        Reset Social Tasks (Restore Default 4)
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <div style="margin-bottom: 15px; display: flex; gap: 10px;">
                    <button onclick="filterTasks('all')" class="app-btn" style="width: auto; padding: 5px 15px; font-size: 0.8rem; background: rgba(255,255,255,0.1);">All</button>
                    <button onclick="filterTasks('social')" class="app-btn" style="width: auto; padding: 5px 15px; font-size: 0.8rem; background: rgba(0, 242, 255, 0.1);">Social</button>
                    <button onclick="filterTasks('daily')" class="app-btn" style="width: auto; padding: 5px 15px; font-size: 0.8rem; background: rgba(46, 204, 113, 0.1);">Daily</button>
                    <button onclick="filterTasks('special')" class="app-btn" style="width: auto; padding: 5px 15px; font-size: 0.8rem; background: rgba(254, 202, 87, 0.1);">Special</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Reward</th>
                            <th>Type</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="tasks-table-body">
                        <?php foreach ($tasks as $task): ?>
                        <tr class="task-row" data-type="<?php echo $task['type']; ?>">
                            <td><?php echo Security::xss($task['title']); ?></td>
                            <td><?php echo $task['reward']; ?> PCN</td>
                            <td>
                                <span class="status-badge" style="background: rgba(255,255,255,0.05);">
                                    <?php echo strtoupper($task['type']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                    <button
                                        type="button"
                                        onclick="openEditTaskModal(<?php echo (int)$task['id']; ?>, '<?php echo Security::xss($task['title']); ?>', '<?php echo (float)$task['reward']; ?>', '<?php echo Security::xss($task['link']); ?>', '<?php echo Security::xss($task['type']); ?>')"
                                        class="app-btn"
                                        style="padding: 5px 10px; font-size: 0.8rem; background: rgba(255,255,255,0.12); box-shadow: none;"
                                    >Edit</button>
                                    <button onclick="deleteTask(<?php echo $task['id']; ?>)" class="app-btn" style="padding: 5px 10px; font-size: 0.8rem; background: var(--danger);">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tasks)): ?>
                        <tr><td colspan="4" style="text-align: center; color: var(--text-dim);">No tasks found in database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Edit Task Modal -->
        <div id="edit-task-modal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
            <div class="app-card" style="width: 100%; max-width: 520px;">
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin:0;">Edit Task</h3>
                    <button type="button" onclick="closeEditTaskModal()" class="app-btn" style="width:auto; padding: 6px 12px; font-size: 0.8rem; background: rgba(255,255,255,0.12); box-shadow:none;">Close</button>
                </div>
                <form id="edit-task-form" style="display: grid; gap: 10px;">
                    <input type="hidden" name="task_id" id="edit_task_id">
                    <input type="text" name="title" id="edit_title" placeholder="Task Title" required style="padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                    <input type="number" step="0.01" name="reward" id="edit_reward" placeholder="Reward (PCN)" required style="padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                    <input type="url" name="link" id="edit_link" placeholder="Task Link" required style="padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                    <select name="type" id="edit_type" required style="padding: 12px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white;">
                        <option value="social">Social</option>
                        <option value="daily">Daily</option>
                        <option value="special">Special</option>
                    </select>
                    <button type="submit" class="app-btn">Save Changes</button>
                </form>
            </div>
        </div>

        <section id="ads-management" style="margin-bottom: 40px;">
            <h2 style="margin-bottom: 15px;"><i class="fas fa-ad"></i> Ads Configuration (Adsterra/Monetag)</h2>
            <div class="app-card">
                <form id="update-ads-form" style="display: grid; gap: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: var(--text-dim); font-size: 0.8rem;">Ad Slot 1 (Top Banner)</label>
                        <textarea name="ad_code_1" placeholder="Paste Adsterra/Monetag HTML Code here" style="width: 100%; height: 80px; padding: 10px; border-radius: 10px; background: rgba(0,0,0,0.2); color: white; border: 1px solid rgba(255,255,255,0.1);"><?php 
                            $code1 = array_filter($ads, function($a) { return $a['ad_slot'] === 'slot_1'; });
                            echo !empty($code1) ? Security::xss(reset($code1)['ad_code']) : ''; 
                        ?></textarea>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; color: var(--text-dim); font-size: 0.8rem;">Ad Slot 2 (Native/Popup)</label>
                        <textarea name="ad_code_2" placeholder="Paste Adsterra/Monetag HTML Code here" style="width: 100%; height: 80px; padding: 10px; border-radius: 10px; background: rgba(0,0,0,0.2); color: white; border: 1px solid rgba(255,255,255,0.1);"><?php 
                            $code2 = array_filter($ads, function($a) { return $a['ad_slot'] === 'slot_2'; });
                            echo !empty($code2) ? Security::xss(reset($code2)['ad_code']) : ''; 
                        ?></textarea>
                    </div>
                    <button type="submit" class="app-btn">Update Ad Settings</button>
                </form>
            </div>
        </section>

        <h2 style="margin-bottom: 15px;"><i class="fas fa-clock"></i> Pending Payments</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>TX ID</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_payments as $payment): ?>
                    <tr>
                        <td><?php echo Security::xss($payment['username']); ?></td>
                        <td style="font-family: monospace; font-size: 0.8rem;"><?php echo substr($payment['transaction_id'], 0, 10); ?>...</td>
                        <td><?php echo $payment['amount']; ?> TON</td>
                        <td><?php echo date('M d, H:i', strtotime($payment['created_at'])); ?></td>
                        <td>
                            <button onclick="approvePayment('<?php echo $payment['payment_id']; ?>')" class="app-btn" style="padding: 5px 10px; font-size: 0.8rem; background: var(--success);">Approve</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recent_payments)): ?>
                    <tr><td colspan="5" style="text-align: center; color: var(--text-dim);">No pending payments</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h2 style="margin-bottom: 15px;"><i class="fas fa-users"></i> Recent Users</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Type</th>
                        <th>Balance</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td style="font-size: 0.8rem;"><?php echo $u['id']; ?></td>
                        <td><?php echo Security::xss($u['username']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $u['subscription_type'] === 'paid' ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo strtoupper($u['subscription_type']); ?>
                            </span>
                        </td>
                        <td><?php echo number_format($u['balance'], 2); ?></td>
                        <td><?php echo date('M d', strtotime($u['joined_date'])); ?></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button onclick="updateUserStatus('<?php echo $u['id']; ?>', 'upgrade')" class="app-btn" style="padding: 5px 10px; font-size: 0.75rem; background: var(--success); display: <?php echo $u['subscription_type'] === 'free' ? 'flex' : 'none'; ?>;">Upgrade</button>
                                <button onclick="updateUserStatus('<?php echo $u['id']; ?>', 'downgrade')" class="app-btn" style="padding: 5px 10px; font-size: 0.75rem; background: var(--danger); display: <?php echo $u['subscription_type'] === 'paid' ? 'flex' : 'none'; ?>;">Downgrade</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.getElementById('add-task-form').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'add_task');
        
        const response = await fetch('admin_task_action.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    };

    async function deleteTask(taskId) {
        if (!confirm('Delete this task?')) return;
        const formData = new FormData();
        formData.append('action', 'delete_task');
        formData.append('task_id', taskId);
        
        const response = await fetch('admin_task_action.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    }

    function openEditTaskModal(id, title, reward, link, type) {
        document.getElementById('edit_task_id').value = id;
        document.getElementById('edit_title').value = title;
        document.getElementById('edit_reward').value = reward;
        document.getElementById('edit_link').value = link;
        document.getElementById('edit_type').value = type;
        const modal = document.getElementById('edit-task-modal');
        modal.style.display = 'flex';
    }

    function closeEditTaskModal() {
        document.getElementById('edit-task-modal').style.display = 'none';
    }

    document.getElementById('edit-task-form').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'update_task');

        const response = await fetch('admin_task_action.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            closeEditTaskModal();
            location.reload();
        }
    };

    async function resetSocialTasks() {
        if (!confirm('This will delete ALL current Social tasks and restore the default 4 tasks. Continue?')) return;
        const formData = new FormData();
        formData.append('action', 'reset_social_tasks');

        const response = await fetch('admin_task_action.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) {
            location.reload();
        }
    }

    function filterTasks(type) {
        const rows = document.querySelectorAll('.task-row');
        rows.forEach(row => {
            if (type === 'all' || row.getAttribute('data-type') === type) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }

    document.getElementById('update-ads-form').onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('action', 'update_ads');
        
        const response = await fetch('admin_task_action.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if (result.success) location.reload();
    };

    function approvePayment(paymentId) {
        if (confirm('Are you sure you want to approve this payment?')) {
            window.location.href = 'approve_payment.php?payment_id=' + paymentId;
        }
    }

    async function updateUserStatus(userId, action) {
        const confirmMsg = action === 'upgrade' ? 'Upgrade this user to PREMIUM?' : 'Downgrade this user to FREE?';
        if (!confirm(confirmMsg)) return;

        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', action);

            const response = await fetch('admin_user_action.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            alert(result.message);
            if (result.success) {
                location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while updating user status.');
        }
    }
    </script>
</body>
</html>
