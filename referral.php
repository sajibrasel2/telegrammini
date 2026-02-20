<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
require_once __DIR__ . '/telegram_gate.php';
require_once 'config.php'; // For BOT_NAME

$user_id_query = '';
if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
    $user_id_query = '?user_id=' . $_GET['user_id'] . '&t=' . time();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Coin - Referral System</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include 'app_style.php'; ?>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <style>
        :root {
            --bg-dark: #111827;
            --bg-med: #1f2937;
            --bg-light: #374151;
            --text-primary: #f9fafb;
            --text-secondary: #9ca3af;
            --accent: #22d3ee; /* Bright Cyan */
            --accent-dark: #0e7490;
            --success: #22c55e;
            --warning: #f59e0b;
            --font-family: 'Poppins', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-dark);
            color: var(--text-primary);
            margin: 0;
            padding: 20px 20px 100px 20px;
        }

        .loading-container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 80vh;
            gap: 20px;
        }

        .loading-container i {
            font-size: 3rem;
            color: var(--accent);
            animation: spin 1.5s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        header {
            text-align: center;
        }

        header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0 0 10px 0;
        }

        header p {
            font-size: 1rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .card {
            background-color: var(--bg-med);
            border-radius: 16px;
            padding: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .card h2 {
            font-size: 1.4rem;
            font-weight: 600;
            margin: 0 0 20px 0;
            text-align: center;
            color: var(--accent);
        }

        .link-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        #ref-link-text {
            flex-grow: 1;
            background-color: var(--bg-dark);
            border: 1px solid var(--bg-light);
            border-radius: 8px;
            padding: 12px 15px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .copy-btn {
            background-color: var(--accent);
            color: var(--bg-dark);
            border: none;
            border-radius: 8px;
            padding: 0 20px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.2s;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .copy-btn:hover { background-color: #67e8f9; }
        .copy-btn:active { transform: scale(0.95); }

        .share-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            transition: transform 0.2s;
        }
        .share-btn:hover { transform: scale(1.05); }
        .share-btn.telegram { background-color: #0088cc; }
        .share-btn.whatsapp { background-color: #25d366; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }

        .stat-card {
            background-color: var(--bg-light);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
            margin: 0 0 5px 0;
        }

        .stat-card p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .benefits-card .benefit-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .benefits-card .benefit-item:not(:last-child) {
            margin-bottom: 15px;
        }

        .benefit-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .benefit-icon.paid { background-color: var(--accent-dark); color: var(--accent); }
        .benefit-icon.free { background-color: var(--bg-light); color: var(--text-primary); }

        .benefit-item h4 { font-size: 1rem; margin: 0 0 4px 0; }
        .benefit-item p { font-size: 0.9rem; color: var(--text-secondary); margin: 0; line-height: 1.4; }

        .tree-container {
            padding: 20px;
            background-color: var(--bg-dark);
            border-radius: 12px;
            border: 1px solid var(--bg-light);
        }

        .node {
            position: relative;
            padding-left: 30px;
        }

        .node:not(:last-child) {
            margin-bottom: 15px;
        }

        .node-content {
            display: flex;
            align-items: center;
            gap: 12px;
            background-color: var(--bg-light);
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid var(--text-secondary);
        }
        .node-content.paid { border-left-color: var(--accent); }

        .node-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .node-info {
            overflow: hidden;
        }

        .node-username {
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .node-balance {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .node-children {
            padding-top: 15px;
            position: relative;
        }

        .node::before, .node::after {
            content: '';
            position: absolute;
            left: 10px;
            border-color: var(--bg-light);
        }

        .node::before {
            border-top: 1px solid;
            top: 30px;
            width: 15px;
            height: 0;
        }

        .node:not(:first-child)::after {
            border-left: 1px solid;
            height: 100%;
            top: -15px;
            width: 0;
        }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-med);
            padding: 10px 0;
            z-index: 1000;
            border-top: 1px solid var(--bg-light);
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
            transition: color 0.2s, transform 0.2s;
            flex: 1;
        }
        .nav-item i { font-size: 1.4rem; margin-bottom: 4px; }
        .nav-item.active, .nav-item:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div id="loading-state" class="loading-container" style="height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--bg-dark);">
        <div style="text-align: center;">
            <i class="fas fa-spinner fa-spin fa-3x" style="color: var(--primary); margin-bottom: 20px;"></i>
            <p>Loading Referral Network...</p>
        </div>
    </div>

    <div class="app-container" id="app-content" style="display: none;">
        <div class="app-header">
            <h1><i class="fas fa-share-nodes"></i> Referral Network</h1>
            <p style="color: var(--text-dim);">Grow your network & earn PCN rewards</p>
        </div>

        <div class="app-card">
            <h3 style="font-size: 1rem; margin-bottom: 15px; color: var(--accent);">Your Referral Link</h3>
            <div class="link-container" style="display: flex; gap: 10px; background: rgba(0,0,0,0.2); padding: 5px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <span id="ref-link-text" style="flex: 1; padding: 10px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.85rem; color: var(--text-dim);">Loading...</span>
                <button class="app-btn copy-btn" onclick="copyReferralLink()" style="width: auto; padding: 10px 20px; font-size: 0.85rem; border-radius: 10px; background: var(--primary); color: white;">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
            <div class="share-buttons" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px;">
                <a id="share-telegram" href="#" class="app-btn" style="background: #0088cc; color: white; border-radius: 12px; padding: 10px; font-size: 0.85rem;">
                    <i class="fab fa-telegram-plane"></i> Telegram
                </a>
                <a id="share-whatsapp" href="#" class="app-btn" style="background: #25d366; color: white; border-radius: 12px; padding: 10px; font-size: 0.85rem;">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>

        <div class="app-card" id="treeStats">
            <h3 style="font-size: 1rem; margin-bottom: 20px; text-align: center;">Network Statistics</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="stat-card" style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 15px; text-align: center; border-bottom: 2px solid var(--primary);">
                    <div class="stat-number" style="font-size: 1.5rem; font-weight: bold; color: var(--primary);">0</div>
                    <div style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase;">Direct</div>
                </div>
                <div class="stat-card" style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 15px; text-align: center; border-bottom: 2px solid var(--accent);">
                    <div class="stat-number" style="font-size: 1.5rem; font-weight: bold; color: var(--accent);">0</div>
                    <div style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase;">Total Network</div>
                </div>
                <div class="stat-card" style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 15px; text-align: center; border-bottom: 2px solid var(--success);">
                    <div class="stat-number" style="font-size: 1.5rem; font-weight: bold; color: var(--success);">0</div>
                    <div style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase;">Premium</div>
                </div>
                <div class="stat-card" style="background: rgba(255,255,255,0.03); padding: 15px; border-radius: 15px; text-align: center; border-bottom: 2px solid var(--text-dim);">
                    <div class="stat-number" style="font-size: 1.5rem; font-weight: bold; color: var(--text-dim);">0</div>
                    <div style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase;">Free</div>
                </div>
            </div>
        </div>

        <div class="app-card" style="padding: 0; overflow: hidden;">
            <h3 style="font-size: 1rem; padding: 20px; background: rgba(255,255,255,0.03); margin: 0; border-bottom: 1px solid rgba(255,255,255,0.05);"><i class="fas fa-network-wired"></i> Referral Tree</h3>
            <div id="referralTree" style="padding: 20px; min-height: 100px;">
                <!-- Tree will be rendered here -->
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php<?php echo $user_id_query; ?>" class="nav-item" id="nav-home">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="tasks.php<?php echo $user_id_query; ?>" class="nav-item" id="nav-tasks">
            <i class="fas fa-tasks"></i>
            <span>Tasks</span>
        </a>
        <a href="referral.php<?php echo $user_id_query; ?>" class="nav-item active" id="nav-friends">
            <i class="fas fa-users"></i>
            <span>Friends</span>
        </a>
        <a href="payment.php<?php echo $user_id_query; ?>" class="nav-item" id="nav-premium">
            <i class="fas fa-crown"></i>
            <span>Premium</span>
        </a>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const userId = urlParams.get('user_id');
            const loadingState = document.getElementById('loading-state');
            const appContent = document.getElementById('app-content');

            if (!userId) {
                loadingState.innerHTML = '<p>Error: User ID is missing. Please access this page through the bot.</p>';
                return;
            }

            const ctrl = (window.AbortController ? new AbortController() : null);
            if (ctrl && typeof window.__registerAbortController === 'function') {
                window.__registerAbortController(ctrl);
            }

            fetch(`get_user_data.php?user_id=${userId}`, ctrl ? { signal: ctrl.signal } : undefined)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Network error: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    appContent.style.display = 'block';
                    loadingState.style.display = 'none';

                    updateReferralInfo(data.referral_link);
                    updateNavLinks(userId, data.user);
                    updateTreeStats(data.referral_stats);
                    renderReferralTree(data.referral_tree);
                })
                .catch(error => {
                    console.error('Failed to load user data:', error);
                    loadingState.innerHTML = `<p>Error: ${error.message}. Please try refreshing.</p>`;
                    appContent.style.display = 'none';
                });
        });

        function updateReferralInfo(referralLink) {
            const refLinkEl = document.getElementById('ref-link-text');
            const shareTelegram = document.getElementById('share-telegram');
            const shareWhatsapp = document.getElementById('share-whatsapp');

            if (refLinkEl) {
                refLinkEl.textContent = referralLink;
            }
            if (shareTelegram && shareWhatsapp) {
                const shareText = encodeURIComponent(`Join me and earn rewards! ${referralLink}`);
                shareTelegram.href = `https://t.me/share/url?url=${encodeURIComponent(referralLink)}&text=${shareText}`;
                shareWhatsapp.href = `https://api.whatsapp.com/send?text=${shareText}`;
            }
        }

        function copyReferralLink() {
            const refLinkText = document.getElementById('ref-link-text').textContent;
            if (!refLinkText || refLinkText === 'Loading...') return;

            navigator.clipboard.writeText(refLinkText).then(() => {
                const copyButton = document.querySelector('.copy-btn');
                if (!copyButton) return;
                const icon = copyButton.querySelector('i');
                const text = copyButton.querySelector('span');
                
                const originalIconClass = icon ? icon.className : '';
                const originalText = text ? text.textContent : '';

                if (icon) icon.className = 'fas fa-check';
                if (text) {
                    text.textContent = 'Copied!';
                } else {
                    copyButton.textContent = 'Copied!';
                }

                setTimeout(() => {
                    if (icon) icon.className = originalIconClass;
                    if (text) {
                        text.textContent = originalText;
                    } else {
                        copyButton.innerHTML = '<i class="fas fa-copy"></i> Copy';
                    }
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy text: ', err);
                alert('Failed to copy link.');
            });
        }

        function updateNavLinks(userId, user) {
            const items = document.querySelectorAll('.nav-item');
            items.forEach(item => {
                const href = item.getAttribute('href');
                if (href && href !== '#') {
                    // Update existing user_id in URL or append it
                    const url = new URL(href, window.location.origin);
                    url.searchParams.set('user_id', userId);
                    item.setAttribute('href', url.pathname + url.search);
                }
            });
        }

        function updateTreeStats(stats) {
            if (!stats) {
                stats = { direct_referrals: 0, level2_referrals: 0, level3_referrals: 0, paid_direct: 0, paid_level2: 0, free_direct: 0, free_level2: 0 };
            }
            const n1 = document.querySelector('#treeStats .stat-card:nth-child(1) .stat-number');
            const n2 = document.querySelector('#treeStats .stat-card:nth-child(2) .stat-number');
            const n3 = document.querySelector('#treeStats .stat-card:nth-child(3) .stat-number');
            const n4 = document.querySelector('#treeStats .stat-card:nth-child(4) .stat-number');

            const direct = parseInt(stats.direct_referrals) || 0;
            const l2 = parseInt(stats.level2_referrals) || 0;
            const l3 = parseInt(stats.level3_referrals) || 0;
            const paid = (parseInt(stats.paid_direct) || 0) + (parseInt(stats.paid_level2) || 0);
            const free = (parseInt(stats.free_direct) || 0) + (parseInt(stats.free_level2) || 0);

            if (n1) n1.textContent = direct;
            if (n2) n2.textContent = direct + l2 + l3;
            if (n3) n3.textContent = paid;
            if (n4) n4.textContent = free;
        }

        function renderReferralTree(treeData) {
            const treeContainer = document.getElementById('referralTree');
            if (treeData && treeData.user) {
                treeContainer.innerHTML = renderNode(treeData);
            } else {
                treeContainer.innerHTML = `<p style="text-align:center; color: var(--text-secondary);">You have no referrals yet. Share your link to get started!</p>`;
            }
        }

        function renderNode(node) {
            if (!node || !node.user) return '';

            const user = node.user;
            const children = node.referrals || [];
            const childrenHtml = children.length > 0 ? `<div class="node-children">${children.map(renderNode).join('')}</div>` : '';

            const subscriptionClass = user.subscription_type === 'paid' ? 'paid' : 'free';
            const avatarUrl = user.photo_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.first_name || user.username || 'U')}&background=random&color=fff&size=40`;

            return `
                <div class="node">
                    <div class="node-content ${subscriptionClass}">
                        <img src="${avatarUrl}" alt="Avatar" class="node-avatar" onerror="this.src='https://ui-avatars.com/api/?name=U&background=random&color=fff&size=40'; this.onerror=null;">
                        <div class="node-info">
                            <div class="node-username">${user.username || user.first_name || 'User'}</div>
                            <div class="node-balance">${parseFloat(user.balance).toFixed(2) || 0} PCN</div>
                        </div>
                    </div>
                    ${childrenHtml}
                </div>
            `;
        }
    </script>
</body>
</html>