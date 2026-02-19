<?php
require_once 'config.php';
require_once 'database.php';

class PCNCoinBot {
    private $token;
    private $adminUserId;
    private $db;
    
    public function __construct() {
        if (!defined('BOT_TOKEN')) {
            require_once __DIR__ . '/config.php';
        }
        $this->token = BOT_TOKEN;
        $this->adminUserId = defined('ADMIN_USER_ID') ? ADMIN_USER_ID : null;
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
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            $this->log("cURL Error: {$curlError}");
            return false;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            $this->log("Invalid JSON Response. HTTP: {$httpCode}, Response: {$response}");
            return false;
        }

        if ($httpCode !== 200) {
            $this->log("HTTP Error: $httpCode, Response: $response");
            $decoded['_http_code'] = $httpCode;
        }

        return $decoded;
    }
    
    // Generate referral link
    private function generateReferralLink($userId) {
        return "https://t.me/" . BOT_NAME . "?start=ref" . $userId;
    }
    
    // Process incoming message
    public function processMessage($message) {
        // Ensure we have a valid message structure
        if (!isset($message['chat']['id']) || !isset($message['from']['id'])) {
            return;
        }

        $chatId = $message['chat']['id'];
        $userId = $message['from']['id'];
        $firstName = $message['from']['first_name'] ?? 'User';
        $lastName = $message['from']['last_name'] ?? '';
        $username = $message['from']['username'] ?? ($firstName . ($lastName ? ' ' . $lastName : ''));
        $raw_text = isset($message['text']) ? trim($message['text']) : '';
        
        $text = explode('@', $raw_text)[0];
        $chatType = $message['chat']['type'] ?? 'private';

        // Get user from DB
        $user = $this->db->getUser($userId, $username);

        // Check if user is admin
        if ($userId == ADMIN_USER_ID || (isset($user['is_admin']) && $user['is_admin'])) {
            return $this->handleAdminMessage($chatId, $text, $userId);
        } else {
            return $this->handleUserMessage($chatId, $text, $userId, $username);
        }
    }
    
    // Handle admin messages
    private function handleAdminMessage($chatId, $text, $userId) {
        // In groups, admins might chat too. Only process commands.
        if (substr($text, 0, 1) !== '/') {
            return; // Ignore non-command text from admin
        }

        // Handle /start command for admin
        if (strtolower($text) === '/start') {
            return $this->handleUserMessage($chatId, $text, $userId, 'Admin');
        }
        $response = "";
        $command = strtolower(explode(' ', $text)[0]);

        // Allow admin to use regular user commands
        $userCommands = [
            '/balance',
            '/referral',
            '/checkin',
            '/withdraw',
            '/subscription',
            '/webapp',
            '/stats',
            '/help'
        ];
        if (in_array($command, $userCommands, true)) {
            return $this->handleUserMessage($chatId, $text, $userId, 'Admin');
        }

        // Commands with parameters that need special handling
        if (strpos(strtolower($text), '/setpaid') === 0) {
            $parts = explode(' ', $text);
            if (count($parts) < 2 || !is_numeric($parts[1])) {
                $response = "Invalid command. Usage: /setpaid [user_id]";
            } else {
                $targetUserId = $parts[1];
                if ($this->db->upgradeToPaid($targetUserId)) {
                    $response = "✅ User {$targetUserId} has been successfully upgraded to Paid.";
                    $this->sendMessage($targetUserId, "🎉 Congratulations! An admin has upgraded your account to the Paid plan.");
                } else {
                    $response = "❌ Failed to upgrade user {$targetUserId}. They may already be a paid user or do not exist.";
                }
            }
            return $this->sendMessage($chatId, $response);

        } elseif (strpos(strtolower($text), '/setfree') === 0) {
            $parts = explode(' ', $text);
            if (count($parts) < 2 || !is_numeric($parts[1])) {
                $response = "Invalid command. Usage: /setfree [user_id]";
            } else {
                $targetUserId = $parts[1];
                if ($this->db->downgradeToFree($targetUserId)) {
                    $response = "✅ User {$targetUserId} has been successfully downgraded to Free.";
                    $this->sendMessage($targetUserId, "An admin has set your account to the Free plan.");
                } else {
                    $response = "❌ Failed to downgrade user {$targetUserId}.";
                }
            }
            return $this->sendMessage($chatId, $response);

        } elseif (strpos(strtolower($text), '/listusers') === 0) {
            $parts = explode(' ', $text);
            $page = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : 1;
            $limit = 10; // Users per page

            $users = $this->db->getUsersWithPagination($page, $limit);
            $stats = $this->db->getTotalStats();
            $totalUsers = $stats['total_users'];
            $totalPages = ceil($totalUsers / $limit);

            $response = "🛡️ <b>PCN Coin Admin Panel</b>\n\n";
            if (empty($users)) {
                $response .= "No users found on this page.";
            } else {
                $response .= "👥 <b>User List</b> (Page {$page}/{$totalPages})\n\n";
                foreach ($users as $user) {
                    $response .= "<b>ID:</b> <code>{$user['id']}</code>\n";
                    $response .= "<b>User:</b> @" . htmlspecialchars($user['username'] ?: 'N/A') . "\n";
                    $response .= "<b>Plan:</b> " . ucfirst($user['subscription_type']) . "\n";
                    $response .= "<b>Balance:</b> " . number_format($user['balance'], 2) . " PCN\n";
                    $response .= "<b>Joined:</b> " . date('Y-m-d', strtotime($user['joined_date'])) . "\n";
                    $response .= "--------------------\n";
                }
                if ($page < $totalPages) {
                    $nextPage = $page + 1;
                    $response .= "\nTo see the next page, use: <code>/listusers {$nextPage}</code>";
                }
            }
            return $this->sendMessage($chatId, $response);

        } elseif (strpos(strtolower($text), '/broadcast') === 0) {
            $messageText = trim(substr($text, 10));
            if (empty($messageText)) {
                return $this->sendMessage($chatId, "Please provide a message to broadcast. Usage: /broadcast [message]");
            }
            $allUsers = $this->db->getAllUsers();
            $count = 0;
            foreach ($allUsers as $user) {
                if ($this->sendMessage($user['id'], $messageText)) {
                    $count++;
                }
            }
            return $this->sendMessage($chatId, "Broadcast sent to $count users.");

        } elseif (strpos(strtolower($text), '/approve') === 0) {
            $parts = explode(' ', $text);
            if (count($parts) < 2) {
                return $this->sendMessage($chatId, "Invalid command. Usage: /approve [payment_id]");
            }
            $paymentId = $parts[1];
            $approvedUserId = $this->db->approvePayment($paymentId);
            if ($approvedUserId) {
                $this->sendMessage($approvedUserId, "🎉 Your payment has been approved and your account is now upgraded to Paid!");
                return $this->sendMessage($chatId, "Payment $paymentId has been approved.");
            } else {
                return $this->sendMessage($chatId, "Failed to approve payment $paymentId. It might not exist or is already processed.");
            }

        } elseif (strpos(strtolower($text), '/reject') === 0) {
            $parts = explode(' ', $text);
            if (count($parts) < 2) {
                return $this->sendMessage($chatId, "Invalid command. Usage: /reject [payment_id] [reason]");
            }
            $paymentId = $parts[1];
            $reason = count($parts) > 2 ? implode(' ', array_slice($parts, 2)) : 'No reason provided.';
            if ($this->db->rejectPayment($paymentId, $reason)) {
                $payment = $this->db->getPayment($paymentId);
                if ($payment) {
                    $this->sendMessage($payment['user_id'], "Your payment has been rejected. Reason: $reason");
                }
                return $this->sendMessage($chatId, "Payment $paymentId has been rejected.");
            } else {
                return $this->sendMessage($chatId, "Failed to reject payment $paymentId.");
            }
        }

        // Switch for simple commands without parameters
        $response = "🛡️ <b>PCN Coin Admin Panel</b>\n\n";
        switch ($command) {
            case '/start':
                $response .= "Welcome to PCN Coin Admin Panel!\n\n";
                $response .= "📋 <b>Available Commands:</b>\n";
                $response .= "/status - Bot status\n";
                $response .= "/users - User statistics\n";
                $response .= "/broadcast [message] - Send message to all users\n";
                $response .= "/pending - View pending payments\n";
                $response .= "/approve [payment_id] - Approve a payment\n";
                $response .= "/reject [payment_id] [reason] - Reject a payment\n";
                $response .= "/setpaid [user_id] - Upgrade a user to paid\n";
                $response .= "/setfree [user_id] - Downgrade a user to free\n";
                $response .= "/listusers [page] - List all users\n";
                break;
                
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
                $subscriptionCounts = $this->db->getUserSubscriptionCounts();

                $response .= "📊 <b>User Statistics</b>\n\n";
                $response .= "👥 <b>Total Users:</b> {$stats['total_users']}\n";
                $response .= "   - 🆓 <b>Free Users:</b> {$subscriptionCounts['free']}\n";
                $response .= "   - 💳 <b>Paid Users:</b> {$subscriptionCounts['paid']}\n";
                $response .= "\n";
                $response .= "💰 <b>Total Coins in Circulation:</b> " . number_format($stats['total_balance'], 2) . " PCN\n";
                $response .= "   - 📈 <b>Total Earned:</b> " . number_format($stats['total_earned'], 2) . " PCN\n";
                $response .= "   - 📉 <b>Total Withdrawn:</b> " . number_format($stats['total_withdrawn'], 2) . " PCN\n";
                break;

            case '/pending':
                $pendingPayments = $this->db->getPendingPayments();
                if (empty($pendingPayments)) {
                    $response .= "No pending payments found.";
                } else {
                    $response .= "⏳ <b>Pending Payments</b>\n\n";
                    foreach ($pendingPayments as $payment) {
                        $response .= "<b>ID:</b> <code>{$payment['payment_id']}</code>\n";
                        $response .= "<b>User ID:</b> <code>{$payment['user_id']}</code>\n";
                        $response .= "<b>Amount:</b> {$payment['amount']} {$payment['currency']}\n";
                        $response .= "<b>Date:</b> {$payment['created_date']}\n";
                        $response .= "--------------------\n";
                    }
                }
                break;
                
            default:
                $response .= "❓ <b>Unknown Command</b>\n\n";
                $response .= "You sent: <code>$text</code>\n\n";
                $response .= "Type /start to see the list of available commands.";
                break;
        }
        
        return $this->sendMessage($chatId, $response);
    }

    // Handle regular user messages
    private function handleUserMessage($chatId, $text, $userId, $username) {
        $user = $this->db->getUser($userId, $username);

        // Handle /start command and referrals
        if (strpos($text, '/start') === 0) {
            // Check for referral parameter
            $parts = explode(' ', $text, 2); // Limit to 2 parts: /start and the rest
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

            // Always send the welcome message for any /start command
            $response = "<b>Welcome to PCN Coin!</b>\n\n";
            $response .= "PCN is a next-generation cryptocurrency designed for fast, secure, and borderless transactions. Join the revolution and grow your wealth with the power of blockchain and community-driven rewards.";
            $response .= "\n\n";
            // User's referral link
            $referralLink = $this->generateReferralLink($userId);
            $response .= "<b>Your Referral Link:</b>\n<code>$referralLink</code>\n\n";
            // Inline Open App button
            $webAppUrl = WEB_APP_URL . "?user_id=" . $userId;
            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🌐 Open App', 'web_app' => ['url' => $webAppUrl]]
                    ]
                ]
            ];
            return $this->sendMessage($chatId, $response, 'HTML', $inlineKeyboard);
        }

        // Handle other commands
        $command = strtolower(explode(' ', $text)[0]);
        switch ($command) {
                
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
                    $paymentUrl = rtrim(dirname(WEB_APP_URL), '/\\') . '/payment.php?user_id=' . urlencode((string)$userId);
                    $response .= "Visit: {$paymentUrl}\n\n";
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
                
                // Construct the web app URL with the user's ID
                $webAppUrl = WEB_APP_URL . "?user_id=" . $userId;
                
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
    
    public function handleUpdate($update) { }

    // Log messages to a file
    private function log($message) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $timestamp = date('Y-m-d H:i:s');
            $logMessage = "[{$timestamp}] - {$message}\n";
            file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);
        }
    }
}

// This block will only run when bot.php is executed directly, not when included
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    // Initialize bot
    $bot = new PCNCoinBot();

    // Handle webhook requests
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Webhook mode
    $input = file_get_contents('php://input');
    
    // Log incoming update for debugging
    file_put_contents(__DIR__ . '/bot_debug.txt', date('[Y-m-d H:i:s] ') . "Incoming Update: " . $input . "\n", FILE_APPEND);
    
    $data = json_decode($input, true);
    
    if (isset($data['message'])) {
        $bot->processMessage($data['message']);
    } elseif (isset($data['callback_query'])) {
        // Handle callback queries if you have inline buttons
        $bot->handleUpdate($data);
    } else {
        $bot->log("Received a non-message and non-callback update: " . $input);
    }
} else {
    // When the script is accessed via a browser (GET request)
    echo "Bot is running in webhook mode. It only responds to POST requests from Telegram.";
}
}
?>