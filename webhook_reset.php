<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'bot.php';

$bot = new PCNCoinBot();

// Get current webhook info
$info = $bot->getWebhookInfo();
echo "<h2>Current Webhook Info:</h2>";
echo "<pre>";
print_r($info);
echo "</pre>";

// Delete the webhook
$deleteResult = $bot->deleteWebhook();
echo "<h2>Delete Webhook Result:</h2>";
echo "<pre>";
print_r($deleteResult);
echo "</pre>";

// Set the webhook again
$setResult = $bot->setWebhook(WEBHOOK_URL);
echo "<h2>Set Webhook Result:</h2>";
echo "<pre>";
print_r($setResult);
echo "</pre>";

if ($setResult && $setResult['ok']) {
    echo "<h1>Webhook has been reset successfully!</h1>";
} else {
    echo "<h1>Error: Failed to reset webhook.</h1>";
}
