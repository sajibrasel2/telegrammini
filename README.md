# PCN Coin Referral Bot

This project is a Telegram bot for PCN Coin cryptocurrency referral system. The bot allows users to earn PCN coins through referrals and provides admin controls for managing the system.

## 📋 File Structure

```
telegram/
├── config.php      # Bot configuration
├── bot.php         # Main bot logic
├── setup.php       # Setup script
├── test.php        # Test script
├── users.json      # User data (created automatically)
├── pcn_bot_log.txt # Activity logs
└── README.md       # This file
```

## 🚀 Setup Instructions

### 1. Project Setup
Copy the project to your XAMPP `htdocs` folder.

### 2. Configuration
The `config.php` file contains your bot token and admin user ID:

```php
define('BOT_TOKEN', '8141270317:AAGFG4Y7iTQlAbN6pHGGFGmmNRz-i5Hg_Es');
define('ADMIN_USER_ID', 'sajibrasel2');
```

### 3. Setup Check
Go to `http://localhost/telegram/setup.php` in your browser to check setup status.

### 4. Bot Testing
Send a message to your bot on Telegram and check the response.

## 🤖 Bot Features

### Admin Commands (for `sajibrasel2` only)
- `/start` - Start admin panel
- `/status` - Check bot status
- `/users` - View user statistics
- `/stats` - Referral statistics
- `/broadcast` - Send message to all users
- `/help` - Admin help

### User Commands
- `/start` - Start the bot
- `/balance` - Check PCN balance
- `/referral` - Get referral link
- `/withdraw` - Withdraw PCN coins
- `/stats` - Your referral statistics
- `/help` - Get help

## 💰 Referral System

### How it works:
1. Users get their referral link using `/referral`
2. When someone uses their link, both users earn 10 PCN
3. Users can withdraw when they reach 100 PCN
4. All data is stored in `users.json` file

### Referral Process:
1. User A gets referral link: `https://t.me/PCN_OfficialBot?start=ref123456`
2. User B clicks the link and joins
3. Both User A and User B earn 10 PCN coins
4. User A gets notified of the new referral

## 🔧 Webhook Setup (Optional)

If you want to use webhooks:

1. Set `WEBHOOK_URL` in `config.php`:
```php
define('WEBHOOK_URL', 'https://yourdomain.com/telegram/bot.php');
```

2. Run `setup.php` to configure the webhook.

## 📝 Logging

All bot activities are logged to `pcn_bot_log.txt`.

## 🔒 Security

- Only admin user can access special commands
- Other users get standard referral responses
- All messages are logged
- User data is stored securely in JSON format

## 🛠️ Troubleshooting

### Bot not responding?
1. Check `setup.php`
2. Verify bot token is correct
3. Make sure XAMPP is running

### Admin commands not working?
1. Check your Telegram username is `sajibrasel2`
2. Send a message to the bot
3. Check log file

### Referral system issues?
1. Check `users.json` file exists
2. Verify file permissions
3. Test with new users

## 📞 Help

If you have problems:
1. Check log files
2. Run `setup.php`
3. Verify bot token is valid

## 🎯 Next Steps

1. Send message to your bot on Telegram
2. Test admin commands
3. Test referral system with users
4. Add custom features
5. Integrate with database

## 💡 PCN Coin Configuration

- **Coin Name:** PCN Coin
- **Coin Symbol:** PCN
- **Referral Bonus:** 10 PCN per referral
- **Minimum Withdrawal:** 100 PCN
- **Admin User:** sajibrasel2

---

**Setup complete! Your PCN Coin referral bot is ready to use.** 🎉 