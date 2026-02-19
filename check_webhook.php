<?php
require_once 'C:/xampp/htdocs/telegrammini/config.php';
require_once 'C:/xampp/htdocs/telegrammini/bot.php';

$bot = new PCNCoinBot();
$info = $bot->getWebhookInfo();
echo json_encode($info, JSON_PRETTY_PRINT);
?>
