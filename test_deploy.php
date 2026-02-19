<?php
header('Content-Type: text/plain; charset=utf-8');

echo "OK\n";
echo "DEPLOY_MARKER\n";
echo "TIME_UTC=" . gmdate('Y-m-d H:i:s') . "\n";

echo "COMMIT=";
$commit = @trim(@shell_exec('git rev-parse --short HEAD'));
echo ($commit !== '' ? $commit : 'unknown');
echo "\n";
