<?php
$offsetFile = __DIR__ . '/polling_offset.txt';

if (file_exists($offsetFile)) {
    unlink($offsetFile);
}

header('Content-Type: text/plain');
echo "Polling offset reset. Next polling run will fetch fresh updates.\n";
