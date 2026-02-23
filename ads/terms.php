<?php
$siteBase = 'https://techandclick.site/telegrammini';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms - PCN Official</title>
    <meta name="robots" content="index,follow" />
    <link rel="canonical" href="<?php echo htmlspecialchars($siteBase . '/ads/terms.php'); ?>" />
    <?php include __DIR__ . '/../app_style.php'; ?>
</head>
<body>
    <div class="app-container">
        <div class="app-header">
            <h1 style="font-size: 1.2rem;">Terms</h1>
            <p style="color: var(--text-dim);">PCN Official</p>
        </div>

        <div class="app-card" style="line-height: 1.6; color: var(--text-dim); font-size: 0.92rem;">
            <p style="margin-bottom: 12px;">By accessing the PCN Official bot, mini app, or related pages, you agree to the following terms.</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">Service description</h2>
            <p style="margin-bottom: 12px;">PCN Official provides community information, optional activities, and educational content. Participation is voluntary.</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">No guarantee</h2>
            <p style="margin-bottom: 12px;">We do not guarantee earnings, profits, or outcomes. Any points or rewards (if available) depend on user activity, eligibility rules, and system availability.</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">Acceptable use</h2>
            <p style="margin-bottom: 12px;">You agree not to abuse the service, attempt to exploit vulnerabilities, or use automated tools to gain unfair advantages.</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">Availability</h2>
            <p style="margin-bottom: 12px;">The service may be updated, paused, or changed at any time for maintenance, security, or operational reasons.</p>

            <h2 style="font-size: 1rem; color: var(--text); margin-bottom: 8px;">Contact</h2>
            <p style="margin-bottom: 0;">For questions, contact us via the official Telegram bot.</p>
        </div>

        <div style="margin-top: 14px; display: grid; gap: 10px;">
            <a class="app-btn btn-primary" href="<?php echo htmlspecialchars($siteBase . '/ads/'); ?>">Back to Official Page</a>
        </div>
    </div>
</body>
</html>
