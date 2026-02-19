<?php
// Shared CSS for Mobile App UI consistency
?>
<style>
    :root {
        --primary: #4ecdc4;
        --secondary: #45b7af;
        --accent: #feca57;
        --bg-dark: #0f0f23;
        --bg-card: rgba(255, 255, 255, 0.08);
        --text-main: #ffffff;
        --success: #2ecc71;
        --danger: #ff4757;
        --bg: #0b0b1a;
        --card-bg: rgba(255, 255, 255, 0.05);
        --text-main: #ffffff;
        --text-dim: rgba(255, 255, 255, 0.6);
        --accent-gradient: linear-gradient(135deg, #00f2ff 0%, #0061ff 100%);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        -webkit-tap-highlight-color: transparent;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: var(--bg);
        color: var(--text-main);
        line-height: 1.6;
        min-height: 100vh;
        padding-bottom: 90px; /* Nav space */
        overflow-x: hidden;
    }

    .app-container {
        max-width: 500px;
        margin: 0 auto;
        padding: 20px 20px 100px 20px;
    }

    /* Reusable Card Component */
    .app-card {
        background: var(--card-bg);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .app-card:active {
        transform: scale(0.98);
    }

    /* Modern Button Component */
    .app-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 15px;
        border-radius: 15px;
        border: none;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        background: var(--accent-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 242, 255, 0.2);
    }
    .app-btn:active {
        transform: translateY(2px);
        box-shadow: 0 2px 10px rgba(0, 242, 255, 0.2);
    }
    .app-btn.secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        box-shadow: none;
    }

    /* Header Style */
    .app-header {
        text-align: center;
        margin-bottom: 30px;
        padding-top: 10px;
    }

    .app-header h1 {
        font-size: 1.8rem;
        background: linear-gradient(45deg, #ff6b6b, #4ecdc4);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 5px;
    }

        text-decoration: none;
        font-size: 1rem;
    }

    .app-btn:active { transform: scale(0.98); }

    .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
    .btn-outline { background: transparent; border: 1px solid var(--primary); color: var(--primary); }

    /* Bottom Nav Style */
    .bottom-nav {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        background: var(--nav-bg);
        height: 75px;
        display: flex;
        justify-content: space-around;
        align-items: center;
        backdrop-filter: blur(15px);
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        z-index: 2000;
        padding-bottom: env(safe-area-inset-bottom);
    }

    .nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: var(--text-dim);
        font-size: 0.75rem;
        flex: 1;
        transition: color 0.3s;
    }

    .nav-item i { font-size: 1.4rem; margin-bottom: 4px; }
    .nav-item.active { color: var(--primary); }

    /* Hide scrollbars */
    ::-webkit-scrollbar { display: none; }
</style>
