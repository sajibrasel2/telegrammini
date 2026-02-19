<?php
require_once __DIR__ . '/telegram_gate.php';
require_once 'config.php';
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$user_id_query = $user_id ? '?user_id=' . $user_id : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCN Coin Blog - Cryptocurrency Insights & Mining Guides</title>
    <meta name="description" content="Explore the latest in cryptocurrency, PCN Coin mining strategies, and blockchain technology. Learn how to maximize your earnings in the PCN ecosystem.">
    <meta name="keywords" content="crypto, bitcoin, pcn coin, mining, blockchain, referral, passive income, ton, telegram mini app">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <?php include 'app_style.php'; ?>
    <style>
        .blog-container { padding: 20px; max-width: 800px; margin: 0 auto; }
        .blog-card { 
            background: rgba(255,255,255,0.03); 
            border-radius: 20px; 
            overflow: hidden; 
            margin-bottom: 25px; 
            border: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.3s;
        }
        .blog-card:hover { transform: translateY(-5px); }
        .blog-image { width: 100%; height: 200px; object-fit: cover; background: #1a1a2e; }
        .blog-content { padding: 20px; }
        .blog-tag { 
            display: inline-block; 
            padding: 4px 12px; 
            background: rgba(0,242,255,0.1); 
            color: var(--primary); 
            border-radius: 20px; 
            font-size: 0.75rem; 
            margin-bottom: 10px;
            font-weight: 600;
        }
        .blog-title { font-size: 1.4rem; font-weight: 700; margin-bottom: 10px; color: white; line-height: 1.3; }
        .blog-excerpt { color: var(--text-dim); font-size: 0.9rem; line-height: 1.6; margin-bottom: 15px; }
        .read-more { color: var(--primary); font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 5px; }
        .blog-header { text-align: center; padding: 40px 20px; }
        .blog-header h1 { font-size: 2.2rem; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="blog-header">
            <h1><i class="fas fa-newspaper" style="color: var(--primary);"></i> PCN Insights</h1>
            <p style="color: var(--text-dim);">Your ultimate guide to the PCN Revolution</p>
        </div>

        <div class="blog-container">
            <!-- Blog 1 -->
            <div class="blog-card">
                <div class="blog-content">
                    <span class="blog-tag">Mining Guide</span>
                    <h2 class="blog-title">The Future of Mobile Mining: Why PCN Coin is Leading the Charge</h2>
                    <p class="blog-excerpt">Discover how PCN Coin is transforming the way we think about mobile mining. Learn why the ecosystem is designed for sustainability and high rewards...</p>
                    <a href="pcn-mining-guide.php<?php echo $user_id_query; ?>" class="read-more">Read Full Article <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Blog 2 -->
            <div class="blog-card">
                <div class="blog-content">
                    <span class="blog-tag">Crypto Trends</span>
                    <h2 class="blog-title">Understanding Blockchain Ecosystems in 2026: The Role of PCN</h2>
                    <p class="blog-excerpt">Blockchain is evolving faster than ever. In this deep dive, we explore how PCN's unique distribution model sets it apart from traditional cryptocurrencies...</p>
                    <a href="blockchain-ecosystem-2026.php<?php echo $user_id_query; ?>" class="read-more">Read Full Article <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Blog 3 -->
            <div class="blog-card">
                <div class="blog-content">
                    <span class="blog-tag">Referral Strategy</span>
                    <h2 class="blog-title">Mastering the PCN Referral Network: How to Build a 10-Level Empire</h2>
                    <p class="blog-excerpt">Built for the community, PCN's referral system offers unprecedented growth opportunities. Here are the top 5 strategies to maximize your network bonuses...</p>
                    <a href="referral-empire-strategy.php<?php echo $user_id_query; ?>" class="read-more">Read Full Article <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php<?php echo $user_id_query; ?>" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="tasks.php<?php echo $user_id_query; ?>" class="nav-item">
            <i class="fas fa-tasks"></i>
            <span>Tasks</span>
        </a>
        <a href="referral.php<?php echo $user_id_query; ?>" class="nav-item">
            <i class="fas fa-users"></i>
            <span>Friends</span>
        </a>
        <a href="blog.php<?php echo $user_id_query; ?>" class="nav-item active">
            <i class="fas fa-newspaper"></i>
            <span>Blog</span>
        </a>
    </nav>
</body>
</html>
