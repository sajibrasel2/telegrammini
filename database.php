<?php
require_once 'config.php';

class Database {
    private $connection;
    
    public function __construct() {
        $this->connect();
        $autoSetup = defined('DB_AUTO_SETUP') ? (bool)DB_AUTO_SETUP : false;
        if ($autoSetup) {
            $this->createTables();
            if (defined('ADMIN_USER_ID')) {
                $this->setAdmin(ADMIN_USER_ID);
            }
        }
    }

    public function getTaskById($taskId)
    {
        $stmt = $this->connection->prepare("SELECT * FROM tasks WHERE id = ? LIMIT 1");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        return $task ?: null;
    }

    // Set a user as admin
    public function setAdmin($userId) {
        $stmt = $this->connection->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function getTodaysCompletedTasks($userId)
    {
        $stmt = $this->connection->prepare("SELECT task_id FROM user_tasks WHERE user_id = ? AND completed_at >= (NOW() - INTERVAL 24 HOUR)");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function completeTask($userId, $taskId, $reward)
    {
        // First, check if the task is already completed within last 24 hours
        $stmt = $this->connection->prepare("SELECT id FROM user_tasks WHERE user_id = ? AND task_id = ? AND completed_at >= (NOW() - INTERVAL 24 HOUR)");
        $stmt->execute([$userId, $taskId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Task already completed in the last 24 hours.'];
        }

        $this->connection->beginTransaction();

        try {
            // Add the task to the completed list
            $stmt = $this->connection->prepare("INSERT INTO user_tasks (user_id, task_id, completed_at) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $taskId]);

            // Update user's balance
            $this->updateBalance($userId, $reward, 'task_reward');

            $this->connection->commit();

            $user = $this->getUser($userId);
            return ['success' => true, 'message' => 'Task completed! ' . $reward . ' PCN added.', 'new_balance' => $user['balance']];
        } catch (Exception $e) {
            $this->connection->rollBack();
            error_log('Task completion failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred. Please try again.'];
        }
    }
    
    // Connect to MySQL database
    private function connect() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    // Create database tables
    private function createTables() {
        // Users table
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id BIGINT PRIMARY KEY,
            username VARCHAR(255),
            balance DECIMAL(10,2) DEFAULT 0,
            total_earned DECIMAL(10,2) DEFAULT 0,
            referrer_id BIGINT NULL,
            subscription_type ENUM('free', 'paid') DEFAULT 'free',
            subscription_date DATETIME NULL,
            joined_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (referrer_id),
            INDEX (username),
            INDEX (subscription_type)
        )";
        $this->connection->exec($sql);

        // User tasks (task claim history)
        $sql = "CREATE TABLE IF NOT EXISTS user_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            task_id INT NOT NULL,
            completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (task_id),
            INDEX (completed_at),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        $this->connection->exec($sql);

        // Add is_admin column to users table if it doesn't exist
        try {
            $this->connection->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN NOT NULL DEFAULT FALSE");
        } catch (PDOException $e) {
            // Ignore error if column already exists
        }

        // Add wallet_address column to users table if it doesn't exist
        try {
            $this->connection->exec("ALTER TABLE users ADD COLUMN wallet_address VARCHAR(255) NULL");
        } catch (PDOException $e) {
            // Ignore error if column already exists
        }
        
        // Referrals table
        $sql = "CREATE TABLE IF NOT EXISTS referrals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referrer_id BIGINT,
            referred_id BIGINT,
            bonus_amount DECIMAL(10,2) DEFAULT 10,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (referrer_id) REFERENCES users(id),
            FOREIGN KEY (referred_id) REFERENCES users(id),
            UNIQUE KEY unique_referral (referrer_id, referred_id)
        )";
        $this->connection->exec($sql);
        
        // Payments table
        $sql = "CREATE TABLE IF NOT EXISTS payments (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            transaction_id VARCHAR(255) NULL,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->connection->exec($sql);

        // Ensure columns used by the application exist (backwards compatible with older schema)
        try { $this->connection->exec("ALTER TABLE payments ADD COLUMN payment_id VARCHAR(255) NULL"); } catch (PDOException $e) {}
        try { $this->connection->exec("ALTER TABLE payments ADD COLUMN amount DECIMAL(10,2) NULL"); } catch (PDOException $e) {}
        try { $this->connection->exec("ALTER TABLE payments ADD COLUMN currency VARCHAR(10) NULL"); } catch (PDOException $e) {}
        try { $this->connection->exec("ALTER TABLE payments ADD COLUMN memo TEXT NULL"); } catch (PDOException $e) {}
        try { $this->connection->exec("ALTER TABLE payments ADD COLUMN processed_date DATETIME NULL"); } catch (PDOException $e) {}
        try { $this->connection->exec("ALTER TABLE payments ADD COLUMN created_date DATETIME NULL"); } catch (PDOException $e) {}

        // If created_date is empty, populate from created_at
        try { $this->connection->exec("UPDATE payments SET created_date = created_at WHERE created_date IS NULL"); } catch (PDOException $e) {}

        // Add an index for pending lookups
        try { $this->connection->exec("CREATE INDEX idx_payments_status ON payments(status)"); } catch (PDOException $e) {}

        // Transactions table
        $sql = "CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            type VARCHAR(255) NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->connection->exec($sql);

        // Backwards compatible: add description column if it doesn't exist
        try {
            $this->connection->exec("ALTER TABLE transactions ADD COLUMN description TEXT NULL");
        } catch (PDOException $e) {
            // Ignore error if column already exists
        }
        
        // Withdrawals table
        $sql = "CREATE TABLE IF NOT EXISTS withdrawals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            amount DECIMAL(10,2),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            wallet_address VARCHAR(255),
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            processed_date DATETIME NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        $this->connection->exec($sql);
        
        // Daily check-ins table
        $sql = "CREATE TABLE IF NOT EXISTS daily_checkins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT,
            checkin_date DATE,
            streak INT DEFAULT 1,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            UNIQUE KEY unique_checkin (user_id, checkin_date)
        )";
        $this->connection->exec($sql);

        // Tasks table
        $sql = "CREATE TABLE IF NOT EXISTS tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            reward DECIMAL(10,2) NOT NULL,
            link VARCHAR(255) NOT NULL,
            icon VARCHAR(50) DEFAULT 'fas fa-tasks',
            type ENUM('social', 'daily', 'special') DEFAULT 'social',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->connection->exec($sql);

        // Ads table
        $sql = "CREATE TABLE IF NOT EXISTS ads_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ad_slot VARCHAR(50) UNIQUE,
            ad_code TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active'
        )";
        $this->connection->exec($sql);

        // Mining sessions table
        $sql = "CREATE TABLE IF NOT EXISTS mining_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            status ENUM('active', 'completed', 'claimed') DEFAULT 'active',
            reward DECIMAL(10, 2) DEFAULT 0,
            INDEX (user_id),
            INDEX (status),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        $this->connection->exec($sql);
    }
    
    // Get or create user
    public function getUser($userId, $username = '') {
        $stmt = $this->connection->prepare("SELECT *, is_admin FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // User doesn't exist, create a new one
            $stmt = $this->connection->prepare("INSERT INTO users (id, username, balance, subscription_type, joined_date) VALUES (?, ?, 0, 'free', NOW())");
            $stmt->execute([$userId, $username]);
            
            // Fetch the newly created user
            $stmt = $this->connection->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $user;
    }

    public function getActiveMiningSession($userId) {
        $stmt = $this->connection->prepare("SELECT * FROM mining_sessions WHERE user_id = ? AND status IN ('active', 'completed') ORDER BY id DESC LIMIT 1");
        $stmt->execute([$userId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($session && $session['status'] === 'active' && strtotime($session['end_time']) <= time()) {
            $stmt = $this->connection->prepare("UPDATE mining_sessions SET status = 'completed' WHERE id = ?");
            $stmt->execute([$session['id']]);
            $session['status'] = 'completed';
        }
        
        return $session;
    }

    public function startMining($userId) {
        $activeSession = $this->getActiveMiningSession($userId);
        if ($activeSession && ($activeSession['status'] === 'active' || $activeSession['status'] === 'completed')) {
            return ['success' => false, 'message' => 'Mining session already active or pending claim.'];
        }

        $user = $this->getUser($userId);
        $reward = ($user['subscription_type'] === 'paid') ? 50 : 25;
        $startTime = date('Y-m-d H:i:s');
        $endTime = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $this->connection->prepare("INSERT INTO mining_sessions (user_id, start_time, end_time, reward, status) VALUES (?, ?, ?, ?, 'active')");
        if ($stmt->execute([$userId, $startTime, $endTime, $reward])) {
            return ['success' => true, 'message' => 'Mining started!', 'end_time' => $endTime];
        }
        return ['success' => false, 'message' => 'Failed to start mining.'];
    }

    public function claimMiningReward($userId) {
        $session = $this->getActiveMiningSession($userId);
        if (!$session || ($session['status'] !== 'completed' && $session['status'] !== 'active')) {
            return ['success' => false, 'message' => 'No reward to claim yet.'];
        }

        $startTs = isset($session['start_time']) ? strtotime($session['start_time']) : null;
        $endTs = isset($session['end_time']) ? strtotime($session['end_time']) : null;
        if (!$startTs || !$endTs) {
            return ['success' => false, 'message' => 'Invalid mining session.'];
        }

        $nowTs = time();
        $duration = max(1, ($endTs - $startTs));
        $elapsed = max(0, min($nowTs - $startTs, $duration));

        $fullReward = (float)($session['reward'] ?? 0);
        $mined = $fullReward * ($elapsed / $duration);
        $mined = max(0, round($mined, 2));

        if ($mined <= 0) {
            return ['success' => false, 'message' => 'No reward to claim yet.'];
        }

        $this->connection->beginTransaction();
        try {
            $stmt = $this->connection->prepare("UPDATE mining_sessions SET status = 'claimed' WHERE id = ?");
            $stmt->execute([$session['id']]);

            // Update user balance using a direct query since updateBalance might be used differently
            $stmt = $this->connection->prepare("UPDATE users SET balance = balance + ?, total_earned = total_earned + ? WHERE id = ?");
            $stmt->execute([$mined, $mined, $userId]);

            // Log transaction
            try {
                $stmt = $this->connection->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, 'mining_reward', $mined, 'Mining reward claimed']);
            } catch (PDOException $e) {
                // Backwards compatible if description column doesn't exist
                $stmt = $this->connection->prepare("INSERT INTO transactions (user_id, type, amount) VALUES (?, ?, ?)");
                $stmt->execute([$userId, 'mining_reward', $mined]);
            }

            $this->connection->commit();

            $user = $this->getUser($userId);
            return ['success' => true, 'message' => 'Reward claimed successfully!', 'claimed' => $mined, 'new_balance' => $user['balance']];
        } catch (Exception $e) {
            $this->connection->rollBack();
            return ['success' => false, 'message' => 'Failed to claim reward.'];
        }
    }
    
    public function saveWalletAddress($userId, $walletAddress) {
        $stmt = $this->connection->prepare("UPDATE users SET wallet_address = ? WHERE id = ?");
        return $stmt->execute([$walletAddress, $userId]);
    }

    // Update user balance
    public function updateBalance($userId, $amount, $type = 'task_reward') {
        // This method is now designed to be called within an existing transaction.
        
        // Update user balance
        $stmt = $this->connection->prepare("
            UPDATE users 
            SET balance = balance + ?, total_earned = total_earned + ? 
            WHERE id = ?
        ");
        $stmt->execute([$amount, $amount, $userId]);
        
        // Log transaction
        $stmt = $this->connection->prepare("
            INSERT INTO transactions (user_id, type, amount) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $type, $amount]);
        
        return true; // No commit or rollback here
    }
    
    // Add referral
    public function addReferral($referrerId, $referredId) {
        $this->connection->beginTransaction();
        
        try {
            // Check if referral already exists
            $stmt = $this->connection->prepare("
                SELECT COUNT(*) as count FROM referrals 
                WHERE referrer_id = ? AND referred_id = ?
            ");
            $stmt->execute([$referrerId, $referredId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                throw new Exception("Referral already exists");
            }
            
            // Get referrer's subscription type
            $stmt = $this->connection->prepare("SELECT subscription_type FROM users WHERE id = ?");
            $stmt->execute([$referrerId]);
            $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $bonusAmount = ($referrer['subscription_type'] == 'paid') ? 10 : 3;
            
            // Update referred user
            $stmt = $this->connection->prepare("
                UPDATE users SET referrer_id = ? WHERE id = ?
            ");
            $stmt->execute([$referrerId, $referredId]);
            
            // Add referral record
            $stmt = $this->connection->prepare("
                INSERT INTO referrals (referrer_id, referred_id, bonus_amount) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$referrerId, $referredId, $bonusAmount]);
            
            // Give bonus to direct referrer
            $this->updateBalance($referrerId, $bonusAmount, 'referral_bonus', 'Direct referral bonus');
            
            // Handle multi-level bonuses for paid users (up to 10 generations)
            if ($referrer['subscription_type'] == 'paid') {
                $this->processMultiLevelBonuses($referrerId, $referredId);
            }
            
            $this->connection->commit();
            return $bonusAmount;
        } catch (Exception $e) {
            $this->connection->rollback();
            return false;
        }
    }
    
    // Process multi-level bonuses for paid users
    private function processMultiLevelBonuses($directReferrerId, $newUserId) {
        $currentUserId = $directReferrerId;
        $generation = 1;
        $maxGenerations = 10;
        
        while ($generation <= $maxGenerations && $currentUserId) {
            // Get the current user's referrer (upline)
            $stmt = $this->connection->prepare("SELECT referrer_id, subscription_type FROM users WHERE id = ?");
            $stmt->execute([$currentUserId]);
            $upline = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$upline || !$upline['referrer_id']) {
                break; // No more upline
            }
            
            $uplineId = $upline['referrer_id'];
            
            // Only give bonuses to paid users
            if ($upline['subscription_type'] == 'paid') {
                // Calculate bonus based on generation (mathematical rate)
                $bonusAmount = $this->calculateMultiLevelBonus($generation);
                
                if ($bonusAmount > 0) {
                    $this->updateBalance($uplineId, $bonusAmount, 'referral_bonus', 
                        "Generation {$generation} bonus from user {$newUserId}");
                }
            }
            
            $currentUserId = $uplineId;
            $generation++;
        }
    }
    
    // Calculate multi-level bonus based on generation
    private function calculateMultiLevelBonus($generation) {
        // Mathematical rate: 10, 5, 3, 2, 1, 0.5, 0.3, 0.2, 0.1, 0.05
        $bonusRates = [
            1 => 10,   // Generation 1: 10 PCN
            2 => 5,    // Generation 2: 5 PCN
            3 => 3,    // Generation 3: 3 PCN
            4 => 2,    // Generation 4: 2 PCN
            5 => 1,    // Generation 5: 1 PCN
            6 => 0.5,  // Generation 6: 0.5 PCN
            7 => 0.3,  // Generation 7: 0.3 PCN
            8 => 0.2,  // Generation 8: 0.2 PCN
            9 => 0.1,  // Generation 9: 0.1 PCN
            10 => 0.05 // Generation 10: 0.05 PCN
        ];
        
        return isset($bonusRates[$generation]) ? $bonusRates[$generation] : 0;
    }
    
    // Get user referrals
    public function getUserReferrals($userId) {
        $stmt = $this->connection->prepare("
            SELECT u.* FROM users u 
            INNER JOIN referrals r ON u.id = r.referred_id 
            WHERE r.referrer_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get user statistics
    public function getUserStats($userId) {
        $stmt = $this->connection->prepare("
            SELECT 
                u.*,
                COUNT(r.referred_id) as referral_count,
                SUM(r.bonus_amount) as referral_earnings
            FROM users u 
            LEFT JOIN referrals r ON u.id = r.referrer_id 
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get all users
    public function getAllUsers() {
        $stmt = $this->connection->prepare("SELECT * FROM users ORDER BY joined_date DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get total statistics
    public function getTotalStats() {
        $stmt = $this->connection->prepare("
            SELECT 
                (SELECT COUNT(*) FROM users) as total_users,
                COALESCE((SELECT SUM(balance) FROM users), 0) as total_balance,
                COALESCE((SELECT SUM(total_earned) FROM users), 0) as total_earned,
                COALESCE((SELECT SUM(amount) FROM transactions WHERE type = 'withdrawal'), 0) as total_withdrawn,
                COALESCE((SELECT COUNT(*) FROM users WHERE updated_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)), 0) as active_users,
                COALESCE((SELECT SUM(balance) FROM users), 0) as total_coins_distributed,
                (SELECT COUNT(*) FROM referrals) as total_referrals
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get user subscription counts
    public function getUsersWithPagination($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $stmt = $this->connection->prepare("
            SELECT id, username, subscription_type, balance, joined_date 
            FROM users 
            ORDER BY joined_date DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user subscription counts
    public function getUserSubscriptionCounts() {
        $stmt = $this->connection->prepare("
            SELECT subscription_type, COUNT(*) as count 
            FROM users 
            GROUP BY subscription_type
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = ['free' => 0, 'paid' => 0];
        foreach ($results as $row) {
            if (isset($counts[$row['subscription_type']])) {
                $counts[$row['subscription_type']] = (int)$row['count'];
            }
        }
        return $counts;
    }
    
    // Create withdrawal request
    public function createWithdrawal($userId, $amount, $walletAddress) {
        $this->connection->beginTransaction();
        
        try {
            // Check if user has enough balance
            $stmt = $this->connection->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user['balance'] < $amount) {
                throw new Exception("Insufficient balance");
            }
            
            // Create withdrawal request
            $stmt = $this->connection->prepare("
                INSERT INTO withdrawals (user_id, amount, wallet_address) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $amount, $walletAddress]);
            
            // Deduct balance
            $stmt = $this->connection->prepare("
                UPDATE users SET balance = balance - ? WHERE id = ?
            ");
            $stmt->execute([$amount, $userId]);
            
            // Log transaction
            $stmt = $this->connection->prepare("
                INSERT INTO transactions (user_id, type, amount, description) 
                VALUES (?, 'withdrawal', ?, 'Withdrawal request')
            ");
            $stmt->execute([$userId, $amount]);
            
            $this->connection->commit();
            return true;
        } catch (Exception $e) {
            $this->connection->rollback();
            return false;
        }
    }
    
    // Daily check-in methods
    public function canCheckIn($userId) {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as count FROM daily_checkins 
            WHERE user_id = ? AND checkin_date = CURDATE()
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }
    
    public function performDailyCheckIn($userId) {
        $this->connection->beginTransaction();
        
        try {
            // Check if already checked in today
            if (!$this->canCheckIn($userId)) {
                throw new Exception("Already checked in today");
            }
            
            // Add check-in record
            $stmt = $this->connection->prepare("
                INSERT INTO daily_checkins (user_id, checkin_date) 
                VALUES (?, CURDATE())
            ");
            $stmt->execute([$userId]);
            
            // Update user balance
            $stmt = $this->connection->prepare("
                UPDATE users SET balance = balance + 5, total_earned = total_earned + 5 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Log transaction
            $stmt = $this->connection->prepare("
                INSERT INTO transactions (user_id, type, amount, description) 
                VALUES (?, 'daily_checkin', ?, 'Daily check-in bonus')
            ");
            $stmt->execute([$userId, 5]);
            
            $this->connection->commit();
            return true;
        } catch (Exception $e) {
            $this->connection->rollback();
            return false;
        }
    }
    
    public function getCheckInStreak($userId) {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as streak FROM daily_checkins 
            WHERE user_id = ? AND checkin_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['streak'];
    }
    
    // Subscription methods
    public function upgradeToPaid($userId) {
        $stmt = $this->connection->prepare("
            UPDATE users SET subscription_type = 'paid', subscription_date = NOW() WHERE id = ? AND subscription_type = 'free'
        ");
        $stmt->execute([$userId]);
        $updated = $stmt->rowCount() > 0;

        if ($updated) {
            // Add subscription bonus for admin-initiated upgrade
            $this->updateBalance($userId, 500, 'subscription_bonus', 'Paid plan upgrade bonus by admin');
        }

        // Return true if a row was updated, false otherwise
        return $updated;
    }
    
    public function downgradeToFree($userId) {
        $stmt = $this->connection->prepare("
            UPDATE users SET subscription_type = 'free', subscription_date = NULL WHERE id = ? AND subscription_type = 'paid'
        ");
        $stmt->execute([$userId]);
        // Return true if a row was updated, false otherwise
        return $stmt->rowCount() > 0;
    }
    
    public function getReferralBonus($referrerId, $isPaidUser) {
        return $isPaidUser ? 10 : 3;
    }
    
    // Referral Tree Methods
    public function getReferralTree($userId, $maxDepth = 3) {
        $tree = [];
        $this->buildReferralTree($userId, $tree, 0, $maxDepth);
        return $tree;
    }
    
    private function buildReferralTree($userId, &$tree, $depth, $maxDepth) {
        if ($depth >= $maxDepth) {
            return;
        }

        // Get user info
        $stmt = $this->connection->prepare("
            SELECT id, username, subscription_type, balance, joined_date 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return;
        }

        // Get direct referrals
        $stmt = $this->connection->prepare("
            SELECT u.id as user_id, u.username, u.balance, u.subscription_type, r.created_date as referral_date
            FROM referrals r
            JOIN users u ON r.referred_id = u.id
            WHERE r.referrer_id = ?
            ORDER BY u.joined_date DESC
        ");
        $stmt->execute([$userId]);
        $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tree['user'] = $user;
        $tree['referrals'] = [];

        foreach ($referrals as $referral) {
            $childTree = [];
            $this->buildReferralTree($referral['user_id'], $childTree, $depth + 1, $maxDepth);
            $tree['referrals'][] = $childTree;
        }
    }
    
    // Task Management Methods
    public function getAllTasks() {
        $stmt = $this->connection->prepare("SELECT * FROM tasks ORDER BY id DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateTask($taskId, $title, $reward, $link, $icon, $type) {
        $stmt = $this->connection->prepare("UPDATE tasks SET title = ?, reward = ?, link = ?, icon = ?, type = ? WHERE id = ?");
        $stmt->execute([$title, $reward, $link, $icon, $type, $taskId]);
        return $stmt->rowCount() > 0;
    }

    public function getTasksCount() {
        $stmt = $this->connection->prepare("SELECT COUNT(*) FROM tasks");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function addTask($title, $reward, $link, $icon, $type) {
        $stmt = $this->connection->prepare("INSERT INTO tasks (title, reward, link, icon, type) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$title, $reward, $link, $icon, $type]);
    }

    public function deleteTask($taskId) {
        $stmt = $this->connection->prepare("DELETE FROM tasks WHERE id = ?");
        return $stmt->execute([$taskId]);
    }

    public function deleteTasksByType($type) {
        $stmt = $this->connection->prepare("DELETE FROM tasks WHERE type = ?");
        return $stmt->execute([$type]);
    }

    // Ads Management Methods
    public function getAdsConfig() {
        $stmt = $this->connection->prepare("SELECT * FROM ads_config");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAdSlot($slot, $code, $status) {
        $stmt = $this->connection->prepare("INSERT INTO ads_config (ad_slot, ad_code, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE ad_code = ?, status = ?");
        return $stmt->execute([$slot, $code, $status, $code, $status]);
    }

    public function getReferralStats($userId) {
        $stmt = $this->connection->prepare("
            SELECT 
                COUNT(DISTINCT r1.referred_id) as direct_referrals,
                COUNT(DISTINCT r2.referred_id) as level2_referrals,
                COUNT(DISTINCT r3.referred_id) as level3_referrals,
                SUM(CASE WHEN u1.subscription_type = 'paid' THEN 1 ELSE 0 END) as paid_direct,
                SUM(CASE WHEN u1.subscription_type = 'free' THEN 1 ELSE 0 END) as free_direct,
                SUM(CASE WHEN u2.subscription_type = 'paid' THEN 1 ELSE 0 END) as paid_level2,
                SUM(CASE WHEN u2.subscription_type = 'free' THEN 1 ELSE 0 END) as free_level2
            FROM users me
            LEFT JOIN referrals r1 ON me.id = r1.referrer_id
            LEFT JOIN users u1 ON r1.referred_id = u1.id
            LEFT JOIN referrals r2 ON u1.id = r2.referrer_id
            LEFT JOIN users u2 ON r2.referred_id = u2.id
            LEFT JOIN referrals r3 ON u2.id = r3.referrer_id
            WHERE me.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get connection
    public function getConnection() {
        return $this->connection;
    }
    
    // Create payment request
    public function createPaymentRequest($userId, $paymentId, $amount, $currency = 'TON') {
        $stmt = $this->connection->prepare("
            INSERT INTO payments (payment_id, user_id, amount, currency) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$paymentId, $userId, $amount, $currency]);
    }
    
    // Update payment with transaction details
    public function updatePaymentWithTransaction($paymentId, $transactionId, $memo = '') {
        $stmt = $this->connection->prepare("
            UPDATE payments 
            SET transaction_id = ?, memo = ? 
            WHERE payment_id = ?
        ");
        return $stmt->execute([$transactionId, $memo, $paymentId]);
    }
    
    // Get pending payments
    public function getPendingPayments() {
        $stmt = $this->connection->prepare("
            SELECT p.*, u.username 
            FROM payments p
            JOIN users u ON p.user_id = u.id
            WHERE p.status = 'pending' 
            ORDER BY COALESCE(p.created_date, p.created_at) ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Send Telegram message
    public function sendTelegramMessage($userId, $text) {
        if (!defined('BOT_TOKEN')) return false;
        
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
        $data = [
            'chat_id' => $userId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }
    
    // Approve payment
    public function approvePayment($paymentId) {
        $this->connection->beginTransaction();
        
        try {
            // Get payment details
            $stmt = $this->connection->prepare("
                SELECT * FROM payments WHERE payment_id = ?
            ");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$payment) {
                throw new Exception("Payment not found");
            }
            
            // Update payment status
            $stmt = $this->connection->prepare("
                UPDATE payments 
                SET status = 'approved', processed_date = NOW() 
                WHERE payment_id = ?
            ");
            $stmt->execute([$paymentId]);
            
            // Upgrade user to paid
            $this->upgradeToPaid($payment['user_id']);
            
            // Add subscription bonus
            $this->updateBalance($payment['user_id'], 500, 'subscription_bonus', 'Paid plan upgrade bonus');
            
            $this->connection->commit();
            
            // Send Notification
            $this->sendTelegramMessage($payment['user_id'], "🌟 <b>Congratulations!</b>\n\nYour payment has been approved. Your account is now upgraded to <b>PREMIUM</b>. You can now enjoy 2x mining speed and 10-level referral bonuses!");
            
            return $payment['user_id']; // Return user ID for notification
        } catch (Exception $e) {
            $this->connection->rollback();
            return false;
        }
    }
    
    // Reject payment
    public function rejectPayment($paymentId, $reason = '') {
        $stmt = $this->connection->prepare("
            UPDATE payments 
            SET status = 'rejected', memo = CONCAT(COALESCE(memo, ''), ' | Rejected: ', ?), processed_date = NOW() 
            WHERE payment_id = ?
        ");
        return $stmt->execute([$reason, $paymentId]);
    }
    
    // Get payment by ID
    public function getPayment($paymentId) {
        $stmt = $this->connection->prepare("
            SELECT * FROM payments WHERE payment_id = ?
        ");
        $stmt->execute([$paymentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get all payments
    public function getAllPayments() {
        $stmt = $this->connection->prepare("
            SELECT * FROM payments ORDER BY created_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get approved payments
    public function getApprovedPayments() {
        $stmt = $this->connection->prepare("
            SELECT * FROM payments WHERE status = 'approved' ORDER BY created_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get rejected payments
    public function getRejectedPayments() {
        $stmt = $this->connection->prepare("
            SELECT * FROM payments WHERE status = 'rejected' ORDER BY created_date DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // New methods for processing payments from admin panel

    public function getPaymentById($id) {
        $stmt = $this->connection->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePaymentStatus($id, $status) {
        $stmt = $this->connection->prepare("
            UPDATE payments 
            SET status = ?, processed_date = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $id]);
    }

    public function updateUserSubscription($userId, $subscriptionType) {
        $stmt = $this->connection->prepare("
            UPDATE users 
            SET subscription_type = ?, subscription_date = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$subscriptionType, $userId]);
    }

    public function isTransactionIdSubmitted($transactionId) {
        $stmt = $this->connection->prepare("SELECT id FROM payments WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        return $stmt->fetchColumn() > 0;
    }

    public function submitPaymentForVerification($userId, $transactionId, $amount, $currency = 'TON') {
        $stmt = $this->connection->prepare("
            INSERT INTO payments (user_id, transaction_id, amount, currency, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        return $stmt->execute([$userId, $transactionId, $amount, $currency]);
    }


}
?>