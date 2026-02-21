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

// Get user data if user_id is present
$user = null;
$user_id_query = '';
if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    $user = $db->getUser($userId);
    $user_id_query = '?user_id=' . $userId . '&t=' . time();
    $completed_tasks = $db->getTodaysCompletedTasks($userId);
    $can_check_in = $db->canCheckIn($userId);
    $special_code_claimed = $db->hasClaimedDailySpecialCode($userId);
} else {
    $completed_tasks = [];
    $can_check_in = false;
    $special_code_claimed = false;
}

// Tasks data
$all_tasks = $db->getAllTasks();

// Auto-seed default tasks if DB is empty
if ((defined('DB_AUTO_SETUP') && DB_AUTO_SETUP) && $db->getTasksCount() === 0) {
    require_once 'tasks_config.php';
    if (isset($tasks) && is_array($tasks)) {
        foreach ($tasks as $t) {
            $title = $t['title'] ?? ($t['name'] ?? 'Task');
            $reward = $t['reward'] ?? 0;
            $link = $t['link'] ?? ($t['url'] ?? '#');
            $icon = $t['icon'] ?? 'fas fa-tasks';
            $type = $t['type'] ?? 'social';
            $db->addTask($title, $reward, $link, $icon, $type);
        }
    }
    $all_tasks = $db->getAllTasks();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Tasks - PCN Coin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'app_style.php'; ?>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a2e 100%);
            color: white;
            margin: 0;
            padding: 20px;
            padding-bottom: 100px; /* Space for bottom nav */
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 2.5rem;
            color: #4ecdc4;
        }
        .task-list {
            display: grid;
            gap: 15px;
        }
        .task-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        .task-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
        }
        .task-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .task-info .icon {
            font-size: 2rem;
            color: #f39c12;
        }
        .task-details h3 {
            margin: 0;
            font-size: 1.2rem;
        }
        .task-details p {
            margin: 5px 0 0;
            color: #bdc3c7;
            font-size: 0.9rem;
        }
        .task-action .btn {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-width: 90px;
            box-sizing: border-box;
            font-size: 0.9rem;
        }
        .task-action .btn.completed {
             background: linear-gradient(135deg, #555, #777);
             cursor: not-allowed;
        }
        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(15, 15, 35, 0.95);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
            z-index: 1000;
            border-top: 1px solid rgba(255,255,255,0.1);
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
            color: #bdc3c7;
            text-decoration: none;
            font-size: 0.8rem;
            flex: 1;
        }
        .nav-item.active {
            color: #4ecdc4;
        }
        .nav-item i {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        /* Modern Tab System Styles */
        .task-cat-container {
            display: none !important;
        }
        .task-cat-container.active-tab {
            display: block !important;
        }
        
        .category-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0.7;
        }
        
        .category-btn.active {
            opacity: 1;
            background: var(--accent-gradient) !important;
            box-shadow: 0 4px 15px rgba(0, 242, 255, 0.3) !important;
            border: none !important;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="app-header">
            <h1><i class="fas fa-tasks"></i> Daily Tasks</h1>
            <p style="color: var(--text-dim);">Complete tasks to earn PCN rewards</p>
        </div>

        <div class="task-sections">
            <!-- Tabs for Categories -->
            <div style="display: flex; gap: 10px; margin-bottom: 25px; overflow-x: auto; padding: 5px 0;">
                <button type="button" data-cat="social" onclick="showTaskCategory('social')" class="app-btn category-btn active" id="btn-social" style="width: auto; padding: 12px 25px; font-size: 0.9rem; flex: 1;">Social</button>
                <button type="button" data-cat="daily" onclick="showTaskCategory('daily')" class="app-btn category-btn" id="btn-daily" style="width: auto; padding: 12px 25px; font-size: 0.9rem; flex: 1; background: rgba(255,255,255,0.1); color: white;">Daily</button>
                <button type="button" data-cat="special" onclick="showTaskCategory('special')" class="app-btn category-btn" id="btn-special" style="width: auto; padding: 12px 25px; font-size: 0.9rem; flex: 1; background: rgba(255,255,255,0.1); color: white;">Special</button>
            </div>

            <?php 
            $categories = ['social', 'daily', 'special'];
            foreach ($categories as $cat):
                $cat_tasks = array_filter($all_tasks, function($t) use ($cat) { return $t['type'] === $cat; });
            ?>
            <div id="cat-<?php echo $cat; ?>" class="task-cat-container <?php echo $cat === 'social' ? 'active-tab' : ''; ?>">
                <div class="app-card">
                    <h2 style="margin-bottom: 20px; text-transform: capitalize;"><i class="fas fa-star"></i> <?php echo $cat; ?> Tasks</h2>
                    <div class="tasks-list">
                        <?php if ($cat === 'daily'): ?>
                            <div class="task-item" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="width: 40px; height: 40px; background: rgba(0, 242, 255, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 0.9rem; margin-bottom: 2px;">Daily Check-in</h4>
                                        <p style="font-size: 0.75rem; color: var(--success);">+5 PCN</p>
                                    </div>
                                </div>

                                <?php if (!$user): ?>
                                    <span style="color: var(--text-dim); font-size: 0.8rem;">Open via bot</span>
                                <?php elseif (!$can_check_in): ?>
                                    <span style="color: var(--success); font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Done</span>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="app-btn claim-btn"
                                        data-task-id="daily_checkin"
                                        data-task-link="#"
                                        data-reward="5"
                                        style="width: auto; padding: 8px 15px; font-size: 0.8rem;"
                                    >
                                        Claim
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($cat === 'special'): ?>
                            <div class="task-item" style="display: grid; gap: 12px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <div style="width: 40px; height: 40px; background: rgba(0, 242, 255, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                            <i class="fas fa-key"></i>
                                        </div>
                                        <div>
                                            <h4 style="font-size: 0.9rem; margin-bottom: 2px;">Daily Article Code</h4>
                                            <p style="font-size: 0.75rem; color: var(--success);">+5 PCN (once per day)</p>
                                        </div>
                                    </div>

                                    <?php if (!$user): ?>
                                        <span style="color: var(--text-dim); font-size: 0.8rem;">Open via bot</span>
                                    <?php else: ?>
                                        <a class="app-btn" href="special_article.php<?php echo $user_id_query; ?>#code" style="width: auto; padding: 8px 15px; font-size: 0.8rem; text-decoration:none;">Open Article</a>
                                    <?php endif; ?>
                                </div>

                                <?php if ($user): ?>
                                    <?php if ($special_code_claimed): ?>
                                        <div style="display:flex; align-items:center; justify-content: space-between; gap: 12px;">
                                            <div style="color: var(--success); font-weight: 700;"><i class="fas fa-check-circle"></i> Claimed</div>
                                            <div style="font-size: 0.85rem; color: var(--text-dim);">Come back tomorrow for a new code</div>
                                        </div>
                                    <?php else: ?>
                                        <form id="special-code-form" style="display:flex; gap: 10px; align-items:center;">
                                            <input
                                                id="special-code-input"
                                                type="text"
                                                placeholder="Enter code (today)"
                                                autocomplete="off"
                                                style="flex: 1; padding: 12px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); background: rgba(0,0,0,0.2); color: white; text-transform: uppercase;"
                                            />
                                            <button
                                                id="special-code-submit"
                                                type="submit"
                                                class="app-btn"
                                                style="width: auto; padding: 10px 15px; font-size: 0.8rem;"
                                            >
                                                Submit
                                            </button>
                                        </form>
                                        <div id="special-code-message" style="font-size: 0.85rem; color: var(--text-dim);"></div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($cat_tasks) && $cat !== 'special'): ?>
                            <p style="text-align: center; color: var(--text-dim); padding: 20px;">No <?php echo $cat; ?> tasks available.</p>
                        <?php else: ?>
                            <?php foreach ($cat_tasks as $task): 
                                $taskId = $task['id'];
                                $isCompleted = in_array($taskId, $completed_tasks);
                            ?>
                            <div class="task-item" style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 12px; margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="width: 40px; height: 40px; background: rgba(0, 242, 255, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                                        <i class="<?php echo Security::xss($task['icon']); ?>"></i>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 0.9rem; margin-bottom: 2px;"><?php echo Security::xss($task['title']); ?></h4>
                                        <p style="font-size: 0.75rem; color: var(--success);">+<?php echo $task['reward']; ?> PCN</p>
                                    </div>
                                </div>
                                <?php if ($isCompleted): ?>
                                    <span style="color: var(--success); font-size: 0.8rem;"><i class="fas fa-check-circle"></i> Done</span>
                                <?php else: ?>
                                    <button
                                        type="button"
                                        class="app-btn claim-btn"
                                        data-task-id="<?php echo (int)$taskId; ?>"
                                        data-task-link="<?php echo Security::xss($task['link']); ?>"
                                        data-reward="<?php echo (float)$task['reward']; ?>"
                                        style="width: auto; padding: 8px 15px; font-size: 0.8rem;"
                                    >
                                        Claim
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
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
        <a href="tasks.php<?php echo $user_id_query; ?>" class="nav-item active">
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
        function showTaskCategory(cat) {
            console.log('Tab clicked:', cat);

            try {
                localStorage.setItem('active_task_cat', cat);
                if (typeof cat === 'string') {
                    window.location.hash = cat;
                }
            } catch (e) {}
            
            // 1. Handle Containers
            const containers = document.getElementsByClassName('task-cat-container');
            for (let i = 0; i < containers.length; i++) {
                containers[i].classList.remove('active-tab');
            }
            
            const targetContainer = document.getElementById('cat-' + cat);
            if (targetContainer) {
                targetContainer.classList.add('active-tab');
            }
            
            // 2. Handle Buttons
            const buttons = document.getElementsByClassName('category-btn');
            for (let i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove('active');
                buttons[i].style.background = 'rgba(255,255,255,0.1)';
            }
            
            const activeBtn = document.getElementById('btn-' + cat);
            if (activeBtn) {
                activeBtn.classList.add('active');
                activeBtn.style.background = 'var(--accent-gradient)';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.category-btn').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const cat = btn.getAttribute('data-cat');
                    if (cat) {
                        showTaskCategory(cat);
                    }
                });
            });

            let initialCat = 'social';
            const hash = (window.location.hash || '').replace('#', '').trim();
            if (hash) {
                initialCat = hash;
            } else {
                try {
                    const saved = localStorage.getItem('active_task_cat');
                    if (saved) initialCat = saved;
                } catch (e) {}
            }

            if (!document.getElementById('cat-' + initialCat)) {
                initialCat = 'social';
            }

            showTaskCategory(initialCat);
        });

        async function completeTask(taskId) {
            const userId = <?php echo json_encode($user ? $user['id'] : null); ?>;
            if (!userId) {
                alert('User not identified. Please access through the bot.');
                return { success: false, message: 'User not identified' };
            }

            const response = await fetch('complete_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: userId,
                    task_id: taskId,
                    _token: '<?php echo md5('pcn_secure_' . date('Y-m-d')); ?>'
                })
            });

            return response.json();
        }

        function startClaimFlow(btn) {
            const rawTaskId = btn.getAttribute('data-task-id');
            const taskId = rawTaskId === 'daily_checkin' ? 'daily_checkin' : parseInt(rawTaskId, 10);
            const taskLink = btn.getAttribute('data-task-link');
            if (!taskId || (typeof taskId === 'number' && !Number.isFinite(taskId))) return;

            if (taskId === 'daily_checkin') {
                const originalText = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'Claiming...';

                (async () => {
                    try {
                        const result = await completeTask('daily_checkin');
                        if (result && result.success) {
                            try {
                                localStorage.setItem('active_task_cat', 'daily');
                                window.location.hash = 'daily';
                            } catch (e) {}
                            window.location.reload();
                            return;
                        }
                        alert(result?.message || 'Failed to claim daily check-in.');
                    } catch (e) {
                        console.error(e);
                        alert('An error occurred. Please try again.');
                    } finally {
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                })();

                return;
            }

            if (taskLink && taskLink !== '#') {
                window.open(taskLink, '_blank');
            }

            let remaining = 30;
            btn.disabled = true;
            const originalText = btn.textContent;

            const interval = setInterval(() => {
                btn.textContent = `Claiming... ${remaining}s`;
                remaining -= 1;
                if (remaining < 0) {
                    clearInterval(interval);
                }
            }, 1000);

            setTimeout(async () => {
                try {
                    const result = await completeTask(taskId);
                    if (result && result.success) {
                        window.location.reload();
                        return;
                    }

                    alert(result?.message || 'Failed to claim task.');
                } catch (e) {
                    console.error(e);
                    alert('An error occurred. Please try again.');
                } finally {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            }, 30000);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.claim-btn').forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    startClaimFlow(btn);
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('special-code-form');
            if (!form) return;
            const input = document.getElementById('special-code-input');
            const msg = document.getElementById('special-code-message');
            const btn = document.getElementById('special-code-submit');
            const userId = <?php echo json_encode($user ? $user['id'] : null); ?>;

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (!userId) return;
                const code = (input && input.value) ? input.value.trim() : '';
                if (!code) {
                    if (msg) msg.textContent = 'Please enter the code.';
                    return;
                }
                if (btn) btn.disabled = true;
                if (msg) msg.textContent = 'Submitting...';
                try {
                    const res = await fetch('special_code_task.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            user_id: userId,
                            code: code,
                            _token: '<?php echo md5('pcn_secure_' . date('Y-m-d')); ?>'
                        })
                    });
                    const data = await res.json();
                    if (data && data.success) {
                        if (msg) msg.style.color = 'var(--success)';
                        if (msg) msg.textContent = data.message || 'Claimed';
                        try { form.style.display = 'none'; } catch (e) {}
                        const container = form.parentElement;
                        if (container) {
                            const claimed = document.createElement('div');
                            claimed.style.display = 'flex';
                            claimed.style.alignItems = 'center';
                            claimed.style.justifyContent = 'space-between';
                            claimed.style.gap = '12px';
                            claimed.innerHTML = '<div style="color: var(--success); font-weight: 700;"><i class="fas fa-check-circle"></i> Claimed</div><div style="font-size: 0.85rem; color: var(--text-dim);">Come back tomorrow for a new code</div>';
                            container.appendChild(claimed);
                        }
                        return;
                    }
                    if (msg) msg.style.color = 'var(--danger)';
                    if (msg) msg.textContent = (data && data.message) ? data.message : 'Failed.';
                } catch (err) {
                    if (msg) msg.style.color = 'var(--danger)';
                    if (msg) msg.textContent = 'Network error. Please try again.';
                } finally {
                    if (btn) btn.disabled = false;
                }
            });
        });
    </script>
</body>
</html>
