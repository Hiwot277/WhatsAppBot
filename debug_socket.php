<?php
$host = "script.google.com";
$port = 443;
$timeout = 5;

echo "Connecting to $host:$port with timeout $timeout...\n";
$start = microtime(true);
$fp = fsockopen("ssl://$host", $port, $errno, $errstr, $timeout);
$end = microtime(true);

echo "Time taken: " . ($end - $start) . " seconds\n";

if (!$fp) {
    echo "ERROR: $errstr ($errno)\n";
} else {
    echo "Connected successfully.\n";
    fclose($fp);
}
