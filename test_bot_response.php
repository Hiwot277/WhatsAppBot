<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_utils.php';
require_once __DIR__ . '/scripts.php';
require_once __DIR__ . '/processor.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test with a clean state
$phone = '1234567890';
$state = [];

// Send initial message
echo "Sending: Hi\n";
$response = processMessage($phone, 'Hi');
print_r($response);

// Send 'Yes' response
echo "\nSending: Yes\n";
$response = processMessage($phone, 'Yes');
print_r($response);

// If there are buttons, select the first one
if (isset($response['buttons']) && !empty($response['buttons'])) {
    $firstButton = $response['buttons'][0]['id'];
    echo "\nSending: $firstButton\n";
    $response = processMessage($phone, $firstButton);
    print_r($response);
}

// Show the final state
$stateFile = __DIR__ . '/state/state_' . md5($phone) . '.json';
if (file_exists($stateFile)) {
    echo "\nFinal state: " . file_get_contents($stateFile) . "\n";
} else {
    echo "\nNo state file found.\n";
}
