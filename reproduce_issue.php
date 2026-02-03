<?php
require_once __DIR__ . '/processor.php';

// Mock state clearing
$stateDir = __DIR__ . '/state';
if (file_exists($stateDir)) {
    $files = glob($stateDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) unlink($file);
    }
}

$from = '1234567890';
$text = 'start';

echo "Simulating message: '$text' from $from\n";
$reply = processMessage($from, $text);

echo "Reply:\n";
print_r($reply);

if (isset($reply['text']) && $reply['text'] === 'start') {
    echo "ISSUE REPRODUCED: Bot echoed 'start'\n";
} else {
    echo "Bot did NOT echo 'start'. Reply text: " . ($reply['text'] ?? 'NULL') . "\n";
}
