<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Allow CLI
if (php_sapi_name() === 'cli') {
    return;
}

// If already verified, allow
if (isset($_SESSION['tg_verified']) && $_SESSION['tg_verified'] === true) {
    return;
}

// Not verified: block normal browsers, attempt auto-verify inside Telegram WebApp
header('Content-Type: text/html; charset=UTF-8');
http_response_code(403);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Open in Telegram</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0b0b1a;color:#fff;font-family:Poppins,system-ui,-apple-system,Segoe UI,Roboto;}
        .card{width:min(520px,92vw);background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:18px;padding:22px;text-align:center;}
        .title{font-size:1.2rem;font-weight:700;margin:0 0 8px;}
        .desc{margin:0 0 14px;color:rgba(255,255,255,0.7);line-height:1.6;}
        .btn{display:inline-block;padding:12px 16px;border-radius:14px;background:linear-gradient(135deg,#00f2ff 0%,#0061ff 100%);color:#fff;text-decoration:none;font-weight:700;}
        .small{margin-top:12px;font-size:0.85rem;color:rgba(255,255,255,0.55);}
    </style>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body>
    <div class="card">
        <p class="title">This app opens only inside Telegram</p>
        <p class="desc">Please open this page from the Telegram mobile app as a Mini App.</p>
        <a class="btn" href="#" onclick="tryInit(); return false;">Retry inside Telegram</a>
        <div class="small" id="status">Waiting for Telegram WebApp...</div>
    </div>

    <script>
        async function tryInit(){
            const status = document.getElementById('status');
            try{
                if(!window.Telegram || !Telegram.WebApp){
                    status.textContent = 'Telegram WebApp not detected.';
                    return;
                }
                const initData = Telegram.WebApp.initData;
                if(!initData){
                    status.textContent = 'initData missing. Please open from Telegram.';
                    return;
                }
                status.textContent = 'Verifying...';

                const form = new FormData();
                form.append('initData', initData);
                form.append('platform', (Telegram.WebApp.platform || '').toString());

                const res = await fetch('telegram_init.php', { method:'POST', body: form, credentials:'same-origin' });
                const json = await res.json();
                if(json && json.success){
                    status.textContent = 'Verified. Loading...';
                    location.reload();
                    return;
                }
                status.textContent = (json && json.message) ? json.message : 'Verification failed.';
            }catch(e){
                console.error(e);
                status.textContent = 'Error during verification.';
            }
        }

        // Auto try
        window.addEventListener('load', () => {
            setTimeout(tryInit, 200);
        });
    </script>
</body>
</html>
<?php
exit;
