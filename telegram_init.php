<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$initData = $_POST['initData'] ?? '';
if (!$initData) {
    @file_put_contents(__DIR__ . '/init_debug.txt', date('c') . " initData missing host=" . ($_SERVER['HTTP_HOST'] ?? '') . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'initData missing']);
    exit;
}

$platform = strtolower((string)($_POST['platform'] ?? ''));
if (!in_array($platform, ['android', 'ios'], true)) {
    @file_put_contents(__DIR__ . '/init_debug.txt', date('c') . " platform blocked platform=" . $platform . " host=" . ($_SERVER['HTTP_HOST'] ?? '') . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'This Mini App is allowed only on Telegram mobile (Android/iOS).']);
    exit;
}

 $debug = [];
if (!Security::verifyTelegramWebAppData($initData, $debug)) {
    $tokenSuffix = defined('BOT_TOKEN') ? substr((string)BOT_TOKEN, -6) : 'no_token';
    $recv = isset($debug['received_hash']) ? substr((string)$debug['received_hash'], 0, 12) : 'no_recv';
    $calc = isset($debug['calculated_hash']) ? substr((string)$debug['calculated_hash'], 0, 12) : 'no_calc';
    $dcs  = isset($debug['data_check_string']) ? substr((string)$debug['data_check_string'], 0, 120) : 'no_dcs';
    $matchedStrategy = isset($debug['matched_strategy']) ? (string)$debug['matched_strategy'] : 'no_strategy';
    $matchedKey = isset($debug['matched_key']) ? (string)$debug['matched_key'] : 'no_key';
    @file_put_contents(
        __DIR__ . '/init_debug.txt',
        date('c') . " initData verification failed host=" . ($_SERVER['HTTP_HOST'] ?? '') .
        " platform=" . $platform .
        " token_suffix=" . $tokenSuffix .
        " matched_strategy=" . $matchedStrategy .
        " matched_key=" . $matchedKey .
        " recv_hash_prefix=" . $recv .
        " calc_hash_prefix=" . $calc .
        " dcs_prefix=" . str_replace(["\n", "\r"], ['\\n', ''], $dcs) .
        " initData_prefix=" . substr($initData, 0, 200) .
        "\n",
        FILE_APPEND
    );
    echo json_encode(['success' => false, 'message' => 'initData verification failed']);
    exit;
}

parse_str($initData, $data);
$userJson = $data['user'] ?? null;
$user = $userJson ? json_decode($userJson, true) : null;

$_SESSION['tg_verified'] = true;
$_SESSION['tg_verified_at'] = time();
$_SESSION['tg_user_id'] = $user['id'] ?? null;
$_SESSION['tg_platform'] = $platform;

echo json_encode(['success' => true]);
