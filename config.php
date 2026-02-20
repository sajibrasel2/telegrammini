<?php
// Load sensitive configuration
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
} else {
    die('Error: env.php not found. Please create it from env.example.php');
}

// Bot settings
define('BOT_NAME', 'PCN_OfficialBot');

$__pcnHost = $_SERVER['HTTP_HOST'] ?? '';
$__pcnIsLocal = (php_sapi_name() === 'cli') || $__pcnHost === 'localhost' || substr($__pcnHost, 0, 10) === 'localhost:' || $__pcnHost === '127.0.0.1' || substr($__pcnHost, 0, 10) === '127.0.0.1:';

define('DB_AUTO_SETUP', $__pcnIsLocal);

if ($__pcnIsLocal) {
    define('WEBHOOK_URL', 'http://localhost/telegrammini/bot.php');
    define('WEB_APP_URL', 'http://localhost/telegrammini/index.php'); // Main web app URL
} else {
    define('WEBHOOK_URL', 'https://techandclick.site/telegrammini/bot.php');
    define('WEB_APP_URL', 'https://techandclick.site/telegrammini/index.php'); // Main web app URL
}

// Basic validation
if (!defined('BOT_TOKEN') || BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE') {
    die('Error: Bot token is not configured. Please set it in env.php');
}

// PCN Coin settings
define('PCN_COIN_NAME', 'PCN Coin');
define('PCN_COIN_SYMBOL', 'PCN');
define('REFERRAL_BONUS', '10'); // PCN coins per referral
define('MIN_WITHDRAWAL', '100'); // Minimum withdrawal amount
define('WALLET_ADDRESS', 'UQByJO2ANNla0RjAUTrXEKc25lC2HyS30TbnoauAQKEv4CVp');

// Logging
define('LOG_FILE', __DIR__ . '/pcn_bot_log.txt');
define('DEBUG_MODE', true);
?>