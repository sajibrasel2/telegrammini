<?php
$siteBase = 'https://techandclick.site/telegrammini';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - PCN Official</title>
    <meta name="robots" content="index,follow" />
    <link rel="canonical" href="<?php echo htmlspecialchars($siteBase . '/ads/privacy.php'); ?>" />
    <?php include __DIR__ . '/../app_style.php'; ?>
</head>
<body>
    <div class="app-container">
        <div class="app-header">
            <h1 style="font-size: 1.2rem;">Privacy Policy</h1>
            <p style="color: var(--text-dim);">PCN Official</p>
        </div>

        <div class="app-card" style="line-height: 1.6; color: var(--text-dim); font-size: 0.92rem;">
            <p style="margin-bottom: 12px;">This Privacy Policy describes how PCN Official pages and services handle information.</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">Information we may process</h2>
            <p style="margin-bottom: 12px;">When you access our Telegram bot or mini app, Telegram may provide basic data such as your Telegram user identifier and username. This data is used to operate features (for example: showing your profile, tracking optional tasks, or awarding points).</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">How we use information</h2>
            <p style="margin-bottom: 12px;">We use information only to provide and improve app functionality, prevent abuse, and support user requests. We do not sell personal data.</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">Cookies and analytics</h2>
            <p style="margin-bottom: 12px;">This landing page is informational. Some browsers or hosting providers may log standard technical data (such as IP address, user-agent, and timestamps) for security and reliability.</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">Third-party services</h2>
            <p style="margin-bottom: 12px;">Our services may be accessed through Telegram. Telegram’s privacy policy applies to their platform. If third-party links are shown, their policies may also apply.</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">Contact</h2>
            <p style="margin-bottom: 0;">For privacy-related questions, contact us via the official Telegram bot.</p>
        </div>

        <div style="margin-top: 14px; display: grid; gap: 10px;">
            <a class="app-btn btn-primary" href="<?php echo htmlspecialchars($siteBase . '/ads/'); ?>">Back to Official Page</a>
        </div>
    </div>
</body>
</html>
