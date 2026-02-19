# 🚀 PCN Coin Bot Startup Guide

## 📋 Prerequisites
- XAMPP installed and running
- PHP 7.4+ available
- MySQL database created: `pcn_coin_bot`

## 🔧 Step 1: Start XAMPP Services

### Option A: Using XAMPP Control Panel
1. Open XAMPP Control Panel
2. Start Apache
3. Start MySQL

### Option B: Using Command Line
```bash
# Start Apache (if service name is different, check XAMPP installation)
net start Apache2.4

# Start MySQL
net start MySQL
```

## 🤖 Step 2: Start the Bot

### Option A: Using Batch File (Windows)
```bash
start_bot.bat
```

### Option B: Using PowerShell Script
```powershell
.\start_bot.ps1
```

### Option C: Direct PHP Command
```bash
php start_bot.php
```

### Option D: Manual Setup Check
```bash
php setup.php
```

## 🌐 Step 3: Access Web Interface

### Main Pages:
- **Home Page:** http://localhost/telegram/index.php
- **Referral System:** http://localhost/telegram/referral.php
- **Payment System:** http://localhost/telegram/payment.php
- **Admin Panel:** http://localhost/telegram/admin_payments.php

## 📱 Step 4: Test Telegram Bot

### Bot Username: @PCN_OfficialBot

### Admin Commands (for sajibrasel2):
- `/start` - Start admin panel
- `/status` - Check bot status
- `/users` - View user statistics
- `/stats` - Referral statistics
- `/broadcast` - Send message to all users
- `/help` - Admin help

### User Commands:
- `/start` - Start the bot
- `/balance` - Check PCN balance
- `/referral` - Get referral link
- `/withdraw` - Withdraw PCN coins
- `/stats` - Your referral statistics
- `/help` - Get help

## 🔍 Step 5: Monitor Bot Activity

### Log Files:
- `pcn_bot_log.txt` - Bot activity logs
- `bot_log.txt` - General bot logs

### Database Tables:
- `users` - User information
- `referrals` - Referral relationships
- `withdrawals` - Withdrawal requests
- `transactions` - Transaction history

## 🛠️ Troubleshooting

### Bot not responding?
1. Check if XAMPP is running
2. Verify bot token in config.php
3. Run `php setup.php` to check configuration

### Database connection failed?
1. Make sure MySQL is running
2. Check database name: `pcn_coin_bot`
3. Verify database credentials in config.php

### Web interface not loading?
1. Check Apache is running
2. Verify file permissions
3. Check for PHP errors in logs

## 📊 Quick Status Check

```bash
# Check bot status
php status.php

# Check database connection
php setup.php

# View recent logs
type pcn_bot_log.txt
```

## 🎯 Bot Features Summary

- ✅ **Referral System** - Multi-level bonuses
- ✅ **Payment Integration** - TON wallet support
- ✅ **Admin Panel** - User management
- ✅ **Web Interface** - Mobile responsive
- ✅ **Daily Check-in** - 5 PCN daily bonus
- ✅ **Withdrawal System** - Minimum 100 PCN
- ✅ **Statistics Dashboard** - Real-time stats

## 🔗 Important Links

- **Bot Link:** https://t.me/PCN_OfficialBot
- **Local Web:** http://localhost/telegram/
- **Setup Check:** http://localhost/telegram/setup.php

---

**🎉 Your PCN Coin Bot is now ready to use!** 