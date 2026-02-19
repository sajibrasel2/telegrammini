<?php
require_once 'config.php';
require_once 'bot.php';

header('Content-Type: application/json');

$bot = new PCNCoinBot();

// 1. Reset Webhook
$bot->deleteWebhook();
$response = $bot->setWebhook(WEBHOOK_URL);

// 2. Get Info
$info = $bot->getWebhookInfo();

echo json_encode([
    'setup_response' => $response,
    'current_info' => $info,
    'target_url' => WEBHOOK_URL,
    'bot_token_preview' => substr(BOT_TOKEN, 0, 10) . '...'
], JSON_PRETTY_PRINT);
?>
