Write-Host "Starting PCN Coin Bot..." -ForegroundColor Green
Write-Host ""
Write-Host "Make sure XAMPP is running (Apache and MySQL)" -ForegroundColor Yellow
Write-Host ""
Write-Host "Bot will start in polling mode..." -ForegroundColor Cyan
Write-Host "Press Ctrl+C to stop the bot" -ForegroundColor Red
Write-Host ""

# Check if PHP is available
try {
    $phpVersion = php -v 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "PHP found, starting bot..." -ForegroundColor Green
        php start_bot.php
    } else {
        Write-Host "PHP not found! Please install PHP or add it to PATH" -ForegroundColor Red
    }
} catch {
    Write-Host "Error: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "Press any key to exit..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown") 