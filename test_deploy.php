<?php
header('Content-Type: text/plain; charset=utf-8');

echo "OK\n";
echo "DEPLOY_MARKER\n";
echo "TIME_UTC=" . gmdate('Y-m-d H:i:s') . "\n";

echo "BUILD_ID=";
$buildId = @gmdate('YmdHis', @filemtime(__FILE__));
echo ($buildId !== '' ? $buildId : 'unknown');
echo "\n";
