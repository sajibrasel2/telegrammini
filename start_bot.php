<?php
require_once 'config.php';
require_once 'bot.php';

echo "Starting PCN Coin Bot in polling mode...\n";
echo "Press Ctrl+C to stop the bot\n\n";

$bot = new PCNCoinBot();
$offset = 0;

while (true) {
    try {
        // Get updates from Telegram
        $updates = $bot->getUpdates($offset, 10);
        
        if ($updates && $updates['ok'] && !empty($updates['result'])) {
            foreach ($updates['result'] as $update) {
                if (isset($update['message'])) {
                    $bot->processMessage($update['message']);
                }
                $offset = $update['update_id'] + 1;
            }
        }
        
        // Sleep for 1 second before next poll
        sleep(1);
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        sleep(5); // Wait 5 seconds before retrying
    }
}
?> 