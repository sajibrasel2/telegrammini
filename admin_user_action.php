<?php
require_once 'config.php';
require_once 'database.php';
require_once 'security_helper.php';
require_once 'admin_auth.php';

$db = new Database();

if (!admin_is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['action'])) {
    $targetUserId = $_POST['user_id'];
    $action = $_POST['action'];
    $success = false;
    $message = "";

    if ($action === 'upgrade') {
        if ($db->upgradeToPaid($targetUserId)) {
            $success = true;
            $message = "User upgraded to Premium successfully!";
            $db->sendTelegramMessage($targetUserId, "🌟 <b>Congratulations!</b>\n\nYour account has been upgraded to <b>PREMIUM</b> by an administrator. Enjoy 2x mining speed and 10-level referral rewards!");
        } else {
            $message = "User is already Premium or update failed.";
        }
    } elseif ($action === 'downgrade') {
        if ($db->downgradeToFree($targetUserId)) {
            $success = true;
            $message = "User downgraded to Free successfully!";
            $db->sendTelegramMessage($targetUserId, "⚠️ <b>Account Update</b>\n\nYour account has been changed to the <b>FREE</b> plan by an administrator.");
        } else {
            $message = "User is already on Free plan or update failed.";
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

die("Invalid request.");
?>
