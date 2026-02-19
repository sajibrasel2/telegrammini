<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/bot.php';

// This script runs the bot in polling mode for LOCAL testing.
// Run: php C:\xampp\htdocs\telegrammini\bot_polling.php

set_time_limit(0);
ini_set('memory_limit', '256M');

$bot = new PCNCoinBot();

$offsetFile = __DIR__ . '/polling_offset.txt';
$offset = 0;
if (file_exists($offsetFile)) {
    $offset = (int)trim((string)file_get_contents($offsetFile));
}

echo "Polling started...\n";

while (true) {
    $updates = $bot->getUpdates($offset, 50);

    if ($updates && isset($updates['ok']) && $updates['ok'] && !empty($updates['result'])) {
        foreach ($updates['result'] as $update) {
            $updateId = $update['update_id'] ?? null;
            if ($updateId !== null) {
                $offset = $updateId + 1;
            }

            if (isset($update['message'])) {
                $bot->processMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $bot->handleUpdate($update);
            }
        }

        file_put_contents($offsetFile, (string)$offset);
    }

    usleep(500000); // 0.5s
}
