<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bot.php';

header('Content-Type: application/json');

$bot = new PCNCoinBot();

$deleted = $bot->deleteWebhook();
$info = $bot->getWebhookInfo();

echo json_encode([
    'delete_response' => $deleted,
    'webhook_info' => $info
], JSON_PRETTY_PRINT);
