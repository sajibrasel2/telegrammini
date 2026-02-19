<?php
require_once 'config.php';
require_once 'bot.php';

$bot = new PCNCoinBot();
$webhookUrl = WEBHOOK_URL;

echo "<h2>Telegram Webhook Setup</h2>";
echo "Attempting to set webhook to: <b>$webhookUrl</b><br><br>";

$response = $bot->setWebhook($webhookUrl);

if ($response && $response['ok']) {
    echo "<span style='color: green;'>✅ Webhook set successfully!</span><br>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<span style='color: red;'>❌ Failed to set webhook.</span><br>";
    echo "Check if your BOT_TOKEN is correct in env.php and if the URL is HTTPS.<br>";
    echo "<pre>" . json_encode($response, JSON_PRETTY_PRINT) . "</pre>";
}

echo "<br><hr>";
echo "<h3>Current Webhook Info:</h3>";
$info = $bot->getWebhookInfo();
echo "<pre>" . json_encode($info, JSON_PRETTY_PRINT) . "</pre>";
?>
