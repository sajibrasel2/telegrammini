<?php
require_once 'config.php';
require_once 'database.php';

class PCNCoinBot {
    private $token;
    private $adminUserId;
    private $db;
    
    public function __construct() {
        $this->token = BOT_TOKEN;
        $this->adminUserId = ADMIN_USER_ID;
        $this->db = new Database();
    }
    
    // Send message to Telegram
    public function sendMessage($chatId, $message, $parseMode = 'HTML', $replyMarkup = null) {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => $parseMode
        ];
        
        if ($replyMarkup) {
            $data['reply_markup'] = json_encode($replyMarkup);
        }
        
        return $this->makeRequest($url, $data);
    }
    
    // Get bot info
    public function getMe() {
        $url = "https://api.telegram.org/bot{$this->token}/getMe";
        return $this->makeRequest($url);
    }
    
    // Get updates (for polling)
    public function getUpdates($offset = null, $limit = 100) {
        $url = "https://api.telegram.org/bot{$this->token}/getUpdates";
        $data = [];
        if ($offset !== null) {
            $data['offset'] = $offset;
        }
        $data['limit'] = $limit;
        
        return $this->makeRequest($url, $data);
    }
    
    // Set webhook
    public function setWebhook($url) {
        $apiUrl = "https://api.telegram.org/bot{$this->token}/setWebhook";
        $data = ['url' => $url];
        return $this->makeRequest($apiUrl, $data);
    }
    
    // Delete webhook
    public function deleteWebhook() {
        $url = "https://api.telegram.org/bot{$this->token}/deleteWebhook";
        return $this->makeRequest($url);
    }
    
    // Get webhook info
    public function getWebhookInfo() {
        $url = "https://api.telegram.org/bot{$this->token}/getWebhookInfo";
        return $this->makeRequest($url);
    }
    
    // Make HTTP request
    public function makeRequest($url, $data = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $this->log("HTTP Error: $httpCode, Response: $response");
            return false;
        }
        
        return json_decode($response, true);
    }
    
    // Generate referral link
    private function generateReferralLink($userId) {
        return "https://t.me/" . BOT_NAME . "?start=ref" . $userId;
    }
    
    // Process incoming message
    public function processMessage($message) {
        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'];
        $username = $message['from']['username'] ?? '';
        $text = $message['text'] ?? '';
        
        $this->log("Received message from $username ($userId): $text");
        
        // Check if user is admin
        if ($userId == $this->adminUserId || $username == $this->adminUserId) {
            return $this->handleAdminMessage($chatId, $text, $userId);
        } else {
            return $this->handleUserMessage($chatId, $text, $userId, $username);
        }
    }
    
    // Handle admin messages
    private function handleAdminMessage($chatId, $text, $userId) {
        $response = "🛡️ <b>PCN Coin Admin Panel</b>\n\n";
        
        switch (strtolower($text)) {
            case '/start':
                $response .= "Welcome to PCN Coin Admin Panel!\n\n";
                $response .= "📋 <b>Available Commands:</b>\n";
                $response .= "/status - Bot status\n";
                $response .= "/users - User statistics\n";
                $response .= "/broadcast - Send message to all users\n";
                $response .= "/help - Admin help\n";
                $response .= "/stats - Referral statistics\n\n";
                $response .= "🌐 <b>Web App:</b>\n";
                $response .= "Click the button below to open the Mini App!";
                
                // WebApp keyboard for admin (inline keyboard for better compatibility)
                $webAppUrl = "http://localhost/telegram/index.php";
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🌐 Open Web App',
                                'web_app' => ['url' => $webAppUrl]
                            ]
                        ]
                    ]
                ];
                
                return $this->sendMessage($chatId, $response, 'HTML', $inlineKeyboard);
                
            case '/status':
                $botInfo = $this->getMe();
                if ($botInfo && $botInfo['ok']) {
                    $bot = $botInfo['result'];
                    $stats = $this->db->getTotalStats();
                    $response .= "🤖 <b>PCN Coin Bot Status:</b>\n";
                    $response .= "Name: {$bot['first_name']}\n";
                    $response .= "Username: @{$bot['username']}\n";
                    $response .= "Status: ✅ Active\n";
                    $response .= "Total Users: " . $stats['total_users'] . "\n";
                    $response .= "Active Users: " . $stats['active_users'] . "\n";
                } else {
                    $response .= "❌ Bot status check failed";
                }
                break;
                
            case '/users':
                $stats = $this->db->getTotalStats();
                $response .= "👥 <b>User Statistics:</b>\n";
                $response .= "Total users: " . $stats['total_users'] . "\n";
                $response .= "Active users: " . $stats['active_users'] . "\n";
                $response .= "Total balance: " . $stats['total_balance'] . " PCN\n";
                $response .= "Total earned: " . $stats['total_earned'] . " PCN\n";
                break;
                
            case '/stats':
                $stats = $this->db->getTotalStats();
                $response .= "📊 <b>Referral Statistics:</b>\n";
                $response .= "Total Users: " . $stats['total_users'] . "\n";
                $response .= "Total PCN Earned: " . $stats['total_earned'] . " PCN\n";
                $response .= "Total Balance: " . $stats['total_balance'] . " PCN\n";
                $response .= "Referral Bonus: " . REFERRAL_BONUS . " PCN per referral\n";
                break;
                
            case '/broadcast':
                $response .= "📢 <b>Broadcast Message</b>\n\n";
                $response .= "Please send the message you want to broadcast to all users.";
                break;
                
            case '/help':
                $response .= "❓ <b>Admin Help</b>\n\n";
                $response .= "You have full control over the PCN Coin referral bot.\n";
                $response .= "Use the commands above to manage the bot.";
                break;
                
            default:
                $response .= "✅ <b>Admin Command Received</b>\n\n";
                $response .= "Your message: <code>$text</code>\n\n";
                $response .= "This is an admin-only response.";
                break;
        }
        
        return $this->sendMessage($chatId, $response);
    }
    
    // Handle regular user messages
    private function handleUserMessage($chatId, $text, $userId, $username) {
        $user = $this->db->getUser($userId, $username);
        
        // Check for referral start parameter
        if (strpos($text, '/start') === 0) {
            $parts = explode(' ', $text);
            if (isset($parts[1]) && strpos($parts[1], 'ref') === 0) {
                $referrerId = substr($parts[1], 3);
                if ($referrerId != $userId) {
                    $referrer = $this->db->getUser($referrerId);
                    if ($referrer) {
                        $bonusAmount = $this->db->addReferral($referrerId, $userId);
                        
                        // Notify referrer
                        if ($bonusAmount) {
                            $this->sendMessage($referrerId, "🎉 <b>New Referral!</b>\n\nYou earned {$bonusAmount} PCN coins from @$username!");
                        }
                    }
                }
            }
        }
        
        $response = "🚀 <b>Welcome to PCN Coin!</b>\n\n";
        
        switch (strtolower($text)) {
            case '/start':
                $response .= "Welcome to PCN Coin Referral Bot!\n\n";
                $response .= "💰 <b>Earn PCN Coins through referrals!</b>\n";
                $response .= "• Get " . REFERRAL_BONUS . " PCN per referral\n";
                $response .= "• Minimum withdrawal: " . MIN_WITHDRAWAL . " PCN\n\n";
                $response .= "📋 <b>Commands:</b>\n";
                $response .= "/balance - Check your balance\n";
                $response .= "/referral - Get your referral link\n";
                $response .= "/checkin - Daily check-in (5 PCN)\n";
                $response .= "/subscription - Upgrade to paid plan\n";
                $response .= "/withdraw - Withdraw PCN coins\n";
                $response .= "/help - Get help\n";
                $response .= "/stats - Your referral stats\n\n";
                $response .= "🌐 <b>Web App:</b>\n";
                $response .= "Click the button below to open the Mini App!";
                
                // WebApp keyboard for regular users (inline keyboard for better compatibility)
                $webAppUrl = "http://localhost/telegram/index.php";
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🌐 Open Web App',
                                'web_app' => ['url' => $webAppUrl]
                            ]
                        ]
                    ]
                ];
                
                return $this->sendMessage($chatId, $response, 'HTML', $inlineKeyboard);
                
            case '/balance':
                $userStats = $this->db->getUserStats($userId);
                $user = $this->db->getUser($userId);
                $canCheckIn = $this->db->canCheckIn($userId);
                $checkInStreak = $this->db->getCheckInStreak($userId);
                
                $response .= "💰 <b>Your PCN Balance</b>\n\n";
                $response .= "Current Balance: <b>{$userStats['balance']} PCN</b>\n";
                $response .= "Total Earned: <b>{$userStats['total_earned']} PCN</b>\n";
                $response .= "Referrals: <b>{$userStats['referral_count']}</b>\n";
                $response .= "Plan: <b>" . ucfirst($user['subscription_type']) . "</b>\n";
                $response .= "Check-in Streak: <b>{$checkInStreak} days</b>\n\n";
                
                if ($canCheckIn) {
                    $response .= "✅ <b>Daily Check-in Available!</b>\n";
                    $response .= "Use /checkin to earn 5 PCN\n\n";
                } else {
                    $response .= "⏰ <b>Daily Check-in Completed</b>\n";
                    $response .= "Come back tomorrow for more coins!\n\n";
                }
                
                if ($userStats['balance'] >= MIN_WITHDRAWAL) {
                    $response .= "✅ You can withdraw your PCN coins!\n";
                    $response .= "Use /withdraw to request withdrawal.";
                } else {
                    $response .= "⚠️ Minimum withdrawal: " . MIN_WITHDRAWAL . " PCN\n";
                    $response .= "Share your referral link to earn more!";
                }
                break;
                
            case '/referral':
                $referralLink = $this->generateReferralLink($userId);
                $userStats = $this->db->getUserStats($userId);
                $user = $this->db->getUser($userId);
                $bonusAmount = ($user['subscription_type'] == 'paid') ? 10 : 3;
                
                $response .= "🔗 <b>Your Referral Link</b>\n\n";
                $response .= "Share this link to earn {$bonusAmount} PCN per referral:\n\n";
                $response .= "<code>$referralLink</code>\n\n";
                $response .= "📊 <b>Your Stats:</b>\n";
                $response .= "• Referrals: {$userStats['referral_count']}\n";
                $response .= "• Earned: {$userStats['total_earned']} PCN\n";
                $response .= "• Balance: {$userStats['balance']} PCN\n";
                $response .= "• Plan: " . ucfirst($user['subscription_type']);
                break;
                
            case '/withdraw':
                $userStats = $this->db->getUserStats($userId);
                if ($userStats['balance'] >= MIN_WITHDRAWAL) {
                    $response .= "💸 <b>Withdrawal Request</b>\n\n";
                    $response .= "Amount: <b>{$userStats['balance']} PCN</b>\n";
                    $response .= "Status: ⏳ Processing\n\n";
                    $response .= "Your withdrawal request has been submitted.\n";
                    $response .= "Admin will process it within 24 hours.\n\n";
                    $response .= "Your balance has been reset to 0.";
                    
                    // Create withdrawal request
                    $this->db->createWithdrawal($userId, $userStats['balance'], '');
                    
                    // Notify admin
                    $this->sendMessage($this->adminUserId, "💰 <b>Withdrawal Request</b>\n\nUser: @$username\nAmount: {$userStats['balance']} PCN\nChat ID: $chatId");
                } else {
                    $response .= "❌ <b>Insufficient Balance</b>\n\n";
                    $response .= "Your balance: <b>{$userStats['balance']} PCN</b>\n";
                    $response .= "Minimum withdrawal: <b>" . MIN_WITHDRAWAL . " PCN</b>\n\n";
                    $response .= "Share your referral link to earn more PCN coins!";
                }
                break;
                
            case '/stats':
                $userStats = $this->db->getUserStats($userId);
                $response .= "📊 <b>Your Referral Statistics</b>\n\n";
                $response .= "Total Referrals: <b>{$userStats['referral_count']}</b>\n";
                $response .= "Total Earned: <b>{$userStats['total_earned']} PCN</b>\n";
                $response .= "Current Balance: <b>{$userStats['balance']} PCN</b>\n";
                $response .= "Joined: <b>{$userStats['joined_date']}</b>\n\n";
                if ($userStats['referral_count'] > 0) {
                    $response .= "🎯 <b>Top Performance!</b>\n";
                    $response .= "Keep sharing your referral link!";
                } else {
                    $response .= "🚀 <b>Start Earning!</b>\n";
                    $response .= "Use /referral to get your link.";
                }
                break;
                
            case '/checkin':
                if ($this->db->canCheckIn($userId)) {
                    if ($this->db->performDailyCheckIn($userId)) {
                        $streak = $this->db->getCheckInStreak($userId);
                        $response .= "✅ <b>Daily Check-in Successful!</b>\n\n";
                        $response .= "You earned <b>5 PCN</b> coins!\n";
                        $response .= "Current streak: <b>{$streak} days</b>\n\n";
                        $response .= "Come back tomorrow for more coins!";
                    } else {
                        $response .= "❌ <b>Check-in Failed</b>\n\n";
                        $response .= "Please try again later.";
                    }
                } else {
                    $response .= "⚠️ <b>Already Checked In</b>\n\n";
                    $response .= "You've already claimed your daily bonus today.\n";
                    $response .= "Come back tomorrow for more coins!";
                }
                break;
                
            case '/subscription':
                $user = $this->db->getUser($userId);
                if ($user['subscription_type'] == 'free') {
                    $response .= "💎 <b>Upgrade to Paid Plan</b>\n\n";
                    $response .= "🎯 <b>Paid Plan Benefits:</b>\n";
                    $response .= "• Instant 500 PCN bonus\n";
                    $response .= "• 10 PCN per direct referral (vs 3 PCN for free)\n";
                    $response .= "• 10-generation multi-level system\n";
                    $response .= "• Priority support\n\n";
                    $response .= "📊 <b>Multi-Level Bonuses:</b>\n";
                    $response .= "Gen 1: 10 PCN | Gen 2: 5 PCN | Gen 3: 3 PCN\n";
                    $response .= "Gen 4: 2 PCN | Gen 5: 1 PCN | Gen 6: 0.5 PCN\n";
                    $response .= "Gen 7: 0.3 PCN | Gen 8: 0.2 PCN | Gen 9: 0.1 PCN | Gen 10: 0.05 PCN\n\n";
                    $response .= "💰 <b>Cost:</b> 0.50 TON\n\n";
                    $response .= "🌐 <b>Upgrade Online:</b>\n";
                    $response .= "Visit: http://localhost/telegram/payment.php\n\n";
                    $response .= "📝 <b>Payment Process:</b>\n";
                    $response .= "1. Enter your Telegram ID/Username\n";
                    $response .= "2. Get unique Payment ID & Memo\n";
                    $response .= "3. Send 0.50 TON to wallet\n";
                    $response .= "4. Submit transaction ID\n";
                    $response .= "5. Admin will verify within 24h\n\n";
                    $response .= "Or contact admin: @sajibrasel2";
                } else {
                    $response .= "✅ <b>Already Premium User</b>\n\n";
                    $response .= "You're already enjoying paid plan benefits!\n";
                    $response .= "• 10 PCN per direct referral\n";
                    $response .= "• 10-generation multi-level system\n";
                    $response .= "• Priority support\n\n";
                    $response .= "📊 <b>Your Multi-Level Bonuses:</b>\n";
                    $response .= "Gen 1: 10 PCN | Gen 2: 5 PCN | Gen 3: 3 PCN\n";
                    $response .= "Gen 4: 2 PCN | Gen 5: 1 PCN | Gen 6: 0.5 PCN\n";
                    $response .= "Gen 7: 0.3 PCN | Gen 8: 0.2 PCN | Gen 9: 0.1 PCN | Gen 10: 0.05 PCN";
                }
                break;
                
            case '/help':
                $response .= "❓ <b>PCN Coin Help</b>\n\n";
                $response .= "💰 <b>How to earn PCN coins:</b>\n";
                $response .= "1. Daily check-in (/checkin) - 5 PCN daily\n";
                $response .= "2. Get your referral link (/referral)\n";
                $response .= "3. Share it with friends\n";
                $response .= "4. Earn PCN per referral (3 for free, 10 for paid)\n";
                $response .= "5. Paid users get 10-generation bonuses\n";
                $response .= "6. Upgrade to paid plan for more benefits\n";
                $response .= "7. Withdraw when you reach " . MIN_WITHDRAWAL . " PCN\n\n";
                $response .= "📊 <b>Paid Plan Multi-Level:</b>\n";
                $response .= "Gen 1: 10 PCN | Gen 2: 5 PCN | Gen 3: 3 PCN\n";
                $response .= "Gen 4: 2 PCN | Gen 5: 1 PCN | Gen 6: 0.5 PCN\n";
                $response .= "Gen 7: 0.3 PCN | Gen 8: 0.2 PCN | Gen 9: 0.1 PCN | Gen 10: 0.05 PCN\n\n";
                $response .= "📋 <b>Commands:</b>\n";
                $response .= "/start - Start the bot\n";
                $response .= "/balance - Check balance\n";
                $response .= "/checkin - Daily check-in\n";
                $response .= "/subscription - Upgrade to paid\n";
                $response .= "/referral - Get referral link\n";
                $response .= "/withdraw - Withdraw PCN\n";
                $response .= "/stats - Your statistics\n";
                $response .= "/webapp - Open Web App\n";
                $response .= "/help - This help message";
                break;
                
            case '/webapp':
            case '/app':
                $response .= "🌐 <b>PCN Coin Web App</b>\n\n";
                $response .= "Click the button below to open the Mini App!\n";
                $response .= "Experience our full web interface inside Telegram.";
                
                $webAppUrl = "http://localhost/telegram/index.php";
                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🌐 Open Web App',
                                'web_app' => ['url' => $webAppUrl]
                            ]
                        ]
                    ]
                ];
                
                return $this->sendMessage($chatId, $response, 'HTML', $inlineKeyboard);
                
            default:
                $response .= "💬 <b>Message Received</b>\n\n";
                $response .= "Your message: <code>$text</code>\n\n";
                $response .= "Use /help to see available commands.";
                break;
        }
        
        return $this->sendMessage($chatId, $response);
    }
    
    // Log messages
    private function log($message) {
        if (DEBUG_MODE) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[$timestamp] $message\n";
            file_put_contents(LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
        }
    }
}

// Initialize bot
$bot = new PCNCoinBot();

// Handle webhook or polling
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Webhook mode
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (isset($data['message'])) {
        $bot->processMessage($data['message']);
    }
} else {
    // Polling mode (for testing)
    $updates = $bot->getUpdates();
    if ($updates && $updates['ok']) {
        foreach ($updates['result'] as $update) {
            if (isset($update['message'])) {
                $bot->processMessage($update['message']);
            }
        }
    }
}
?> 