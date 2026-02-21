<?php
require_once __DIR__ . '/telegram_gate.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security_helper.php';

$db = new Database();

$ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
$isTelegramWebView = (stripos($ua, 'Telegram') !== false);

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$user_id_query = $user_id ? '?user_id=' . htmlspecialchars($user_id, ENT_QUOTES, 'UTF-8') . '&t=' . time() : '';

$code = null;
$claimed = false;
if ($user_id) {
    $code = $db->getDailySpecialCode($user_id);
    $claimed = $db->hasClaimedDailySpecialCode($user_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Learning Article - Daily Code</title>
    <meta name="description" content="Learn crypto basics and earn PCN by submitting the daily code in the Tasks page.">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php include 'app_style.php'; ?>
    <?php if ($isTelegramWebView): ?>
        <script src='//libtl.com/sdk.js' data-zone='10637684' data-sdk='show_10637684'></script>
    <?php else: ?>
        <script src="https://quge5.com/88/tag.min.js" data-zone="213183" async data-cfasync="false"></script>
    <?php endif; ?>
</head>
<body>
    <div class="app-container" style="max-width: 850px;">
        <div class="app-header">
            <h1><i class="fas fa-book-open"></i> Crypto Article</h1>
            <p style="color: var(--text-dim);">Read and collect your daily code</p>
        </div>

        <?php if (!$user_id): ?>
            <div class="app-card">
                <p style="color: var(--danger); margin: 0;">User ID missing. Please open this page from the bot.</p>
            </div>
        <?php else: ?>
            <div class="app-card" style="line-height: 1.8;">
                <h2 style="margin-top:0;">What is cryptocurrency and how to stay safe</h2>
                <p>Cryptocurrency is a digital asset secured by cryptography. It enables peer-to-peer transfers without traditional intermediaries. In this article, you will learn the basics of wallets, networks, fees, and common scams.</p>
                <p><strong>Wallets:</strong> A wallet can be custodial (exchange) or non-custodial (you hold keys). Never share your seed phrase. Use reputable wallets and keep backups offline.</p>
                <p><strong>Networks:</strong> Tokens live on blockchains (like TON). Always confirm the network before sending funds. A wrong network can result in permanent loss.</p>
                <p><strong>Fees:</strong> Every blockchain uses fees. Low fees are good, but do not trust any project promising guaranteed profits.</p>
                <p><strong>Scams:</strong> Beware of fake support, impersonation, and airdrop scams. Verify official usernames and domains. If someone asks for OTP/seed phrase, it is a scam.</p>
                <p>Now scroll down to get your daily code. Submit it in Tasks → Special to earn PCN.</p>
            </div>

            <div class="app-card" id="code" style="border: 1px solid rgba(0,242,255,0.25); background: rgba(0,242,255,0.06);">
                <h3 style="margin-top:0; color: var(--primary);"><i class="fas fa-key"></i> Your Daily Code</h3>
                <div style="display:flex; gap: 12px; align-items:center; flex-wrap: wrap;">
                    <div style="font-family: monospace; font-size: 1.6rem; letter-spacing: 2px; padding: 10px 14px; border-radius: 12px; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08);">
                        <?php echo htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php if ($claimed): ?>
                        <div style="color: var(--success); font-weight: 700;"><i class="fas fa-check-circle"></i> Claimed today</div>
                    <?php else: ?>
                        <div style="color: var(--text-dim);">Not claimed yet</div>
                    <?php endif; ?>
                </div>
                <p style="margin: 12px 0 0; color: var(--text-dim); font-size: 0.9rem;">This code changes daily and is unique per user.</p>
            </div>

            <div style="display:flex; gap: 12px;">
                <a class="app-btn" href="tasks.php<?php echo $user_id_query; ?>#special" style="flex:1; text-align:center;">Go to Special Task</a>
                <a class="app-btn" href="index.php<?php echo $user_id_query; ?>" style="flex:1; text-align:center; background: rgba(255,255,255,0.1);">Back Home</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isTelegramWebView): ?>
    <script>
        (function () {
            function tryShowAd() {
                try {
                    if (typeof window.show_10637684 === 'function') {
                        window.show_10637684();
                    }
                } catch (e) {}
            }

            try {
                document.addEventListener('DOMContentLoaded', function () {
                    setTimeout(tryShowAd, 1200);
                });
            } catch (e) {
                setTimeout(tryShowAd, 1500);
            }
        })();
    </script>
    <?php endif; ?>
</body>
</html>
