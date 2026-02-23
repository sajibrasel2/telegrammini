<?php
require_once __DIR__ . '/../config.php';

$siteBase = 'https://techandclick.site/telegrammini';
$botUrl = 'https://t.me/PCN_OfficialBot';
$botDeepLink = $botUrl . '?start=ads';
$miniAppUrl = $siteBase . '/index.php';

$userId = isset($_GET['user_id']) ? preg_replace('/[^0-9]/', '', (string)$_GET['user_id']) : '';
$userParam = $userId !== '' ? ('?user_id=' . $userId) : '';
$miniAppUrlWithUser = $miniAppUrl . $userParam;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Official - Telegram Bot</title>
    <meta name="description" content="PCN Official on Telegram. Learn about PCN Coin, explore tasks, and follow official updates inside Telegram." />
    <meta name="robots" content="index,follow" />
    <link rel="canonical" href="<?php echo htmlspecialchars($siteBase . '/ads/'); ?>" />

    <meta property="og:type" content="website" />
    <meta property="og:title" content="PCN Official - Telegram Bot" />
    <meta property="og:description" content="Open the official PCN bot on Telegram and explore the PCN mini app." />
    <meta property="og:url" content="<?php echo htmlspecialchars($siteBase . '/ads/'); ?>" />

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "PCN Official",
      "url": "<?php echo htmlspecialchars($siteBase . '/ads/'); ?>",
      "sameAs": [
        "<?php echo htmlspecialchars($botUrl); ?>"
      ]
    }
    </script>

    <?php include __DIR__ . '/../app_style.php'; ?>
</head>
<body>
    <div class="app-container">
        <div class="app-header">
            <h1 style="font-size: 1.3rem;">PCN Official</h1>
            <p style="color: var(--text-dim);">Official Telegram bot & mini app access</p>
        </div>

        <div class="app-card" style="margin-bottom: 14px;">
            <h2 style="font-size: 1.05rem; margin-bottom: 10px;">Open on Telegram</h2>
            <p style="color: var(--text-dim); line-height: 1.5; font-size: 0.9rem;">
                This is the official destination for the PCN Telegram bot. You can open the bot on Telegram and access the PCN mini app.
            </p>
            <div style="margin-top: 14px; display: grid; gap: 10px;">
                <a class="app-btn btn-primary" href="<?php echo htmlspecialchars($botDeepLink); ?>" target="_blank" rel="noopener">Open @PCN_OfficialBot</a>
                <a class="app-btn btn-outline" href="<?php echo htmlspecialchars($miniAppUrlWithUser); ?>" target="_blank" rel="noopener">Open PCN Mini App</a>
            </div>
        </div>

        <div class="app-card" style="margin-bottom: 14px;">
            <h3 style="font-size: 1rem; margin-bottom: 8px;">What you will find</h3>
            <div style="color: var(--text-dim); line-height: 1.55; font-size: 0.9rem;">
                <div style="margin-bottom: 8px;">- Official updates and announcements</div>
                <div style="margin-bottom: 8px;">- Optional tasks and community activities</div>
                <div>- Educational content about PCN Coin and related topics</div>
            </div>
        </div>

        <div class="app-card" style="margin-bottom: 14px;">
            <h3 style="font-size: 1rem; margin-bottom: 8px;">Important notice</h3>
            <p style="color: var(--text-dim); line-height: 1.55; font-size: 0.9rem;">
                Participation in any activity is optional. Any points or rewards (if available) depend on user activity and app rules.
                This page does not offer investment advice and does not guarantee earnings.
            </p>
        </div>

        <div class="app-card" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
            <a class="app-btn btn-outline" href="<?php echo htmlspecialchars($siteBase . '/ads/privacy.php'); ?>" target="_blank" rel="noopener" style="font-size: 0.9rem;">Privacy Policy</a>
            <a class="app-btn btn-outline" href="<?php echo htmlspecialchars($siteBase . '/ads/terms.php'); ?>" target="_blank" rel="noopener" style="font-size: 0.9rem;">Terms</a>
            <a class="app-btn btn-outline" href="<?php echo htmlspecialchars($siteBase . '/blog.php'); ?>" target="_blank" rel="noopener" style="font-size: 0.9rem;">Blog</a>
        </div>
    </div>
</body>
</html>
